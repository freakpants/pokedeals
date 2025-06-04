<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use App\Helpers\ShopHelper;
use App\Mail\NewProductsMail;

class CheckShop extends Command
{
    protected $signature = 'app:check-shop {shopType?} {--all : Refresh all shops}';
    protected $description = 'Check a shop (or all shops) for new products';

    public function handle()
    {
        $shopHelper = new ShopHelper($this);
        $shopType = $this->argument('shopType');
        $all = $this->option('all');

        $shops = collect();

        if ($all) {
            $shops = DB::table('external_shops')->orderBy('last_scraped_at', 'asc')->get();
        } elseif ($shopType) {
            $shop = DB::table('external_shops')
                ->where('shop_type', $shopType)
                ->orWhere('name', $shopType)
                ->first();

            if (!$shop) {
                $this->error("No shop found for type or name: " . $shopType);
                return;
            }

            $shops->push($shop);
        } else {
            $shop = DB::table('external_shops')->orderBy('last_scraped_at', 'asc')->first();

            if (!$shop) {
                $this->error("No shops found in the database.");
                return;
            }

            $shops->push($shop);
        }

        foreach ($shops as $shop) {
            $this->scrapeShop($shop, $shopHelper);
        }
    }

    private function scrapeShop($shop, $shopHelper)
    {
        $this->info("Checking shop: " . $shop->name);

        try {
            $products = $shopHelper->retrieveProductsFromShop($shop);
        } catch (\Throwable $e) {
            $this->error("Error retrieving products from {$shop->name}: " . $e->getMessage());
            return;
        }

        $positiveStockProducts = collect($products)->filter(fn($product) =>
            ($product['stock'] ?? 0) > 0 || ($product['available'] ?? false) === true
        );

        $this->info("Found " . count($products) . " products");

        $ids = collect($products)->pluck('id');
        $existingProducts = DB::table('external_products')->whereIn('external_id', $ids)->get();
        $existingIds = $existingProducts->pluck('external_id');

        $newProducts = collect($products)->filter(fn($product) =>
            !$existingIds->contains($product['id'])
        );

        $outOfStockProducts = $existingProducts->filter(fn($product) =>
            $product->stock == 0
        );

        $inStockProducts = $positiveStockProducts->filter(fn($product) =>
            $outOfStockProducts->contains('external_id', $product['id'])
        );

        $this->info("Found " . count($existingProducts) . " existing products");
        $this->info("Found " . count($outOfStockProducts) . " out-of-stock products");
        $this->info("Found " . count($inStockProducts) . " back in stock");
        $this->info("Found " . count($newProducts) . " new products");

        if ($newProducts->isNotEmpty()) {
            $this->notifyByEmail($newProducts, $shop, 'New Product(s) on ' . ucfirst($shop->name));
        }

        if ($inStockProducts->isNotEmpty()) {
            $this->notifyByEmail($inStockProducts, $shop, 'Product(s) back in stock on ' . ucfirst($shop->name));
        }

        foreach ($products as $product) {
            if (isset($product['variants'])) {
                foreach ($product['variants'] as $variant) {
                    $shopHelper->saveVariant($variant, $shop, $product['url'], $product['title']);
                }
            }
        }

        DB::table('external_shops')
            ->where('id', $shop->id)
            ->update(['last_scraped_at' => now()]);
    }

    private function notifyByEmail($products, $shop, $subject)
    {
        $emailAddresses = DB::table('email_addresses')->pluck('email');
        foreach ($emailAddresses as $email) {
            Mail::to($email)->send(new NewProductsMail($products, $shop, $subject));
        }
    }
}
