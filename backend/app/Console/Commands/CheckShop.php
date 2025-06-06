<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use App\Helpers\ShopHelper;
use App\Mail\NewProductsMail;
use App\Models\User;

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

    $this->info("Found " . count($products) . " products");

    // Flatten all variants into one collection
    $allVariants = collect();
    foreach ($products as $product) {
        foreach ($product['variants'] ?? [] as $variant) {
            $variant['product_title'] = $product['title'];
            $variant['url'] = $product['url'];
            $variant['shop_id'] = $shop->id;
            $variant['largest_image_url'] = $product['largest_image_url'] ?? null;
            $allVariants->push($variant);
        }
    }

    $variantIds = $allVariants->pluck('id');
    $existingVariants = DB::table('external_products')
        ->whereIn('external_id', $variantIds)
        ->get()
        ->keyBy('external_id');

    $newVariants = collect();
    $backInStockVariants = collect();

    foreach ($allVariants as $variant) {
        $existing = $existingVariants->get($variant['id']);
        $currentStock = $variant['stock'] ?? 0;

        if (!$existing) {
            $newVariants->push($variant);
        } elseif (($existing->stock ?? 0) == 0 && $currentStock > 0) {
            $backInStockVariants->push($variant);
        }

        $shopHelper->saveVariant($variant, $shop, $variant['url'], $variant['product_title']);
    }

    $this->info("Found " . count($existingVariants) . " existing variants");
    $this->info("Found " . count($newVariants) . " new variants");
    $this->info("Found " . count($backInStockVariants) . " variants back in stock");

    if ($newVariants->isNotEmpty()) {
        $this->notifyByEmail($newVariants, $shop, 'New product(s) on ' . ucfirst($shop->name));
    }

    if ($backInStockVariants->isNotEmpty()) {
        $this->notifyByEmail($backInStockVariants, $shop, 'Product(s) back in stock on ' . ucfirst($shop->name));
    }

    DB::table('external_shops')
        ->where('id', $shop->id)
        ->update(['last_scraped_at' => now()]);
}


    private function notifyByEmail($products, $shop, $subject)
    {
        $usersToNotify = User::whereHas('notifiedShops', function ($query) use ($shop) {
            $query->where('external_shop_id', $shop->id);
        })->get();

        foreach ($usersToNotify as $user) {
            Mail::to($user->email)->send(new NewProductsMail($products, $shop, $subject));
        }
    }
}
