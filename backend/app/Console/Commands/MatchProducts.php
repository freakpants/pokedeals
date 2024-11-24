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

    public function handle()
    {
        $externalProducts = ExternalProduct::all();
        $localProducts = PokemonProduct::all(); // Load all local products for fuzzy matching
        $matchedCount = 0;

        foreach ($externalProducts as $externalProduct) {
            $metadata = $externalProduct->metadata;
            $externalSku = $metadata['variants'][0]['sku'] ?? null;
            $pokemonProduct = null;

            if ($externalSku) {
                // Attempt exact SKU match
                $pokemonProduct = PokemonProduct::where('sku', $externalSku)->first();
            }

            if (!$pokemonProduct) {
                // Fuzzy title matching if no exact SKU match
                $highestSimilarity = 0;
                $bestMatch = null;

                foreach ($localProducts as $product) {
                    $externalTitle = strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $externalProduct->title));
                    $localTitle = strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $product->title));

                    similar_text($externalTitle, $localTitle, $similarity);

                    if ($similarity > $highestSimilarity) {
                        $highestSimilarity = $similarity;
                        $bestMatch = $product;
                    }
                }

                // Match only if similarity is above a certain threshold
                if ($highestSimilarity >= 80) {
                    $pokemonProduct = $bestMatch;
                }
            }

            // Create match only if both SKU and external ID exist
            if ($pokemonProduct && $externalSku) {
                $existingMatch = ProductMatch::where('local_sku', $pokemonProduct->sku)
                    ->where('external_id', $externalProduct->external_id)
                    ->first();

                if (!$existingMatch) {
                    ProductMatch::create([
                        'local_sku' => $pokemonProduct->sku,
                        'external_id' => $externalProduct->external_id,
                        'title' => $externalProduct->title,
                        'price' => $externalProduct->price,
                        'shop_id' => $externalProduct->shop_id,
                    ]);
                    $matchedCount++;
                    $this->info("Matched: {$pokemonProduct->sku} with {$externalProduct->title}");
                }
            } else {
                $this->warn("No match found for: {$externalProduct->title}");
            }
        }

        $this->info("Matching complete. Total matched products: {$matchedCount}");
    }
}
