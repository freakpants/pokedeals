<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use App\Helpers\ShopHelper;
use App\Mail\NewProductsMail;

class CheckShop extends Command
{
    protected $signature = 'app:check-shop {shopType}';
    protected $description = 'Check a shop website for new products';

    public function handle()
    {
        $shopIdentifier = $this->argument('shopType');
        $shopHelper = new ShopHelper($this);

        $shop = DB::table('external_shops')
        ->where('shop_type', $shopIdentifier)
        ->orWhere('name', $shopIdentifier)
        ->orderBy('last_scraped_at', 'asc')
        ->first();

        if (!$shop) {
            $this->error("No shop found for type or name: " . $shopIdentifier);
            return;
        }

        try {
                $products = $shopHelper->retrieveProductsFromShop($shop);
        } catch (\Throwable $e) {
            $this->error("Error retrieving products: " . $e->getMessage());
            return; // Exit early, don't update last_scraped_at
        }

        $positiveStockProducts = collect($products)->filter(function ($product) {
            return ($product['stock'] ?? 0) > 0 || ($product['available'] ?? false) === true;
        });

        $this->info("Found " . count($products) . " products on " . $shop->name);

        $ids = collect($products)->pluck('id');
        $existingProducts = DB::table('external_products')->whereIn('external_id', $ids)->get();
        $existingIds = $existingProducts->pluck('external_id');

        $newProducts = collect($products)->filter(function ($product) use ($existingIds) {
            return !$existingIds->contains($product['id']);
        });

        $outOfStockProducts = $existingProducts->filter(function ($product) {
            return $product->stock == 0;
        });

        $this->info("Found " . count($existingProducts) . " existing products on " . $shop->name);
        $this->info("Found " . count($outOfStockProducts) . " out of stock products on " . $shop->name);
    
        // Determine products that were out of stock and are now back in stock
        $inStockProducts = $positiveStockProducts->filter(function ($product) use ($outOfStockProducts) {
            return $outOfStockProducts->contains('external_id', $product['id']);
        });

 $this->info("Found " . count($inStockProducts) . " products that are now in stock on " . $shop->name);
    $this->info("Found " . count($newProducts) . " new products on " . $shop->name);

        // Notify about new products
        if ($newProducts->isNotEmpty()) {
            $subject = 'New Product(s) on ' . ucfirst($shop->name);
            $this->notifyByEmail($newProducts, $shop, $subject);


        }

        // Notify about back-in-stock products
        if ($inStockProducts->isNotEmpty()) {
            $subject = 'Product(s) back in stock on ' . ucfirst($shop->name);
            $this->notifyByEmail($inStockProducts, $shop, $subject);


        }

        // Save new products to the database
        foreach ($products as $product) {
            if (isset($product['variants'])) {
                foreach ($product['variants'] as $variant) {
                    $shopHelper->saveVariant($variant, $shop, $product['url'], $product['title']);
                }
            }
        }

        // mark the shop as checked
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
