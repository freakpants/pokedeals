<?php

namespace App\Console\Commands;

use App\Models\ExternalProduct;
use App\Models\PokemonProduct;
use App\Models\ProductMatch;
use Illuminate\Console\Command;

class MatchProducts extends Command
{
    protected $signature = 'products:match';
    protected $description = 'Match external products with local Pokemon products';

    // Normalize titles while preserving word boundaries
    function preprocessTitle(string $title): string {
        // Convert to lowercase
        $title = strtolower($title);
    
        // Replace common separators with spaces
        $title = str_replace(['-', '/', ':'], ' ', $title);
    
        // Remove unwanted characters except alphanumerics and spaces
        $title = preg_replace('/[^a-zA-Z0-9\s]/', '', $title);
    
        // Replace multiple spaces with a single space
        $title = preg_replace('/\s+/', ' ', $title);
    
        // Trim leading and trailing spaces
        return trim($title);
    }
    


    public function handle()
    {
        $externalProducts = ExternalProduct::all();
        $localProducts = PokemonProduct::all(); // Load all local products for fuzzy matching
        $matchedCount = 0;

        // Define arrays for sets and generations
        $sets = ['surging sparks', 'shrouded fable', 'prismatic evolutions', 'paldea evolved', 'stellar crown']; // Example, fill this with actual sets
        // $generations = ['scarlet & violet', 'sword & shield']; // Example, fill this with actual generations

        foreach ($externalProducts as $externalProduct) {
            $externalTitle = $this->preprocessTitle($externalProduct->title);

            foreach ($localProducts as $localProduct) {
                $localTitle = $this->preprocessTitle($localProduct->title);

                // Check if the titles match
                if ($externalTitle === $localTitle) {
                    // Create a new match
                    $match = new ProductMatch();
                    $match->local_sku = $localProduct->sku;
                    $match->external_id = $externalProduct->external_id;
                    $match->title = $externalProduct->title;
                    $match->price = $externalProduct->price;
                    $match->shop_id = $externalProduct->shop_id;
                    $match->save();

                    $matchedCount++;
                    break;
                }
            }
        }
        
        

        
        
        
        

        $this->info("Matching complete. Total matched products: {$matchedCount}");
    }
}
