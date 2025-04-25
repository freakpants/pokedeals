<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class ImportPokemonJapanSealed extends Command
{
    protected $signature = 'pokemon:import-japan-sealed {--dry-run : Do not import or write to disk}';

    protected $description = 'Import sealed Pokémon Japan products from tcgcsv.com (excluding cards)';

    public function handle(): int
    {
        $this->info('Fetching all Pokémon Japan groups from tcgcsv.com...');

        $groupsResponse = Http::get('https://tcgcsv.com/tcgplayer/85/groups');

        if (!$groupsResponse->ok()) {
            $this->error('Failed to fetch group data.');
            return Command::FAILURE;
        }

        $groups = $groupsResponse->json()['results'] ?? [];

        $sealedProducts = [];

        $this->output->progressStart(count($groups));

foreach ($groups as $group) {
    $groupId = $group['groupId'];

    $this->output->progressAdvance();

    $productsResponse = Http::get("https://tcgcsv.com/tcgplayer/85/{$groupId}/products");

    if (!$productsResponse->ok()) {
        $this->warn(" Failed to fetch products for group ID {$groupId}. Skipping.");
        continue;
    }

    $products = $productsResponse->json()['results'] ?? [];

    foreach ($products as $product) {
        $isCard = false;

        if (!empty($product['extendedData'])) {
            foreach ($product['extendedData'] as $data) {
                if (strtolower($data['displayName']) !== 'description') {
                    $isCard = true;
                    break;
                }
            }
        }

        if ($isCard) {
            continue;
        }

        $title = $product['name'] ?? 'Unnamed Product';
        $sku = $product['productId'] ?? null;
        $url = $product['url'] ?? '';
        $image = $product['imageUrl'] ?? '';

        
        $productData = [
            'title' => $title,
            'sku' => $sku,
            'price' => '',
            'productUrl' => $url,
            'images' => [$image],
        ];
        
        if (str_contains($title, 'Booster Box')) {
            $productData['type'] = 'japanese_display_box';
        }
        
        $sealedProducts[] = $productData;
    }
}

$this->output->progressFinish();


        if (empty($sealedProducts)) {
            $this->info('No sealed products found.');
            return Command::SUCCESS;
        }

        if ($this->option('dry-run')) {
            $this->info("🔍 Dry run: Found " . count($sealedProducts) . " sealed products.");
            $this->line('Sample product titles:');
            foreach (array_slice($sealedProducts, 0, 10) as $product) {
                $this->line('- ' . $product['title']);
            }
            $this->info('Dry run completed — nothing written or imported.');
            return Command::SUCCESS;
        }

        $this->info('Saving filtered sealed products to temp JSON...');
        $path = storage_path('japan-sealed-products.json');
        file_put_contents($path, json_encode($sealedProducts));

        $this->info('Importing sealed products...');
        $this->call('pokemon:import', ['file' => $path]);

        $this->info('Import completed successfully!');
        return Command::SUCCESS;
    }
}
