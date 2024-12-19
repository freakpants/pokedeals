<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\Console\Input\InputOption;
use App\Enums\ProductTypes;

use App\Helpers\PokemonHelper;

class SaveExternalProducts extends Command
{
    protected $signature = 'products:save-external {--force-refresh}';
    protected $description = 'Save external products to the database';

    public function handle()
    {
        $shops = DB::table('external_shops')->get(); 
        $totalNewProducts = 0; // Track total new products across all shops

        $forceRefresh = $this->option('force-refresh') ?? false;
        if($forceRefresh){
            $this->info("Force refresh enabled. All shops will be queried for new products...");

        } else {
            $this->info("Using cache for this run.");
        }

        foreach ($shops as $shop) {
            $totalNewProducts = $this->processShop($shop, $totalNewProducts);
        }

        $this->info("All shops processed. Total new products added: $totalNewProducts.");
            
    }

    private function processShop($shop, $totalNewProducts)
    {
        // output info and skip if the shop type is neither websell nor shopify
        if ($shop->shop_type !== 'websell' && $shop->shop_type !== 'shopify') {
            $this->warn("Skipping shop {$shop->name} with unsupported type: {$shop->shop_type}");
            return $totalNewProducts;
        }

        $baseUrl = $shop->base_url;
        $shopId = $shop->id;
        $page = 1;
        $perPage = 250; // Max limit
        $newProducts = 0;

        $forceRefresh = $this->option('force-refresh') ?? false;

        $products = [];

        $continue_types = ["singles", "graded cards", "playmat", "binder", "sleeve", "plastic-model-kit", 'toploader', 'sleeves'];

        // check if we have a json for this page and shop
        $shop_short = str_replace('.png', '', $shop->image);
        $json_dir = '/home/freakpants/pokedeals/backend/storage/shops/' . $shop_short;
        $json_file = storage_path('/shops/' . $shop_short . '/products_page_' . $page . '.json');
        if (file_exists($json_file) && !$forceRefresh) {
            $products = json_decode(file_get_contents($json_file), true);
            // check on the first page of the shop, if there are any products we dont have yet
            if ($shop->shop_type === 'shopify') {
                $page = $shop->previous_last_page;
                $response = Http::get("$baseUrl/products.json", [
                    'page' => $page,
                    'limit' => $perPage,
                ]);
                $newProductsArray = $response->json()['products'] ?? [];
                $newProducts = 0;
                foreach ($newProductsArray as $product) {
                    $externalId = $product['id'] ?? null;

                    // if there are variants, use that id
                    if (count($product['variants']) > 0) {
                        $externalId = $product['variants'][0]['id'];
                    }

                    $title = $product['title'] ?? 'Unknown Title';
                    $existingProduct = DB::table('external_products')
                        ->where('external_id', $externalId)
                        ->where('shop_id', $shopId)
                        ->first();
                    if (!$existingProduct) {
                        // determine if this would be saved
                        $product_type = PokemonHelper::determineProductCategory($product);



                        // continue if its not a unknown or pokemon product, and not something like
                        if (($product_type === 'pokemon' || $product_type === 'unknown') &&
                        !in_array(strtolower($product_type), $continue_types)
                        ) {
                            $newProducts++;
                        } else {
                            // also check against the specific pokemon product type
                            $details = PokemonHelper::determineProductDetails($product_type);
                            if($details['product_type'] !== ProductTypes::Other){
                                $newProducts++;
                            }
                        }
                    }
                }
                
                if(!$newProducts){
                    $this->info("No new products found on page $page.");
                    $this->info("Using cached products for {$shop->name} (Page: $page).");
                } else {
                    $this->info("Found $newProducts new products on page $page.");
                }
            }
        } else {
            // nothing is cached, so we need to fetch the products
            $this->info("No cached products found for {$shop->name} (Page: $page).");
        } 
        
        // if there are new products, run the rest of the process
        if((!file_exists($json_file) || $newProducts) && $forceRefresh){
            if ($shop->shop_type === 'websell') {
                $productInfoUrl = $baseUrl  . 'store/ajax/productinfo.nsc';
                $categoryUrls = json_decode($shop->category_urls);
    
                $allCodes = [];
    
                $this->info("Starting HTML parsing for product codes...");
    
                foreach($categoryUrls as $categoryUrl){
                    $page = 1;
                    $hasMorePages = true;
                    while ($hasMorePages) {
                        $paginatedUrl = $categoryUrl . '?page=' . $page;
                        $this->info("Fetching page $page: $paginatedUrl");
        
                        $response = Http::get($paginatedUrl);
        
                        if ($response->failed()) {
                            $this->error("Failed to fetch page $page. Status: {$response->status()}");
                            break;
                        }
        
                        $html = $response->body();
        
                        // Parse HTML to extract product codes
                        $crawler = new Crawler($html);
        
        
                        $codes = $crawler->filter('article.product-card')->each(function (Crawler $node) {
                            return $node->attr('data-sku'); // Extract the data-sku attribute
                        });
                            
        
                        if (!empty($codes)) {
                            $allCodes = array_merge($allCodes, $codes);
                            $this->info("Extracted " . count($codes) . " codes from page $page.");
                        } else {
                            $this->warn("No codes found on page $page.");
                        }
        
                        // Check if a "next page" button exists
                        $hasMorePages = $crawler->filter('.btn.btn-default.next')->count() > 0;
                        $page++;
                    }
                }
    
                $this->info("Total product codes extracted: " . count($allCodes));
    
                // Fetch product details for extracted codes
                $products = $this->fetchWebSellProductDetails($allCodes, $productInfoUrl);
                foreach($products as &$product){
                    // create a variant for each product
                    $product['id'] = $product['item_id'] ?? null;
                    $product['title'] = $product['item_name'] ?? 'Unknown Title';
                    $product['price'] = $product['price'];
                    $product['url'] = $shop->base_url;
                    $product['stock'] = 1;
                    $product['variants'] = [
                        [
                            'id' => $product['id'],
                            'title' => $product['title'],
                            'price' => $product['price'],
                            'url' => $product['url'],
                            'stock' => $product['stock'],
                            'available' => true,
                        ]
                    ];
                }
            } else {
                $products = [];
                do {
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

                    $newProductsArray = $response->json()['products'] ?? [];
        
                    $products = array_merge($products, $newProductsArray);
                    $this->info("Processed Page $page with " . count($newProductsArray) . " products.");
                    
                    if (empty($newProductsArray)) {
                        $this->info("No more products found on Page $page.");
                        break;
                    }
                    $page++;

                } while (!empty($newProductsArray));
            }
        }


        foreach ($products as $product) {
            $externalId = $product['id'] ?? null;
            $title = $product['title'] ?? 'Unknown Title';

            // Check if the product already exists in the database
            $existingProduct = DB::table('external_products')
                ->where('external_id', $externalId)
                ->where('shop_id', $shopId)
                ->first();

            if ($existingProduct) {
                $this->info("Product already exists: $title (ID: $externalId)");
                continue;
            }

            $product_type = PokemonHelper::determineProductCategory($product);

            // continue if its not a unknown or pokemon product
            if ($product_type !== 'pokemon' && $product_type !== 'unknown') {
                // check against the specific pokemon product types
                $details = PokemonHelper::determineProductDetails($product_type);
                if($details['product_type'] === ProductTypes::Other){
                    // cant determine product type this way either - skip
                    continue;
                }
            }

            // continue if the product type is singles or graded cards or playmat
            if (in_array(strtolower($product_type), $continue_types)) {
                continue;
            }



            if($shop->shop_type === 'websell'){
                $url = $shop->base_url . 'store/product/' . $product['item_id'];
            } else {
                $url = "$baseUrl/products/{$product['handle']}";
            }

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
                    $multiplier = $details['multiplier'];

                    // Insert or update the product - only include the variant title if it isnt "Default Title"
                    $inserted = DB::table('external_products')->updateOrInsert(
                        [
                            'external_id' =>  $variant_id,
                            'shop_id' => $shopId,
                        ],
                        [
                            'title' => $title . ($variant_title !== 'Default Title' ? " - $variant_title" : ''),
                            'price' => $variant_price,
                            'stock' => $variant_stock,
                            'url' => $variant_url,
                            'multiplier' => $multiplier,
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

        
        // save to json, create if not exists
        // Check if the directory exists, if not, create it
        if (!is_dir($json_dir)) {
            mkdir($json_dir, 0755, true); // Create the directory with recursive flag
        }
        file_put_contents($json_file, json_encode($products));

        $this->info("Processing {$shop->name}...");


        $this->info("Finished processing {$shop->name}. Total new products added: $newProducts.");
        $totalNewProducts += $newProducts; // Add to the overall count


        return $totalNewProducts;
    }

    /**
     * Fetch product details from productinfo API using extracted codes.
     */
    private function fetchWebSellProductDetails(array $codes, string $url)
    {
        $allProducts = [];
        foreach (array_chunk($codes, 20) as $codeChunk) {
            $this->info("Fetching product info for codes: " . implode(', ', $codeChunk));

            $response = Http::asForm()->post($url, [
                'codes' => $codeChunk,
            ]);

            if ($response->successful()) {
                $data = $response->json();
                $allProducts = array_merge($allProducts, $data['items'] ?? []);
            } else {
                $this->error("Failed to fetch product info for codes. Status: {$response->status()}");
            }
        }

        // Save product data
        Storage::disk('local')->put('kabooom_products.json', json_encode($allProducts, JSON_PRETTY_PRINT));
        $this->info("All product data saved to storage/app/kabooom_products.json.");

        return $allProducts;
    }

}