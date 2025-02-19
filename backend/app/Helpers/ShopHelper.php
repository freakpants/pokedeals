<?php

namespace App\Helpers;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\DB;

class ShopHelper{

    protected $command, $pokemonHelper;

    public function __construct($command)
    {
        $this->command = $command;
        $this->pokemonHelper = new PokemonHelper(); 
    }

    public function retrieveProductsFromShop($shop){
        switch($shop->shop_type){
            case 'wog':
                return $this->retrieveProductsFromWog($shop);
            break;
            case 'interdiscount':
                return $this->retrieveProductsFromInterdiscount($shop);
            break;
        }
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
}