<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\DB;
use App\Helpers\PokemonHelper;
use App\Enums\ProductTypes;

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

    $variants = [];
    
    foreach ($products as $product) {
        $isCard = false;

        if (!empty($product['extendedData'])) {
            foreach ($product['extendedData'] as $data) {
                if ($product['productId'] == 209536) {
                    $this->info("Extended Data: {$data['displayName']} => {$data['value']}");
                }
                if (strtolower($data['displayName']) === 'card number' ||
                    strtolower($data['displayName']) === 'card type' ){

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


        // use the pokemon helper to determine product details
        $details = PokemonHelper::determineProductDetails($title);

        if ($details['product_type'] !== ProductTypes::Other->value) {
                // use the clean name with _ as the en_short
                // replace pokemon tcg in the clean name
                $product['cleanName'] = str_replace('Pokemon TCG ', '', $product['cleanName']);

                $enShort = $product['cleanName'];
                // replace spaces in enshort with _
                $enShort = str_replace(' ', '_', $enShort);
                // lowercase the enshort
                $enShort = strtolower($enShort);

                // try the first two words for the en strings
                $enStrings = explode(' ', $product['cleanName']);

                // if there is something like [Pikachu] in the unclean String use that as the en_string
                if (is_string($product['name']) && preg_match('/\[(.*?)\]/', $product['name'], $matches)) {
                    $enStrings = $matches[1];
                    // replace the []
                    $enStrings = str_replace('[', '', $enStrings);
                    $enStrings = str_replace(']', '', $enStrings);
                } else {

                    if(count($enStrings) < 2) {
                        $enStrings = $product['name'];
                    } else {
                        // if any of the strings is a year higher than 1999, use all strings up until that point
                        // check every single string
                        $foundYear = false;
                        foreach ($enStrings as $key => $string) {
                            if (is_numeric($string) && $string > 1999) {
  
                                // use all strings up to this key, separated by a space
                                $enStrings = implode(' ', array_slice($enStrings, 0, $key+1));

                                $foundYear = true;
                                break;
                            }
                        }
                        if(!$foundYear) {
                            $enStrings = $enStrings[0] . ' ' . $enStrings[1];
                        }
    
                        
                    }
                }

                

                // check if there is a variant with this en_short
                if (DB::table('pokemon_product_variants')->where('en_short', $enShort)->exists()) {
                    continue;
                }

                // if the en_string is only "Back to", skip this variant
                if ($enStrings === 'Back to') {
                    continue;
                }

                $variants[] = [
                    'product_type' => $details['product_type'],
                    'en_short' => $enShort,
                    'de_strings' => json_encode([]),
                    'en_strings' => json_encode([$enStrings]),
                    'en_name' => $title
                ];
            }
        
        $sealedProducts[] = $productData;
    }


     // save the variants
        foreach ($variants as $variant) {
            if (!DB::table('pokemon_product_variants')->where('en_short', $variant['en_short'])->exists()) {
                DB::table('pokemon_product_variants')->insert($variant);
            }
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
