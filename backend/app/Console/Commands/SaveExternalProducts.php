<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

class SaveExternalProducts extends Command
{
    protected $signature = 'products:save-external';
    protected $description = 'Save external products to the database';

    public function handle()
    {
        $shop = DB::table('external_shops')->where('name', 'Skyspell')->first();

        if (!$shop) {
            $this->error('Skyspell shop not found. Please seed the external_shops table.');
            return 1;
        }

        $baseUrl = $shop->base_url;
        $shopId = $shop->id;
        $page = 1;
        $perPage = 250; // Max limit
        $newProducts = 0;

        $this->info("Fetching external products from {$shop->name}...");

        do {
            $response = Http::get("$baseUrl/products.json", [
                'page' => $page,
                'limit' => $perPage,
            ]);

            if ($response->failed()) {
                $this->error("Failed to fetch products from {$shop->name} (Page: $page).");
                return 1;
            }

            $products = $response->json()['products'] ?? [];
            if (empty($products)) {
                $this->info("No more products found on Page $page.");
                break;
            }

            foreach ($products as $product) {
                $externalId = $product['id'] ?? null;
                $title = $product['title'] ?? 'Unknown Title';
                $price = $product['variants'][0]['price'] ?? null;
                $url = "$baseUrl/products/{$product['handle']}";
                $metadata = json_encode($product);

                if (!$externalId) {
                    $this->warn("Skipping product with missing ID: $title");
                    continue;
                }

                // Insert or update the product
                $inserted = DB::table('external_products')->updateOrInsert(
                    [
                        'external_id' => $externalId,
                        'shop_id' => $shopId,
                    ],
                    [
                        'title' => $title,
                        'price' => $price,
                        'url' => $url,
                        'metadata' => $metadata,
                        'updated_at' => now(),
                    ]
                );

                if ($inserted) {
                    $newProducts++;
                }
            }

            $this->info("Processed Page $page with " . count($products) . " products.");
            $page++;

            // For subsequent runs, break after the first page if there are no new products
            if ($page === 2 && $newProducts === 0) {
                $this->info('No new products found. Stopping.');
                break;
            }
        } while (!empty($products));

        $this->info("Finished. Total new products added: $newProducts.");
        return 0;
    }
}
