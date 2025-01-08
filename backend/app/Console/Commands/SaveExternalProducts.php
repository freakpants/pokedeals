<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\DomCrawler\Crawler;
use App\Enums\ProductTypes;

use App\Helpers\PokemonHelper;

class SaveExternalProducts extends Command
{
    protected $signature = 'products:save-external {--force-refresh}';
    protected $description = 'Save external products to the database';

    private $pokemonHelper;

    public function handle()
    {
        // $shops = DB::table('external_shops')->get(); 
        // get all shops sorted by last scraped at asc
        $shops = DB::table('external_shops')
            ->orderBy('last_scraped_at', 'asc')
            ->orderBy('id', 'asc')
            ->get();
        $totalNewProducts = 0; // Track total new products across all shops

        $this->pokemonHelper = new PokemonHelper();

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
        $supported_shops = ['shopify', 'websell', 'shopware', 'prestashop', 'spielezar', 'kidz', 'galaxy','wog','cs-cart','softridge','ecwid','woocommerce'];
        // output info and skip if the shop type is neither websell nor shopify
        if (!in_array($shop->shop_type, $supported_shops)) {
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

        $continue_types = ["singles", "graded cards", "playmat", "binder", "sleeve", "plastic-model-kit", 'toploader', 'sleeves','Strategiespiele', 'painting'];

        // check if we have a json for this page and shop
        $shop_short = str_replace('.png', '', $shop->image);
        $json_dir = '/home/freakpants/pokedeals/backend/storage/shops/' . $shop_short;
        $json_file = storage_path('/shops/' . $shop_short . '/products_page_' . $page . '.json');
        if (file_exists($json_file) && !$forceRefresh) {
            $products = json_decode(file_get_contents($json_file), true);
            // check if the external products table is empty
            $existingProduct = DB::table('external_products')
                ->first();

            // check on the first page of the shop, if there are any products we dont have yet
            if ($shop->shop_type === 'shopify' && !$existingProduct) {
                $page = $shop->previous_last_page;
                $this->info("Checking for new products on page $page...");
                // sleep for 1 second to not get rate limited
                sleep(1);
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
                        $product_type = $this->pokemonHelper->determineProductCategory($product);



                        // continue if its not a unknown or pokemon product, and not something like
                        if (($product_type === 'pokemon' || $product_type === 'unknown') &&
                        !in_array(strtolower($product_type), $continue_types)
                        ) {
                            $newProducts++;
                        } else {
                            // also check against the specific pokemon product type
                            $details = $this->pokemonHelper->determineProductDetails($product_type);
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
        if((!file_exists($json_file) || $newProducts) || $forceRefresh){
            // update the shop's last scraped at timestamp
            DB::table('external_shops')
                ->where('id', $shopId)
                ->update([
                    'last_scraped_at' => now(),
                ]);
            if($shop->shop_type === 'woocommerce'){
                $categoryUrls = json_decode($shop->category_urls);
                $this->info("Starting HTML parsing for WooCommerce...");

                foreach ($categoryUrls as $categoryUrl) {
                    $page = 1;
                    $hasMorePages = true;
                    while ($hasMorePages) {
                        $paginatedUrl = $categoryUrl . 'page/' . $page;
                        $this->info("Fetching page $page: $paginatedUrl");

                        $response = Http::get($paginatedUrl);

                        if ($response->failed()) {
                            $this->error("Failed to fetch page $page. Status: {$response->status()}");
                            break;
                        }

                        $html = $response->body();

                        // Parse HTML to extract data
                        $crawler = new Crawler($html);

                        // find all product-box elements
                        $current_products = $crawler->filter('.product')->each(function (Crawler $node) use ($shop) {
                            $product = [];
                            // $product['id'] = $node->attr('data-product_id');
                            // id data-product_id attribute of the <a> element with the class button product_type_simple add_to_cart_button ajax_add_to_cart
                            try {
                                $product['id'] = $node->filter('.button.product_type_simple.add_to_cart_button.ajax_add_to_cart')->attr('data-product_id');
                            } catch (\Exception $e) {
                                $product['id'] = null;
                            }

                            // Find the price element, the last one with the specified class
                            $priceElement = $node->filter('.woocommerce-Price-currencySymbol')->last();

                            // Ensure the element exists
                            if ($priceElement->count() > 0) {
                                // Get the text of the parent node of the price element
                                $price = $priceElement->ancestors()->first()->text();
                                info("Price: " . $price);
                            } else {
                                info("Price element not found.");
                            }


                            // replace everything that is not a number or a dot
                            $price = preg_replace('/[^0-9.]/', '', $price);
                            $product['price'] = floatval($price);
                            // find the product-title element
                            $product['title'] = $node->filter('.product-title')->text();

                            // find the first href element
                            $product['url'] = $node->filter('a')->first()->attr('href');
                            // replace the base url
                            $product['handle'] = str_replace($shop->base_url, '', $product['url']);
                            $product['available'] = true;

                            $product['variants'] = [$product];

                            return $product;
                        });

                        // merge the arrays
                        $products = array_merge($products, $current_products);

                        // Check if a link with the class "next page-numbers" exists
                        $hasMorePages = $crawler->filter('.next.page-numbers')->count() > 0;
                        $page++;
                    }
                }
            }
            else if($shop->shop_type === 'ecwid'){
                $categoryUrls = json_decode($shop->category_urls);
                $this->info("Starting parsing for Ecwid...");

                $products = [];
                foreach ($categoryUrls as $categoryUrl) {
                    $offset = 0;
                    $hasMorePages = true;

                    while($hasMorePages){
                        $paginatedUrl = $categoryUrl;
                        if($offset){
                            $paginatedUrl .= '&offset=' . $offset;
                        }
                        $this->info("Fetching page $page: $paginatedUrl");

                        $response = Http::get($paginatedUrl);

                        if ($response->failed()) {
                            $this->error("Failed to fetch page $page. Status: {$response->status()}");
                            break;
                        }

                        // crawl the html
                        $html = $response->body();

                        // Parse HTML to extract data
                        $crawler = new Crawler($html);

                        // find all grid-product elements
                        $current_products = $crawler->filter('.grid-product')->each(function (Crawler $node) use ($shop) {
                            $product = [];
                            
                            // Extract product ID from the `data-product-id` attribute
                            $product['id'] = $node->filter('.grid-product__wrap')->attr('data-product-id');
                            
                            // Extract and clean the product price
                            try {
                                $price = $node->filter('.grid-product__price-value')->text('');
                                $price = preg_replace('/[^0-9.]/', '', $price); // Clean price to retain numbers and dot
                                $product['price'] = floatval($price);
                            } catch (\Exception $e) {
                                $product['price'] = null; // Handle missing price
                            }
                            
                            // Extract the product title
                            try {
                                $product['title'] = $node->filter('.grid-product__title-inner')->text('');
                            } catch (\Exception $e) {
                                $product['title'] = null; // Handle missing title
                            }
                            
                            // Extract the product URL and handle
                            try {
                                $product['url'] = $node->filter('a')->first()->attr('href');
                                $product['handle'] = str_replace($shop->base_url, '', $product['url']);
                            } catch (\Exception $e) {
                                $product['url'] = null;
                                $product['handle'] = null;
                            }
                            
                            // Detect sold-out status
                            try {
                                // there is an element: grid-product__button-hover grid-product__buy-now with a span that says Ausverkauft
                                $product['available'] = !$node->filter('.grid-product__button-hover.grid-product__buy-now span:contains("Ausverkauft")')->count();

                            } catch (\Exception $e) {
                                $product['available'] = false; // Assume unavailable if the hover button is not found
                            }
                        
                            // Variants (example structure, modify if needed)
                            $product['variants'] = [$product];
                            
                            return $product;
                        });

                        // check if there is an element with the class 'pager__button-text' and the text Nächste
                        $hasMorePages = $crawler->filter('.pager__button-text')->each(function (Crawler $node) {
                            return $node->text() === 'Nächste';
                        });

                        // merge the arrays
                        $products = array_merge($products, $current_products);

                        $offset += 60;
                    }

                }

            }
            else if($shop->shop_type === 'softridge'){
                $categoryUrls = json_decode($shop->category_urls);
                $this->info("Starting parsing for Softridge...");

                $products = [];
                foreach ($categoryUrls as $categoryUrl) {
                    $page = 1;
                    $hasMorePages = true;

                    
                    while ($hasMorePages) {
                        $paginatedUrl = $categoryUrl . '&page=' . $page;
                        $this->info("Fetching page $page: $paginatedUrl");

                        $response = Http::get($paginatedUrl);

                        if ($response->failed()) {
                            $this->error("Failed to fetch page $page. Status: {$response->status()}");
                            break;
                        }

                        $json = $response->json();

                        foreach($json['products'] as $product){
                            $productArray = [];
                            $productArray['id'] = $product['id'];
                            $productArray['price'] = $product['salesPriceText'];

                            // replace .– in price
                            $productArray['price'] = str_replace('.–', '', $productArray['price']);

                            $productArray['title'] = $product['fullTitle'] . ' ' . $product['regionCode'];
                            $productArray['handle'] = $product['linkUrl'];


                            $productArray['url'] =  $shop->base_url . $productArray['handle'];
                            $productArray['available'] = $product['statusColor'] !== 'Gray';
                            
                            $productArray['variants'] = [$productArray];

                            $products[] = $productArray;
                        }

                        $hasMorePages = $json['hasMore']; 
                        $page++;
                        
                    }
                }
            }    
            else if($shop->shop_type === 'cs-cart'){
                $categoryUrls = json_decode($shop->category_urls);
                $this->info("Starting HTML parsing for CS-Cart...");

                $products = [];
                foreach ($categoryUrls as $categoryUrl) {
                    // do the request
                    $response = Http::get($categoryUrl);
                    if ($response->failed()) {
                        $this->error("Failed to fetch page $page. Status: {$response->status()}");
                        break;
                    }
                    // create a crawler
                    $crawler = new Crawler($response->json()['html']['pagination_contents']);

                    // find the class products_grid
                    $products = $crawler->filter('.ut2-gl__item')->each(function (Crawler $node) use ($shop) {
                        $product = [];
                        
                        // Extract product ID
                        $product['id'] = $node->filter('input[name^="product_data"]')->count() 
                            ? $node->filter('input[name^="product_data"]')->attr('value') 
                            : null;
                        
                        // Extract price
                        $priceNode = $node->filter('.ty-price');
                        if ($priceNode->count()) {
                            $priceText = $priceNode->text(); // Full text, e.g., "CHF 7.90"
                            $product['price'] = trim(str_replace('CHF', '', $priceText)); // Remove "CHF" to leave just the number
                        } else {
                            $product['price'] = null;
                        }
                        
                        // Extract title
                        $product['title'] = $node->filter('.product-title')->count() 
                            ? trim($node->filter('.product-title')->text()) 
                            : null;
                        
                        // Extract URL
                        $product['url'] = $node->filter('.product-title')->count() 
                            ? $node->filter('.product-title')->attr('href') 
                            : null;
                        
                        // Check availability
                        $product['available'] = !$node->filter('.ty-qty-out-of-stock')->count(); // If "Out of stock" is not found, it's available
                        
                        // Generate handle
                        $product['handle'] = $product['url'] 
                            ? str_replace($shop->base_url, '', $product['url']) 
                            : null;
                    
                        // Variants placeholder
                        $product['variants'] = [$product];
                    
                        return $product;
                    });
                }


            }
            else if($shop->shop_type === 'wog'){
                // wog
                $response = Http::withHeaders([
                    'Accept' => '*/*',
                    'Content-Type' => 'multipart/form-data',
                ])->asForm()->post('https://www.wog.ch/index.cfm/ajax.productList', [
                    'type' => 'Toys',
                    'developerID' => '7688',
                    'productTypeID' => '3',
                    'productFormTypeName' => '',
                    'displayTypeID' => '3',
                    'listType' => 'developers',
                    'maxRows' => '48',
                    'page' => '1',
                    'forceTileView' => 'false',
                ]);
                
                if ($response->successful()) {
                    $data = $response->json();
                    // Handle your response data
                    // loop all products
                    $products = [];
                    foreach($data['products'] as $product){
                        $productArray = [];
                        $productArray['id'] = $product['productID'];
                        $productArray['price'] = $product['unitPrice'];
                        $productArray['title'] = $product['fullTitle'];
                        $productArray['url'] = $product['linkTo'];
                        $productArray['available'] = $product['deliveryDetail'] !== 'crossIcon';
                        $productArray['handle'] = str_replace($shop->base_url, '', $productArray['url']);
                        $productArray['variants'] = [$productArray];

                        $products[] = $productArray;
                    }
                } else {
                    // Handle errors
                    dd($response->status(), $response->body());
                }
            }
            else if($shop->shop_type === 'galaxy'){
                // galaxy
                $categoryUrls = json_decode($shop->category_urls);
                $this->info("Starting HTML parsing for Galaxy...");

                foreach ($categoryUrls as $categoryUrl) {
                    $page = 1;
                    $hasMorePages = true;
                    while ($hasMorePages) {
                        $paginatedUrl = $categoryUrl . '&page=' . $page;
                        $this->info("Fetching page $page: $paginatedUrl");

                        $response = Http::get($paginatedUrl);

                        if ($response->failed()) {
                            $this->error("Failed to fetch page $page. Status: {$response->status()}");
                            break;
                        }

                        $html = $response->body();

                        // Parse HTML to extract data
                        $crawler = new Crawler($html);

                        // find all product-box elements
                        $current_products = $crawler->filter('.product-block')->each(function (Crawler $node) use ($shop) {
                            $product = [];
                            $product['id'] = $node->attr('data-product-id');
                            // find the product-price element
                            if($node->filter('.price')->count()){
                                $price = $node->filter('.price')->text();
                            } else {
                                // skip this product if there is no price
                                return;
                            }
                            $price = $node->filter('.amount.theme-money')->text();
                            // replace everything that is not a number or a dot
                            $price = preg_replace('/[^0-9.]/', '', $price);
                            $product['price'] = floatval($price);
                            // find the product-title element
                            $product['title'] = $node->filter('.title')->text();

                            // find the first href element
                            $product['url'] = $node->filter('a')->first()->attr('href');
                            // replace the base url
                            $product['handle'] = str_replace($shop->base_url, '', $product['url']);
                            $product['available'] = true;

                            $product['variants'] = [$product];

                            return $product;
                        });


                        // remove all null values
                        $current_products = array_filter($current_products);

                        // merge the arrays
                        $products = array_merge($products, $current_products);

                        // Check if a "next page" button exists
                        $hasMorePages = $crawler->filter('.linkless.next')->count() < 1;
                        $page++;
                    }
                }
            }
            else if($shop->shop_type === 'kidz'){
                $categoryUrls = json_decode($shop->category_urls);
                $this->info("Starting HTML parsing for Kidz...");

                foreach ($categoryUrls as $categoryUrl) {
                    $page = 1;
                    $hasMorePages = true;
                    while ($hasMorePages) {
                        $paginatedUrl = $categoryUrl . '&page=' . $page;
                        $this->info("Fetching page $page: $paginatedUrl");

                        $response = Http::get($paginatedUrl);

                        if ($response->failed()) {
                            $this->error("Failed to fetch page $page. Status: {$response->status()}");
                            break;
                        }

                        $html = $response->body();

                        // Parse HTML to extract data
                        $crawler = new Crawler($html);

                        // find all product-box elements
                        $current_products = $crawler->filter('.card--card')->each(function (Crawler $node) use ($shop) {
                            $product = [];
                            // select h3's with card__heading
                            $element = $node->filter('.card__heading.h5')->first();
                            $product['title'] = $element->text();
                            // the id are the last numbers inside the id of the the element
                            $product['id'] = preg_replace('/.*?(\d+)$/', '$1', $element->attr('id'));
                            // find the product-price element
                            // its the last one if there are multiple
                            $price = $node->filter('.price-item--sale.price-item--last')->text();
                            // replace everything that is not a number or a dot
                            $price = preg_replace('/[^0-9.]/', '', $price);
                            $product['price'] = floatval($price);

                            // find the first href element
                            $product['url'] = $node->filter('a')->first()->attr('href');
                            // replace the base url
                            $product['handle'] = str_replace($shop->base_url, '', $product['url']);
                            $product['available'] = true;

                            $product['variants'] = [$product];

                            return $product;
                        });

                        // merge the arrays
                        $products = array_merge($products, $current_products);

                        // Check if a "next page" button exists
                        $hasMorePages = $crawler->filter('.pagination__item--prev')->count() > 0;
                        $page++;
                    }
                }
            }
            else if($shop->shop_type === 'spielezar'){
                $categoryUrls = json_decode($shop->category_urls);
                $this->info("Starting HTML parsing for Spielezar...");

                foreach ($categoryUrls as $categoryUrl) {
                    $offset = 0;
                    $hasMorePages = true;
                    while ($hasMorePages) {
                        // extract the filter from the url
                        $explodedUrl = explode('?', $categoryUrl);
                        $filter = $explodedUrl[1] ?? '';
                        $paginatedUrl = $explodedUrl[0] . '?offset=' . $offset . '&' . $filter;
                        $this->info("Fetching page $page: $paginatedUrl");

                        $response = Http::get($paginatedUrl);

                        if ($response->failed()) {
                            $this->error("Failed to fetch page $page. Status: {$response->status()}");
                            break;
                        }

                        $html = $response->body();

                        // Parse HTML to extract data
                        $crawler = new Crawler($html);

                        // find all product-box elements
                        $current_products = $crawler->filter('.ajax_block_product')->each(function (Crawler $node) use ($shop) {
                            $product = [];
                            $product['id'] = $node->attr('data-id-product');
                            // find the product-price element
                            $price = $node->filter('.price_default')->text();
                            // replace comma with a dot
                            $price = str_replace(',', '.', $price);
                            // replace everything that is not a number or a dot
                            $price = preg_replace('/[^0-9.]/', '', $price);
                            $product['price'] = floatval($price);
                            // find the h3
                            $product['title'] = $node->filter('h3')->text();

                            // find the first href element
                            $product['url'] = $node->filter('a')->first()->attr('href');
                            // replace the base url
                            $product['handle'] = str_replace($shop->base_url, '', $product['url']);
                            $product['available'] = true;

                            $product['variants'] = [$product];

                            return $product;
                        });

                        // merge the arrays
                        $products = array_merge($products, $current_products);

                        // Check if a "next page" button exists
                        $hasMorePages = $crawler->filter('#show_more')->count() > 0;
                        $offset += 30;

                    }
                }

            }
            else if($shop->shop_type === 'prestashop'){
                $categoryUrls = json_decode($shop->category_urls);
                $this->info("Starting HTML parsing for PrestaShop...");

                foreach ($categoryUrls as $categoryUrl) {
                    $page = 1;
                    $hasMorePages = true;
                    while ($hasMorePages) {
                        // wait for 1 second between each page
                        sleep(1);
                        $isMana = $shop->name === 'The Mana Shop';
                        if($isMana){
                            $paginatedUrl = $categoryUrl . '&n=1000';
                        } else {
                            $paginatedUrl = $categoryUrl . '?page=' . $page;
                        }
                        
                        $this->info("Fetching page $page: $paginatedUrl");

                        $response = Http::get($paginatedUrl);

                        if ($response->failed()) {
                            $this->error("Failed to fetch page $page. Status: {$response->status()}");
                            break;
                        }

                        $html = $response->body();

                        // Parse HTML to extract data
                        $crawler = new Crawler($html);


                        if($isMana){
                            $productClass = '.product-container';
                        } else {
                            $productClass = '.product-miniature';
                        }

                        // find all product-box elements
                        $current_products = $crawler->filter($productClass)->each(function (Crawler $node) use ($shop, $isMana) {
                            // $product = json_decode($node->attr('data-product')); // Extract the data-product attribute
                            // turn it into an array
                            $product = [];

                            $product['id'] = $node->attr('data-id-product');

                            if(!$product['id']){
                                $product['id'] = $node->filter('.addToWishlist')->attr('rel');
                            }

                            // find the product-price element
                            $priceNode = $node->filter('.product-price-and-shipping .price');

                            // check if the price node is empty
                            if(!$priceNode->count()){
                                $priceNode = $node->filter('.product-price.price');
                            }

                            $price = $priceNode->text();

                            // replace everything that is not a number or a dot
                            $price = preg_replace('/[^0-9.]/', '', $price);
                            $product['price'] = floatval($price);
                            // find the product-title element
                            $productTitleNode = $node->filter('.product-title');
                            if(!$productTitleNode->count()){
                                $productTitleNode = $node->filter('.product-name');
                            }

                            if($isMana){
                                $product['title'] = $productTitleNode->attr('title');
                            } else {
                                $product['title'] = $productTitleNode->text();
                            }
                            

                            // find the first href element
                            $product['url'] = $node->filter('a')->first()->attr('href');
                            // replace the base url

                            $product['handle'] = 
                            str_replace($shop->base_url, '', str_replace('www.', '', $product['url']));

                            // if there is a label-danger or label-warning, the product is out of stock
                            if($node->filter('.label-danger')->count() || $node->filter('.label-warning')->count() ||
                                $node->filter('.product-unavailable')->count()){
                                $product['available'] = false;
                            } else {
                                $product['available'] = true;
                            }

                            $product['variants'] = [$product];

                            return $product;
                        });

                        // merge the arrays
                        $products = array_merge($products, $current_products);

                        $hasMorePages = $crawler->filter('.next')->count() > 0 && !$isMana;
                        $page++;
                    }
                }

            }
            else if($shop->shop_type === 'shopware'){
                $categoryUrls = json_decode($shop->category_urls);
                $this->info("Starting HTML parsing for Shopware...");

                foreach ($categoryUrls as $categoryUrl) {
                    $page = 1;
                    $hasMorePages = true;
                    while ($hasMorePages) {
                        $paginatedUrl = $categoryUrl . '?p=' . $page;
                        $this->info("Fetching page $page: $paginatedUrl");

                        $response = Http::get($paginatedUrl);

                        if ($response->failed()) {
                            $this->error("Failed to fetch page $page. Status: {$response->status()}");
                            break;
                        }

                        $html = $response->body();

                        // Parse HTML to extract data
                        $crawler = new Crawler($html);

                        // find all product-box elements
                        $current_products = $crawler->filter('.product-box')->each(function (Crawler $node) use ($shop) {
                            $product = json_decode($node->attr('data-product-information')); 
                            // turn it into an array
                            $product = (array) $product;
                            // find the product-price element
                            $price = $node->filter('.product-price')->text();
                            // replace everything that is not a number or a dot
                            $price = preg_replace('/[^0-9.]/', '', $price);
                            $product['price'] = floatval($price);
                            // find the product-variant-characteristics-text element
                            $variant = $node->filter('.product-variant-characteristics-text')->text();
                            if($product['name'] === $variant){
                                $product['title'] = $product['name'];
                            } else {
                                $product['title'] = $product['name'] . ' - ' . $variant;
                            }

                            // find the first href element
                            $product['url'] = $node->filter('a')->first()->attr('href');
                            // replace the base url
                            $product['handle'] = str_replace($shop->base_url, '', $product['url']);
                            $product['available'] = true;

                            $product['variants'] = [$product];

                            return $product;
                        });

                        // merge the arrays
                        $products = array_merge($products, $current_products);

                        


                        // Check if a "next page" button exists
                        $hasMorePages = $crawler->filter('.page-item.page-next.disabled')->count() < 1;
                        $page++;
                    }
                }
                
            }
            else if ($shop->shop_type === 'websell') {
                $productInfoUrl = $baseUrl  . '/store/ajax/productinfo.nsc';
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
        
                        $out_of_stock_codes = [];

                        $codes = $crawler->filter('article.product-card')->each(function (Crawler $node) use (&$out_of_stock_codes) {
                            // check if there is a out-of-stock class on this element
                            // get the classname of the element
                            $class = $node->attr('class');
                            // check if the string contains out-of-stock
                            if(strpos($class, 'out-of-stock') !== false){
                                // if it does, add the data-sku to the out of stock array
                                $out_of_stock_codes[] = $node->attr('data-sku');
                            }
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
                    // if the product is in the out of stock array, set stock to 0
                    if(in_array($product['id'], $out_of_stock_codes)){
                        $product['stock'] = 0;
                    } else {
                        $product['stock'] = 1;
                    }
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

            // try to get product type from body_html
            if(isset($product['body_html'])){
                $crawler = new Crawler($product['body_html']);

                // when title contains tiny towns, debug
                if(strpos($title, 'Bluebird') !== false){
                    // $this->info("Tiny Towns found");
                }
    
                // Define the list of product types to check
                $productTypes = ['Brettspiel', 'Strategiespiel', 'Kartenspiel', 'Familienspiel', 'Gesellschaftsspiel', 'Puzzle',
                    'Fantasiespiel', 'Kerze', 'Rollenspiel', 'Spielezubehör', 'Würfelspiel','Reaktionsspiel', 'Bretsspiel',
                    'Geschicklichkeitsspiel', 'Spielematte', 'Lernspiel', 'Stategiespiel','Partyspiel', 'Abenteuerspiel','Figurenset',
                    'Spielfigur', 'Farbpalette', 'Spieleaccessoire','Kerze', 'Bücher', 'Puzzle', 'Holzpuzzle', 'Holzspielerei',
                    'Taschenbuch', 'Wandbild'];
            
                foreach ($productTypes as $type) {
                    // Use regex to match the product type in the HTML
                    if (preg_match('/<td[^>]*>.*?' . preg_quote($type, '/') . '.*?<\/td>/is', $product['body_html'])) {
                        $product['product_type'] = $type;
                        break;
                    }
                }
            }
            
            

            $product_type = $this->pokemonHelper->determineProductCategory($product);

            // continue if its not a unknown or pokemon product
            if ($product_type !== 'pokemon' && $product_type !== 'unknown' && $product_type !== 'Sammelkarten'
            && $product_type !== ''
            ) {
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
                $url = $shop->base_url . '/store/product/' . $product['item_id'];
            } else if($shop->shop_type === 'shopware'){
                $url = $shop->base_url . '/' . $product['handle'];
            } else if($shop->shop_type === 'prestashop'){
                $url = $shop->base_url . $product['handle'];
            } else if($shop->shop_type === 'wog'){
                $url = $product['url'];
            }
            else if($shop->shop_type === 'shopify'){
                $url = "{$baseUrl}/products/{$product['handle']}";
            } else {
                $url = $product['url'];
            }

            if (!$externalId) {
                // $this->warn("Skipping product with missing ID: $title");
                continue;
            }

            $original_title = $title;
            // if there are variants, also loop them and add the variant name to the title
            if (count($product['variants']) > 0) {
                foreach ($product['variants'] as $variant) {
                    $title = $original_title;
                    $variant_title = $variant['title'];               
                    $variant_price = $variant['price'];


                    $variant_stock = $variant['stock'] ?? 1;
                    // if price is "0.00" or available is false, this variant doesnt have stock
                    if ($variant_price === "0.00" || $variant['available'] === false) {
                        $variant_stock = 0;
                    }

                    $variant_id = $variant['id'];
                    // ?variant=49311955910942
                    
                    if($shop->shop_type === 'shopify'){
                        $variant_url = "$url?variant=$variant_id";
                    } else {
                        $variant_url = $url;
                    }

                    
                    $variant_metadata = json_encode($variant);

                    $details = PokemonHelper::determineProductDetails($title, $variant_title);

                    $set_identifier = $details['set_identifier'];
                    $product_type = $details['product_type'];
                    $language = $details['language'];
                    $variant = $details['variant'];
                    $multiplier = $details['multiplier'];

                    if($title !== $variant_title && $variant_title !== 'Default Title'){
                        $title = $title . ' - ' . $variant_title;
                    }

                    // when the shop is manaShop, set language to english if it isnt set yet
                    if($shop->name === 'The Mana Shop' && !$language){
                        $language = 'en';
                    }

                    // Insert or update the product - only include the variant title if it isnt "Default Title"
                    $inserted = DB::table('external_products')->updateOrInsert(
                        [
                            'external_id' =>  $variant_id,
                            'shop_id' => $shopId,
                        ],
                        [
                            'title' => $title,
                            'price' => $variant_price,
                            'stock' => $variant_stock,
                            'url' => $variant_url,
                            'multiplier' => $multiplier,
                            'type' => $product_type,
                            'set_identifier' => $set_identifier,
                            'variant' => $variant,
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