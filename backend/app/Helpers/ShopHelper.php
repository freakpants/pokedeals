<?php

namespace App\Helpers;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\DB;
use Symfony\Component\DomCrawler\Crawler;

class ShopHelper{

    protected $command, $pokemonHelper;

    public function __construct($command)
    {
        $this->command = $command;
        $this->pokemonHelper = new PokemonHelper(); 
    }

    public function retrieveProductsFromShop($shop)
{
    switch ($shop->shop_type) {
        case 'wog':
            $products = $this->retrieveProductsFromWog($shop);
            break;
        case 'interdiscount':
            $products = $this->retrieveProductsFromInterdiscount($shop);
            break;
        case 'woocommerce':
            $products = $this->retrieveProductsFromWooCommerce($shop);
            break;
        case 'shopify':
            $products = $this->retrieveProductsFromShopify($shop);
            break;
        case 'spielezar':
            $products = $this->retrieveProductsFromSpielezar($shop);
            break;
        case 'websell':
            $products = $this->retrieveProductsFromWebsell($shop);
            break;
        case 'prestashop':
            $products = $this->retrieveProductsFromPrestaShop($shop);
            break;
        case 'ecwid':
            $products = $this->retrieveProductsFromEcwid($shop);
            break;
        case 'softridge':
             $products = $this->retrieveProductsFromSoftridge($shop);
            break;
        case 'cs-cart':
            $products = $this->retrieveProductsFromCsCart($shop);
            break; 
        case 'kidz':
            $products = $this->retrieveProductsFromKidz($shop);
            break;
        case 'galaxy':
            $products = $this->retrieveProductsFromGalaxy($shop);
            break;
        case 'shopware':
            return $this->retrieveProductsFromShopware($shop);
        default:
            $products = [];
    }


    

    $filteredProducts = [];
    $discardedCount = 0;

    foreach ($products as $product) {
        $category = PokemonHelper::determineProductCategory($product);

        $details = $this->pokemonHelper->determineProductDetails($product['title'], $product['variants'][0]['title'] ?? null);
$productType = $details['product_type'];
$setIdentifier = $details['set_identifier'];
$language = $details['language'];
$variant = $details['variant'];

if ($details['product_type'] === \App\Enums\ProductTypes::Other && preg_match('/LOR\s?\d{1,3}/i', $product['title'])) {

    $this->command->line("DEBUG: {$product['title']}");
    $this->command->line("  → Type: {$productType->value}");
    $this->command->line("  → Set: {$setIdentifier}");
    $this->command->line("  → Language: {$language}");
    $this->command->line("  → Variant: {$variant}");
}

        if ($category === 'pokemon' || $category === 'unknown') {
            $filteredProducts[] = $product;
        } else {
            $discardedCount++;
            $this->command->warn("Discarded product: {$product['title']} | Category detected: {$category}");
        }
    }

    $this->command->info("Filtered products: " . count($filteredProducts));
    $this->command->info("Discarded products: $discardedCount");

    return $filteredProducts;
}


    public function retrieveProductsFromInterdiscount($shop){
        $categoryUrls = json_decode($shop->category_urls);
        $this->command->info("Starting HTML parsing for Interdiscount...");

        $page = 1;

        foreach ($categoryUrls as $categoryUrl) {
                // request the json
            $response = Http::get($categoryUrl);
            if ($response->failed()) {
                $this->command->error("Failed to fetch page $page. Status: {$response->status()}");
                break;
            }

            $json = $response->json();

            $products = [];
            foreach($json['products'] as $product){
                $productArray = [];
                $productArray['id'] = $product['code'];

                try {
                    $productArray['price'] = $product['productPriceData']['prices'][0]['finalPrice']['value'];
                } catch (\Exception $e) {
                    $productArray['price'] = null;
                }
                $productArray['title'] = $product['name'];
                $productArray['url'] = $shop->base_url . "/de/product/" . $product['code'];
                $productArray['available'] = true;
                $productArray['handle'] = str_replace($shop->base_url, '', $productArray['url']);
                
                // Add largest image URL to metadata
                $largestImage = null;
                if (isset($product['customImageData']) && is_array($product['customImageData'])) {
                    foreach ($product['customImageData'] as $imageData) {
                        if (isset($imageData['sizes']) && is_array($imageData['sizes'])) {
                            foreach ($imageData['sizes'] as $size) {
                                if ($largestImage === null || $size['size'] > $largestImage['size']) {
                                    $largestImage = $size;
                                }
                            }
                        }
                    }
                }
                $productArray['largest_image_url'] = $largestImage ? $shop->base_url . $largestImage['url'] : null;

                $productArray['variants'] = [$productArray];

                $products[] = $productArray;
            }
        }
        return $products;
    }

    public function retrieveProductsFromWog($shop){
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
                $productArray['largest_image_url'] = 'https://wog.ch/' . $product['coverImage'];
                
                $products[] = $productArray;
            }
        } else {
            // Handle errors
            dd($response->status(), $response->body());
        }
        return $products;
    }

    public function saveVariant($variant, $shop, $url, $original_title ){
        $title = $original_title ?: 'Unknown Product';
        $variant_title = $variant['title'] ?? 'Default Title';
        $variant_price = $variant['price'] ?? '0.00';


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

        $details = $this->pokemonHelper->determineProductDetails($title, $variant_title);

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

        $shopId = $shop->id;

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
        return $inserted;
    }

    public function retrieveProductsFromWooCommerce($shop)
{
    $categoryUrls = json_decode($shop->category_urls);
    $this->command->info("Starting HTML parsing for WooCommerce...");

    $products = [];

    // Define selector and class map
    $selectorMap = [
        'title' => ['.woocommerce-loop-product__title', 'h3.product-title a', '.product-title a'],
        'image' => ['.woocommerce-LoopProduct-link img', '.featured-image img', '.woocommerce-thumbnail img'],
        'price' => ['.price bdi', '.product-price bdi'],
        'product_id' => ['.button[data-product_id]', '[data-product_id]'],
        'outofstock_class' => ['outofstock', 'sold-out', 'stock-unavailable'],
    ];

    foreach ($categoryUrls as $categoryUrl) {
        $page = 1;
        $hasMorePages = true;

        while ($hasMorePages) {
            $paginatedUrl = $categoryUrl . 'page/' . $page;
            $this->command->info("Fetching page $page: $paginatedUrl");

            $response = Http::get($paginatedUrl);

            if ($response->failed()) {
                $this->command->error("Failed to fetch page $page. Status: {$response->status()}");
                break;
            }

            $html = $response->body();
            $crawler = new \Symfony\Component\DomCrawler\Crawler($html);

            $wc_products = $crawler->filter('.product')->each(function ($node) use ($shop, $selectorMap) {
                $product = [];

                // Get ID
                $productId = null;
                foreach ($selectorMap['product_id'] as $selector) {
                    if ($node->filter($selector)->count()) {
                        $productId = $node->filter($selector)->attr('data-product_id');
                        break;
                    }
                }
                $product['id'] = $productId ?? ($node->filter('a')->count()
                    ? md5($node->filter('a')->attr('href'))
                    : uniqid());

                // Get title
                $title = null;
                foreach ($selectorMap['title'] as $selector) {
                    if ($node->filter($selector)->count()) {
                        $title = trim($node->filter($selector)->text());
                        break;
                    }
                }
                $product['title'] = $title ?? 'Unknown Product';

                // Get URL
                $product['url'] = $node->filter('a')->count()
                    ? $node->filter('a')->attr('href')
                    : null;

                // Get price
                $priceText = null;
                foreach ($selectorMap['price'] as $selector) {
                    if ($node->filter($selector)->count()) {
                        $priceText = $node->filter($selector)->text();
                        break;
                    }
                }

                $product['price'] = self::cleanPriceString($priceText);

                // Check out-of-stock classes
                $classAttr = $node->attr('class') ?? '';
                $outofstock = false;
                foreach ($selectorMap['outofstock_class'] as $class) {
                    if (strpos($classAttr, $class) !== false) {
                        $outofstock = true;
                        break;
                    }
                }
                $product['available'] = !$outofstock;

                // Get image
                $image = null;
                foreach ($selectorMap['image'] as $selector) {
                    if ($node->filter($selector)->count()) {
                        $image = $node->filter($selector)->attr('src');
                        break;
                    }
                }
                $product['largest_image_url'] = $image;

                $product['handle'] = $product['url'] ? str_replace($shop->base_url, '', $product['url']) : null;
                $product['variants'] = [$product];

                return $product;
            });

            $products = array_merge($products, $wc_products);

            $hasMorePages = $crawler->filter('.next.page-numbers')->count() > 0;
            $page++;
        }
    }

    return $products;
}

public function retrieveProductsFromShopify($shop)
{
    $this->command->info("Starting Shopify API parsing...");

    $products = [];
    $baseUrl = $shop->base_url;
    $page = 1;
    $perPage = 250;

    $url = "$baseUrl/products.json";
    if (isset($shop->category_urls)) {
        $categoryUrls = json_decode($shop->category_urls);
        $url = $categoryUrls[0];
    }

    do {
        $this->command->info("Fetching page $page: $url?page=$page&limit=$perPage");

        $response = Http::get($url, [
            'page' => $page,
            'limit' => $perPage,
        ]);

        if ($response->failed()) {
            $this->command->error("Failed to fetch Shopify page $page. Status: {$response->status()}");
            break;
        }

        $newProductsArray = $response->json()['products'] ?? [];

        foreach ($newProductsArray as $product) {
            $externalId = $product['id'] ?? null;

            if (!empty($product['variants'])) {
                $externalId = $product['variants'][0]['id'];
            }

            $handle = $product['handle'] ?? null;
            $title = $product['title'] ?? 'Unknown Title';
            $price = $product['variants'][0]['price'] ?? 0;

            $productArray = [
                'id' => $externalId,
                'title' => $title,
                'price' => $price,
                'url' => "$baseUrl/products/$handle",
                'handle' => $handle,
                'available' => false, // will calculate below
                'variants' => [],
            ];

            $productAvailable = false;
            foreach ($product['variants'] as $variant) {
                $isAvailable = $variant['available'] ?? true;
                $productArray['variants'][] = [
                    'id' => $variant['id'],
                    'title' => $variant['title'],
                    'price' => $variant['price'],
                    'available' => $isAvailable,
                ];
                if ($isAvailable) {
                    $productAvailable = true;
                }
            }

            $productArray['available'] = $productAvailable;

            $products[] = $productArray;
        }

        $this->command->info("Fetched " . count($newProductsArray) . " products from page $page.");
        $page++;

    } while (!empty($newProductsArray));

    return $products;
}

public function retrieveProductsFromSpielezar($shop)
    {
        $categoryUrls = json_decode($shop->category_urls);
        $this->command->info("Starting HTML parsing for Spielezar...");

        $products = [];
        foreach ($categoryUrls as $categoryUrl) {
            $offset = 0;
            $hasMorePages = true;

            while ($hasMorePages) {
                $explodedUrl = explode('?', $categoryUrl);
                $filter = $explodedUrl[1] ?? '';
                $paginatedUrl = $explodedUrl[0] . '?offset=' . $offset . '&' . $filter;
                $this->command->info("Fetching offset $offset: $paginatedUrl");

                $response = Http::get($paginatedUrl);

                if ($response->failed()) {
                    $this->command->error("Failed to fetch page at offset $offset. Status: {$response->status()}");
                    break;
                }

                $html = $response->body();
                $crawler = new Crawler($html);

                $current_products = $crawler->filter('.ajax_block_product')->each(function (Crawler $node) use ($shop) {
                    $product = [];

                    $product['id'] = $node->attr('data-id-product');
                    $price = $node->filter('.price_default')->text();
                    $price = str_replace(',', '.', $price);
                    $price = preg_replace('/[^0-9.]/', '', $price);
                    $product['price'] = floatval($price);

                    $product['title'] = $node->filter('h3')->text();
                    $product['url'] = $node->filter('a')->first()->attr('href');
                    $product['handle'] = str_replace($shop->base_url, '', $product['url']);

                    $isOutOfStock = $node->filter('.button_small.line-through')->reduce(function (Crawler $btn) {
                        return stripos(trim($btn->text()), 'nicht auf lager') !== false;
                    })->count() > 0;

                    $product['available'] = !$isOutOfStock;

                    $product['variants'] = [$product];

                    return $product;
                });

                $products = array_merge($products, $current_products);
                $hasMorePages = $crawler->filter('#show_more')->count() > 0;
                $offset += 30;
            }
        }

        return $products;
    }

    public function retrieveProductsFromWebsell($shop)
{
    $this->command->info("Crawling Kabooom via HTML...");

    $categoryUrls = json_decode($shop->category_urls);
    $products = [];

    foreach ($categoryUrls as $categoryUrl) {
        $page = 1;
        $hasMorePages = true;

        while ($hasMorePages) {
            $paginatedUrl = $categoryUrl . '?page=' . $page;
            $this->command->info("Fetching page $page: $paginatedUrl");

            $response = Http::get($paginatedUrl);
            if ($response->failed()) {
                $this->command->error("Failed to fetch page $page. Status: {$response->status()}");
                break;
            }

            $html = $response->body();
            $crawler = new Crawler($html);

            $current_products = $crawler->filter('article.product-card')->each(function (Crawler $node) use ($shop) {
                $product = [];

                $product['id'] = $node->attr('data-sku');

                try {
                    $product['title'] = trim($node->filter('.productnameTitle')->text());
                } catch (\Exception $e) {
                    $product['title'] = 'Unknown Title';
                }

                try {
                    $relativeUrl = $node->filter('a')->attr('href');
                    $product['url'] = $shop->base_url . $relativeUrl;
                    $product['handle'] = $relativeUrl;
                } catch (\Exception $e) {
                    $product['url'] = null;
                    $product['handle'] = null;
                }

                try {
                    $priceRaw = $node->filter('.text-pricespecial')->text();
                    $priceClean = preg_replace('/[^0-9.]/', '', $priceRaw);
                    $product['price'] = floatval($priceClean);
                } catch (\Exception $e) {
                    $product['price'] = 0;
                }

                $classAttr = $node->attr('class') ?? '';
                $product['available'] = strpos($classAttr, 'out-of-stock') === false;

                $product['variants'] = [$product];

                return $product;
            });

            $products = array_merge($products, $current_products);

            $hasMorePages = $crawler->filter('.btn.btn-default.next')->count() > 0;
            $page++;
        }
    }

    return $products;
}

public function retrieveProductsFromPrestaShop($shop)
{
    $categoryUrls = json_decode($shop->category_urls);
    $this->command->info("Starting HTML parsing for PrestaShop...");

    $products = [];
    foreach ($categoryUrls as $categoryUrl) {
        $page = 1;
        $hasMorePages = true;

        while ($hasMorePages) {
            sleep(1); // Avoid hammering the server
            $isMana = $shop->name === 'The Mana Shop';
            $paginatedUrl = $isMana ? $categoryUrl . '&n=1000' : $categoryUrl . '?page=' . $page;

            $this->command->info("Fetching page $page: $paginatedUrl");
            $response = Http::get($paginatedUrl);

            if ($response->failed()) {
                $this->command->error("Failed to fetch page $page. Status: {$response->status()}");
                break;
            }

            $crawler = new Crawler($response->body());
            $productClass = $isMana ? '.product-container' : '.product-miniature';

            $current_products = $crawler->filter($productClass)->each(function (Crawler $node) use ($shop, $isMana) {
                $product = [];

                $product['id'] = $node->attr('data-id-product') ?? $node->filter('.addToWishlist')->attr('rel');
                if (!$product['id']) return null;

                $priceNode = $node->filter('.product-price-and-shipping .price');
                if (!$priceNode->count()) {
                    $priceNode = $node->filter('.product-price.price');
                }

                $price = $priceNode->count() ? $priceNode->text() : '0.00';
                $price = preg_replace('/[^0-9.]/', '', $price);
                $product['price'] = floatval($price);

                $productTitleNode = $node->filter('.product-title');
                if (!$productTitleNode->count()) {
                    $productTitleNode = $node->filter('.product-name');
                }
                $product['title'] = $isMana ? $productTitleNode->attr('title') : $productTitleNode->text();

                $product['url'] = $node->filter('a')->first()->attr('href');
                $product['handle'] = str_replace($shop->base_url, '', str_replace('www.', '', $product['url']));

                $product['available'] = !(
                    $node->filter('.label-danger')->count() ||
                    $node->filter('.label-warning')->count() ||
                    $node->filter('.product-unavailable')->count()
                );

                $product['variants'] = [$product];

                return $product;
            });

            $current_products = array_filter($current_products);
            $products = array_merge($products, $current_products);

            $hasMorePages = $crawler->filter('.next')->count() > 0 && !$isMana;
            $page++;
        }
    }

    return $products;
}

public function retrieveProductsFromEcwid($shop)
{
    $categoryUrls = json_decode($shop->category_urls);
    $this->command->info("Starting HTML parsing for Ecwid...");

    $products = [];

    foreach ($categoryUrls as $categoryUrl) {
        $offset = 0;
        $hasMorePages = true;

        while ($hasMorePages) {
            $paginatedUrl = $categoryUrl;
            if ($offset) {
                $paginatedUrl .= '&offset=' . $offset;
            }

            $this->command->info("Fetching offset $offset: $paginatedUrl");

            $response = Http::get($paginatedUrl);
            if ($response->failed()) {
                $this->command->error("Failed to fetch page at offset $offset. Status: {$response->status()}");
                break;
            }

            $crawler = new Crawler($response->body());

            $current_products = $crawler->filter('.grid-product')->each(function (Crawler $node) use ($shop) {
                $product = [];

                $product['id'] = $node->filter('.grid-product__wrap')->attr('data-product-id');

                try {
                    $price = $node->filter('.grid-product__price-value')->text();
                    $price = preg_replace('/[^0-9.]/', '', $price);
                    $product['price'] = floatval($price);
                } catch (\Exception $e) {
                    $product['price'] = null;
                }

                try {
                    $product['title'] = $node->filter('.grid-product__title-inner')->text();
                } catch (\Exception $e) {
                    $product['title'] = null;
                }

                try {
                    $product['url'] = $node->filter('a')->first()->attr('href');
                    $product['handle'] = str_replace($shop->base_url, '', $product['url']);
                } catch (\Exception $e) {
                    $product['url'] = null;
                    $product['handle'] = null;
                }

                try {
                    $soldOutNode = $node->filter('.grid-product__button-hover.grid-product__buy-now span');
                    $product['available'] = $soldOutNode->count() && stripos($soldOutNode->text(), 'Ausverkauft') !== false
                        ? false
                        : true;
                } catch (\Exception $e) {
                    $product['available'] = false;
                }

                $product['variants'] = [$product];

                return $product;
            });

            $hasMorePages = $crawler->filter('.pager__button-text')->reduce(function (Crawler $node) {
                return trim($node->text()) === 'Nächste';
            })->count() > 0;

            $products = array_merge($products, $current_products);
            $offset += 60;
        }
    }

    return $products;
}

public function retrieveProductsFromSoftridge($shop)
{
    $categoryUrls = json_decode($shop->category_urls);
    $this->command->info("Starting parsing for Softridge...");

    $products = [];

    foreach ($categoryUrls as $categoryUrl) {
        $page = 1;
        $hasMorePages = true;

        while ($hasMorePages) {
            $paginatedUrl = $categoryUrl . '&page=' . $page;
            $this->command->info("Fetching page $page: $paginatedUrl");

            $response = Http::get($paginatedUrl);

            if ($response->failed()) {
                $this->command->error("Failed to fetch page $page. Status: {$response->status()}");
                break;
            }

            $json = $response->json();

            foreach ($json['products'] as $product) {
                $productArray = [];
                $productArray['id'] = $product['id'];

                $priceText = $product['salesPriceText'] ?? '';
                $priceText = str_replace('.–', '', $priceText);
                $priceText = preg_replace('/[^0-9.]/', '', $priceText);
                $productArray['price'] = floatval($priceText);

                $productArray['title'] = $product['fullTitle'] . ' ' . $product['regionCode'];
                $productArray['handle'] = $product['linkUrl'];
                $productArray['url'] = $shop->base_url . $productArray['handle'];
                $productArray['available'] = $product['statusColor'] !== 'Gray';

                $productArray['variants'] = [$productArray];

                $products[] = $productArray;
            }

            $hasMorePages = $json['hasMore'] ?? false;
            $page++;
        }
    }

    return $products;
}
    
public function retrieveProductsFromCsCart($shop)
{
    $categoryUrls = json_decode($shop->category_urls);
    $this->command->info("Starting HTML parsing for CS-Cart...");

    $products = [];

    foreach ($categoryUrls as $categoryUrl) {
        $response = Http::get($categoryUrl);

        if ($response->failed()) {
            $this->command->error("Failed to fetch CS-Cart category: $categoryUrl. Status: {$response->status()}");
            continue;
        }

        $htmlFragment = $response->json()['html']['pagination_contents'] ?? '';
        $crawler = new Crawler($htmlFragment);

        $current_products = $crawler->filter('.ut2-gl__item')->each(function (Crawler $node) use ($shop) {
            $product = [];

            $product['id'] = $node->filter('input[name^="product_data"]')->count()
                ? $node->filter('input[name^="product_data"]')->attr('value')
                : null;

            $priceText = $node->filter('.ty-price')->count()
                ? $node->filter('.ty-price')->text()
                : '';
            $product['price'] = floatval(trim(str_replace('CHF', '', preg_replace('/[^0-9.]/', '', $priceText))));

            $product['title'] = $node->filter('.product-title')->count()
                ? trim($node->filter('.product-title')->text())
                : null;

            $product['url'] = $node->filter('.product-title')->count()
                ? $node->filter('.product-title')->attr('href')
                : null;

            $product['handle'] = $product['url']
                ? str_replace($shop->base_url, '', $product['url'])
                : null;

            $product['available'] = $node->filter('button.ty-btn__add-to-cart')->count() > 0 &&
                                    $node->filter('.ty-qty-out-of-stock')->count() === 0;

            $product['variants'] = [$product];

            return $product;
        });

        $products = array_merge($products, $current_products);
    }

    return $products;
}

public function retrieveProductsFromKidz($shop)
{
    $categoryUrls = json_decode($shop->category_urls);
    $this->command->info("Starting HTML parsing for Kidz...");

    $products = [];

    foreach ($categoryUrls as $categoryUrl) {
        $page = 1;
        $hasMorePages = true;

        while ($hasMorePages) {
            $paginatedUrl = $categoryUrl . '&page=' . $page;
            $this->command->info("Fetching page $page: $paginatedUrl");

            $response = Http::get($paginatedUrl);
            if ($response->failed()) {
                $this->command->error("Failed to fetch page $page. Status: {$response->status()}");
                break;
            }

            $crawler = new Crawler($response->body());

            $current_products = $crawler->filter('.card--card')->each(function (Crawler $node) use ($shop) {
                try {
                    $product = [];

                    $titleNode = $node->filter('.card__heading a');
                    $product['title'] = trim($titleNode->text());
                    $product['url'] = $titleNode->attr('href');
                    $product['handle'] = str_replace($shop->base_url, '', $product['url']);

                    // ID from hidden input
                    $idNode = $node->filter('input.product-variant-id');
                    $product['id'] = $idNode->count() ? $idNode->attr('value') : md5($product['url']);

                    // Price (prefer sale, fallback to regular)
                    $priceNode = $node->filter('.price-item--sale.price-item--last');
                    if (!$priceNode->count()) {
                        $priceNode = $node->filter('.price-item--regular');
                    }
                    $price = preg_replace('/[^0-9.]/', '', $priceNode->text());
                    $product['price'] = floatval($price);

                    // Availability: check disabled button or "Ausverkauft" text
                    $isDisabled = $node->filter('.quick-add__submit')->attr('disabled') !== null;
                    $badgeText = strtolower($node->filter('.badge')->text(''));
                    $product['available'] = !$isDisabled && !str_contains($badgeText, 'ausverkauft');

                    // Optional image
                    $imgNode = $node->filter('img');
                    if ($imgNode->count()) {
                        $product['largest_image_url'] = 'https:' . $imgNode->attr('src');
                    }

                    $product['variants'] = [$product];

                    return $product;
                } catch (\Exception $e) {
                    return null; // Skip if structure breaks
                }
            });

            $products = array_merge($products, array_filter($current_products));

            $hasMorePages = $crawler->filter('.pagination__item--prev')->count() > 0;
            $page++;
        }
    }

    return $products;
}

public function retrieveProductsFromGalaxy($shop)
{
    $categoryUrls = json_decode($shop->category_urls);
    $this->command->info("Starting HTML parsing for Galaxy...");

    $products = [];

    foreach ($categoryUrls as $categoryUrl) {
        $page = 1;
        $hasMorePages = true;

        while ($hasMorePages) {
            $paginatedUrl = $categoryUrl . '&page=' . $page;
            $this->command->info("Fetching page $page: $paginatedUrl");

            $response = Http::get($paginatedUrl);
            if ($response->failed()) {
                $this->command->error("Failed to fetch page $page. Status: {$response->status()}");
                break;
            }

            $crawler = new Crawler($response->body());

            $current_products = $crawler->filter('.product-block')->each(function (Crawler $node) use ($shop) {
                try {
                    $product = [];

                    $product['id'] = $node->attr('data-product-id') ?? md5($node->text());

                    if ($node->filter('.amount.theme-money')->count()) {
                        $price = $node->filter('.amount.theme-money')->text();
                    } elseif ($node->filter('.price')->count()) {
                        $price = $node->filter('.price')->text();
                    } else {
                        return null;
                    }

                    $price = preg_replace('/[^0-9.]/', '', $price);
                    $product['price'] = floatval($price);

                    $product['title'] = $node->filter('.title')->text('');
                    $product['url'] = $node->filter('a')->first()->attr('href');
                    $product['handle'] = str_replace($shop->base_url, '', $product['url']);
                    $product['available'] = true;

                    $product['variants'] = [$product];

                    return $product;
                } catch (\Exception $e) {
                    return null;
                }
            });

            $products = array_merge($products, array_filter($current_products));

            // Galaxy hides the next button when there are no more pages
            $hasMorePages = $crawler->filter('.linkless.next')->count() < 1;
            $page++;
        }
    }

    return $products;
}


public static function cleanPriceString(?string $raw): float
{
    if (!$raw) return 0.0;

    // Replace non-breaking spaces and narrow no-break spaces
    $raw = str_replace(["\u{00A0}", ' ', ' ', '&nbsp;', 'CHF'], '', $raw);

    // Remove all but digits, dots and commas
    $cleaned = preg_replace('/[^\d.,]/', '', $raw);

    // If comma is used as decimal, fix it
    if (substr_count($cleaned, ',') === 1 && substr_count($cleaned, '.') === 0) {
        $cleaned = str_replace(',', '.', $cleaned);
    } else {
        $cleaned = str_replace(',', '', $cleaned); // remove thousands sep
    }

    return floatval($cleaned);
}

public function retrieveProductsFromShopware($shop)
{
    $this->command->info("Starting HTML parsing for Shopware...");

    $products = [];
    $categoryUrls = json_decode($shop->category_urls);

    foreach ($categoryUrls as $categoryUrl) {
        $page = 1;
        $hasMorePages = true;

        while ($hasMorePages) {
            $paginatedUrl = $categoryUrl . '?p=' . $page;
            $this->command->info("Fetching page $page: $paginatedUrl");

            $response = Http::get($paginatedUrl);

            if ($response->failed()) {
                $this->command->error("Failed to fetch page $page. Status: {$response->status()}");
                break;
            }

            $html = $response->body();
            $crawler = new Crawler($html);

            $current_products = $crawler->filter('.product-box')->each(function (Crawler $node) use ($shop) {
                try {
                    $product = json_decode($node->attr('data-product-information'), true);
                    if (!$product) return null;

                    $priceNode = $node->filter('.product-price');
                    $price = $priceNode->count() ? $priceNode->text() : '';
                    $price = preg_replace('/[^0-9.]/', '', $price);
                    $product['price'] = floatval($price);

                    $variantNode = $node->filter('.product-variant-characteristics-text');
                    $variant = $variantNode->count() ? $variantNode->text() : '';

                    if ($product['name'] === $variant || !$variant) {
                        $product['title'] = $product['name'];
                    } else {
                        $product['title'] = $product['name'] . ' - ' . $variant;
                    }

                    $urlNode = $node->filter('a')->first();
                    $product['url'] = $urlNode->count() ? $urlNode->attr('href') : null;
                    $product['handle'] = str_replace($shop->base_url, '', $product['url']);
                    $product['available'] = true;
                    $product['variants'] = [$product];

                    return $product;
                } catch (\Exception $e) {
                    $this->command->warn("Error parsing a product: " . $e->getMessage());
                    return null;
                }
            });

            $current_products = array_filter($current_products);
            $products = array_merge($products, $current_products);

            $hasMorePages = $crawler->filter('.page-item.page-next')->count() > 0;

            $page++;
        }
    }

    return $products;
}

}