<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

use App\Helpers\PokemonHelper;

class SaveExternalProducts extends Command
{
    protected $signature = 'products:save-external';
    protected $description = 'Save external products to the database';

    public function handle()
    {
        $shops = DB::table('external_shops')->get();
        $totalNewProducts = 0; // Track total new products across all shops

        foreach ($shops as $shop) {
            $baseUrl = $shop->base_url;
            $shopId = $shop->id;
            $page = 1;
            $perPage = 250; // Max limit
            $newProducts = 0;

            $this->info("Processing {$shop->name}...");

            // try to detect the type of product
            $possible_product_types = [
            'basketball', 'pokemon', 'yugioh', 'magic', 'one piece', 'disney lorcana', "weiss schwarz", "plushy", "psa 10", "mystery",
            'union arena', "accessory", "MTG", "dragon ball", "Postal Stamp", 'plüsch', 'Squishmallows', 'Weiß Schwarz', 'Card Case', 
            'Magnetic Holder','Card Holder','Battle Spirits','Build Divide','Funko Pop','Gundam','Panini','Naruto','Bandai','Yu-Gi-Oh',
            'Versandkosten', 'Penny Sleeves', 'Ultra Pro', 'Ultra-Pro', 'Ulta Pro','Star Wars','Acryl Case','PRO-BINDER','KEYCHAIN',
            'Dragon Shield', 'Store Card', 'Duskmourn'
            ];

            $continue_types = ["singles", "graded cards", "playmat", "binder", "sleeve", "plastic-model-kit", 'toploader'];

            $fails_array = [];
            do {
                // check if we have a json for this page and shop
                $shop_short = str_replace('.png', '', $shop->image);
                $json_dir = '/home/freakpants/pokedeals/backend/storage/shops/' . $shop_short;
                $json_file = storage_path('/shops/' . $shop_short . '/products_page_' . $page . '.json');
                if (file_exists($json_file)) {
                    $this->info("Using cached products for {$shop->name} (Page: $page).");
                    $products = json_decode(file_get_contents($json_file), true);

                } else {
                    try{
                        $this->info("Fetching products from {$shop->name} (Page: $page)...");
                        $response = Http::get("$baseUrl/products.json", [
                        'page' => $page,
                        'limit' => $perPage,
                    ]);
                    } catch (\Exception $e) {
                        $this->error("Failed to fetch products from {$shop->name} (Page: $page).");
                        // remember the details of the failed page, so we can retry after all other pages
                        // to fails array
                        $fails[] = [
                            'shop' => $shop,
                            'page' => $page
                        ];
                        break;
                    }
                    if ($response->failed()) {
                        $this->error("Failed to fetch products from {$shop->name} (Page: $page).");
                        break;
                    }
    
                    $products = $response->json()['products'] ?? [];
                    // save to json, create if not exists
                    // Check if the directory exists, if not, create it
                    if (!is_dir($json_dir)) {
                        mkdir($json_dir, 0755, true); // Create the directory with recursive flag
                    }
                    file_put_contents($json_file, json_encode($products));

                }

            
                if (empty($products)) {
                    $this->info("No more products found on Page $page.");
                    break;
                }

                foreach ($products as $product) {
                    $externalId = $product['id'] ?? null;
                    $title = $product['title'] ?? 'Unknown Title';

                    $product_type = 'unknown';
                    foreach ($possible_product_types as $type) {
                        if (stripos($title, $type) !== false) {
                            $product_type = $type;
                            break;
                        }
                    }

                    // continue if its not a unknown or pokemon product
                    if ($product_type !== 'pokemon' && $product_type !== 'unknown') {
                        // $this->warn("Skipping product with type: $product_type" . " - " . $title);
                        continue;
                    }

                    // also check the product type reported by the store
                    if (isset($product['product_type'])) {
                        $product_type = $product['product_type'];
                    }

                    // continue if the product type is singles or graded cards or playmat
                    if (in_array(strtolower($product_type), $continue_types)) {
                        // $this->warn("Skipping product with type: $product_type" . " - " . $title);
                        continue;
                    }

                    $url = "$baseUrl/products/{$product['handle']}";
                    $metadata = json_encode($product);

                    if (!$externalId) {
                        // $this->warn("Skipping product with missing ID: $title");
                        continue;
                    }

                    // if there are variants, also loop them and add the variant name to the title
                    if (count($product['variants']) > 0) {
                        foreach ($product['variants'] as $variant) {
                            $variant_title = $variant['title'];               
                            $variant_price = $variant['price'];

                             // if price is "0.00" or available is false, this variant doesnt have stock
                            if ($variant_price === "0.00" || $variant['available'] === false) {
                                $variant_stock = 0;
                            } else {
                                $variant_stock = 1;
                            }

                            $variant_id = $variant['id'];
                            // ?variant=49311955910942
                            $variant_url = "$url?variant=$variant_id";
                            $variant_metadata = json_encode($variant);

                            $details = PokemonHelper::determineProductDetails($title, $variant_title);

                            $set_identifier = $details['set_identifier'];
                            $product_type = $details['product_type'];
                            $language = $details['language'];

                    



                            // Insert or update the product - only include the variant title if it isnt "Default Title"
                            $inserted = DB::table('external_products')->updateOrInsert(
                                [
                                    'external_id' => $variant_id,
                                    'shop_id' => $shopId,
                                ],
                                [
                                    'title' => $title . ($variant_title !== 'Default Title' ? " - $variant_title" : ''),
                                    'price' => $variant_price,
                                    'stock' => $variant_stock,
                                    'url' => $variant_url,
                                    'type' => $product_type,
                                    'set_identifier' => $set_identifier,
                                    'language' => $language,
                                    'metadata' => $variant_metadata
                                ]
                            );

                            if ($inserted) {
                                $newProducts++;
                            }
                        }
                    }
                }

                $this->info("Processed Page $page with " . count($products) . " products.");
                $page++;

                // For subsequent runs, break after the first page if there are no new products
                /* if ($page === 2 && $newProducts === 0) {
                    $this->info("No new products found for {$shop->name}. Stopping.");
                    break;
                } */
            } while (!empty($products));

            $this->info("Finished processing {$shop->name}. Total new products added: $newProducts.");
            $totalNewProducts += $newProducts; // Add to the overall count
        }

        $this->info("All shops processed. Total new products added: $totalNewProducts.");
        return 0;
    }
}
