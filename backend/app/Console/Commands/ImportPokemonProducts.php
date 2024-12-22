<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\PokemonProduct;
use Illuminate\Support\Facades\File;

use App\Helpers\PokemonHelper;
class ImportPokemonProducts extends Command
{
    protected $signature = 'pokemon:import {file}';
    protected $description = 'Import Pokémon TCG products from a JSON file';

    public function handle()
    {
        $filePath = $this->argument('file');

        if (!File::exists($filePath)) {
            $this->error("File not found: {$filePath}");
            return Command::FAILURE;
        }

        $jsonData = File::get($filePath);
        $products = json_decode($jsonData, true);

        if (is_null($products)) {
            $this->error("Invalid JSON in file: {$filePath}");
            return Command::FAILURE;
        }

        $pokemonHelper = new PokemonHelper();

        foreach ($products as $product) {

            $details = $pokemonHelper->determineProductDetails($product['title']);

            $set_identifier = $details['set_identifier'];
            $product_type = $details['product_type'];

            PokemonProduct::updateOrCreate(
                ['sku' => $product['sku']], // Use SKU as the primary key and unique constraint
                [
                    'title' => $product['title'] ?? 'Unknown Title',
                    'price' => $product['price'] ?? null,
                    'type' => $product_type,
                    'set_identifier' => $set_identifier,
                    'variant' => $details['variant'] ?? null,
                    'product_url' => $product['productUrl'] ?? 'N/A', // Map productUrl to product_url
                    'images' => $product['images'] ?? [], // Default images (empty array)
                ]
            );
        }
        
        

        $this->info('Products imported successfully!');
        return Command::SUCCESS;
    }
}
?>