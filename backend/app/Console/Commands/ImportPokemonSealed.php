<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class ImportPokemonSealed extends Command
{
    protected $signature = 'pokemon:import-sealed {--dry-run : Do not import or write to disk}';

    protected $description = 'Import sealed Pokémon products from tcgcsv.com (excluding cards)';

    public function handle(): int
    {
        $this->info('Fetching all Pokémon groups from tcgcsv.com...');

        $groupsResponse = Http::get('https://tcgcsv.com/tcgplayer/3/groups');

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

    $productsResponse = Http::get("https://tcgcsv.com/tcgplayer/3/{$groupId}/products");

    if (!$productsResponse->ok()) {
        $this->warn(" Failed to fetch products for group ID {$groupId}. Skipping.");
        continue;
    }

    // inform about the current group and its name
    // $this->info("Fetching products for group ID {$groupId} ({$group['name']})...");

    $products = $productsResponse->json()['results'] ?? [];

    $debug = false;
    foreach ($products as $product) {
        // if groupid is 24269, debug
        if ($groupId == 24269) {
            $debug = true;
        }
        $isCard = false;

        if (!empty($product['extendedData'])) {
            foreach ($product['extendedData'] as $data) {
                if (strtolower($data['displayName']) === 'card number') {
                    // if debug is true, print the data
                    if ($debug) {
                        // we are skipping this product
                        $this->info('Skipping product: ' . $product['name']);
                        // displayname was
                        $this->info('Display name: ' . $data['displayName']);
                    }
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
