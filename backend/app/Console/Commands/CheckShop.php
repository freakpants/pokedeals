<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use App\Helpers\ShopHelper;
use App\Mail\NewProductsMail;

class CheckShop extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:check-shop {shopType}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check a shop website for new products';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $shopType = $this->argument('shopType');
        $shopHelper = new ShopHelper($this);

        $shop = DB::table('external_shops')->where('shop_type', $shopType)->first();
        $products = $shopHelper->retrieveProductsFromShop($shop);

        // output the number of products
        $this->info("Found " . count($products) . " products on " . ucfirst($shopType));

        // check if we have all ids in the database 
        $ids = array_map(function($product){
            return $product['id'];
        }, $products);

        $existingProducts = DB::table('external_products')->whereIn('external_id', $ids)->get();
        $existingIds = $existingProducts->map(function($product){
            return $product->external_id;
        });

        $newProducts = array_filter($products, function($product) use ($existingIds){
            return !$existingIds->contains($product['id']);
        });

        $this->info("Found " . count($newProducts) . " new products on " . ucfirst($shopType));

        // send email to every email address in the email_addresses table
        if (count($newProducts) > 0) {
            
            $subject = 'New Product(s) on ' . ucfirst($shopType); // Custom subject
            
            $emailAddresses = DB::table('email_addresses')->pluck('email');
            foreach ($emailAddresses as $email) {
                Mail::to($email)->send(new NewProductsMail($newProducts,$shop, $subject));
            }
            // add the product to the database
            foreach ($newProducts as $product) {
                foreach ($product['variants'] as $variant) {
                    $url = $product['url'];
                    $original_title = $product['title'];
                    $shopHelper->saveVariant($variant, $shop, $url, $original_title);
                }
            }
        }
    }
}
