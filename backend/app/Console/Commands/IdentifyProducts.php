<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class IdentifyProducts extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'products:identify';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        /*
            SELECT * FROM `external_products` WHERE stock > 0
            and title not like '%Chopin%'
            AND title not like '%Basel%'
            AND title not like '%Aargau%'
            AND title not like '%Crados Album%'
            AND title not like '%Ravensburger%'
            AND title not like '%Trading Card Game Classic%'
            AND title not like '%World Championship%'
            AND title not like '%Wimmelspiel%'
            AND title not like '%Kampf-Akademie%'
            AND title not like '%Turnierkollektion%'
            AND title not like '%Scrabble%'
            AND title not like '%Festtagskalender%'
            AND title not like '%Trainer-Toolkit%'
            AND title not like '%Trainer\'s Toolkit%'
            AND title not like '%Deck%'
            AND title not like '%Battle Deck%'
            AND title not like '%Kampf Akademie%'
            and title not like '%Kampfdeck%'
            AND title not like '%Liga Kampf Deck%'
            AND title not like '%Deck Holder Kollektion%'
            AND title NOT LIKE '%Adventure Chest%' 
            AND title NOT LIKE '%FUN Pack%' 
            AND title NOT LIKE '%Back to School%' 
            AND title NOT LIKE '%Adventskalender%' 
            AND title NOT LIKE '%Sammleralbum%' 
            AND title NOT LIKE '%Trainer Toolkit%' 
            AND (type = 'other' OR ('set_identifier' = 'other' AND variant = 'other'))  
            ORDER BY `external_products`.`price` DESC;
        */
        $products = \App\Models\ExternalProduct::where('stock', '>', 0)
            ->where(function ($query) {
                $query->where('type', 'other')
                    ->orWhere(function ($query) {
                        $query->where('set_identifier', 'other')
                            ->where('variant', 'other');
                    });
            })
            ->get();

        $pokemonHelper = new \App\Helpers\PokemonHelper();

        // loop through each product
        foreach ($products as $product) {
           

            // use the pokemon helper to identify the product
            $productDetails = $pokemonHelper->determineProductDetails($product->title);

            // if there is a set_identifier that is not 'other', update the product
            if ($product->set_identifier === 'other' && $productDetails['set_identifier'] !== 'other') {
                $product->set_identifier = $productDetails['set_identifier'];
            }

            // if there is a variant that is not 'other', update the product
            if ($product->variant === 'other' && $productDetails['variant'] !== 'other') {
                $product->variant = $productDetails['variant'];
            }

            // if the productType was 'other', and now isnt, update it
            if ($product->type === 'other' && $productDetails['product_type'] !== 'other') {
                $product->type = $productDetails['product_type'];
            }

            // save the product
            $product->save();

            // output the product details
            $this->info($product->title . ' - ' . $productDetails['set_identifier'] . ' - ' . $productDetails['variant']);


            

        }
    }
}
