<?php

namespace App\Console\Commands;

use App\Helpers\PokemonHelper;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Enums\ProductTypes;

class RefreshAndImportPokemon extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'pokemon:refresh-and-import';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Rollback migrations, migrate, seed the database, and import Pokémon products from JSON';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Rolling back all migrations...');
        $this->call('migrate:rollback', ['--force' => true]);

        $this->info('Running migrations...');
        $this->call('migrate', ['--force' => true]);

        $this->info('Seeding the database...');
        $this->call('db:seed', ['--force' => true]); 

        $this->info('Importing Pokémon products...');
        // Define the files to import
        $filePaths = [
            storage_path('pokemon.json'),           
        ];

        foreach ($filePaths as $filePath) {
            if (file_exists($filePath)) {
                $this->info("Importing from file: {$filePath}");
                $this->call('pokemon:import', ['file' => $filePath]);
            } else {
                $this->error("File not found: {$filePath}");
            }
        }

        // import the tcgplayerproducts json file, modify it then use it to call pokemon:import
        $this->info('Importing TCGPlayer products...');
        $tcgplayerProducts = json_decode(file_get_contents(storage_path('tcgplayerproducts.json')), true)['results'];

        // Modify the TCGPlayer products to match the Pokémon products
        $pokemonProducts = [];
        $variants = [];
        foreach ($tcgplayerProducts as $tcgplayerProduct) {
            /* 
                "title": "Pokémon TCG: Knock Out Collection (Boltund, Eiscue & Galarian Sirfetch'd)",
    "sku": "699-17134",
    "price": "£9.99",
    "productUrl": "https://www.pokemoncenter.com/en-gb/product/699-17134/pokemon-tcg-knock-out-collection-boltund-eiscue-and-galarian-sirfetch-d",
    "images": [

    */ 

            $extendedData = $tcgplayerProduct['extendedData'];
            // check if any of the extended data have a array property called displayName with the value "Card Number"
            $cardNumber = null;
            foreach ($extendedData as $data) {
                if ($data['displayName'] === 'Card Number') {
                    $cardNumber = $data['value'];
                    break;
                }
            }

            // if there is a card number, this is a card. so skip it
            if ($cardNumber) {
                continue;
            }

            // replace Pokemon TCG
            $tcgplayerProduct['name'] = str_replace('Pokemon TCG: ', '', $tcgplayerProduct['name']);
            $title = $tcgplayerProduct['name'];


            $sku = $tcgplayerProduct['productId'];
            $price = '';
            $productUrl = $tcgplayerProduct['url'];
            $images = [$tcgplayerProduct['imageUrl']];

            $pokemonProducts[] = [
                'title' => $title,
                'sku' => $sku,
                'price' => $price,
                'productUrl' => $productUrl,
                'images' => $images
            ];

            // use the pokemon helper to determine product details
            $details = PokemonHelper::determineProductDetails($title);

            /*
                        [
                'product_type' => ProductTypes::EliteTrainerBox->value,
                'en_short' => 'roaring_moon_etb',
                'de_strings' => json_encode(['donnersichel', 'Paradox Rift ETB - Deutsch - Blau']),
                'en_strings' => json_encode(['roaring moon', 'Paradox Rift ETB - Englisch - Blau']),
                'en_name' => 'Scarlet & Violet-Paradox Rift Elite Trainer Box (Roaring Moon)'
            ],
            */
            // if the product type isnt other, create a variant for this
            if ($details['product_type'] !== ProductTypes::Other->value) {
                // use the clean name with _ as the en_short
                // replace pokemon tcg in the clean name
                $tcgplayerProduct['cleanName'] = str_replace('Pokemon TCG ', '', $tcgplayerProduct['cleanName']);

                $enShort = $tcgplayerProduct['cleanName'];
                // replace spaces in enshort with _
                $enShort = str_replace(' ', '_', $enShort);
                // lowercase the enshort
                $enShort = strtolower($enShort);

                // try the first two words for the en strings
                $enStrings = explode(' ', $tcgplayerProduct['cleanName']);

                // if there is something like [Pikachu] in the unclean String use that as the en_string
                if (is_string($tcgplayerProduct['name']) && preg_match('/\[(.*?)\]/', $tcgplayerProduct['name'], $matches)) {
                    $enStrings = $matches[1];
                    // replace the []
                    $enStrings = str_replace('[', '', $enStrings);
                    $enStrings = str_replace(']', '', $enStrings);
                } else {

                    if(count($enStrings) < 2) {
                        $enStrings = $tcgplayerProduct['name'];
                    } else {
                        // if any of the strings is a year higher than 1999, use all strings up until that point
                        // check every single string
                        $foundYear = false;
                        foreach ($enStrings as $key => $string) {
                            if (is_numeric($string) && $string > 1999) {
  
                                // use all strings up to this key, separated by a space
                                $enStrings = implode(' ', array_slice($enStrings, 0, $key+1));

                                $foundYear = true;
                                break;
                            }
                        }
                        if(!$foundYear) {
                            $enStrings = $enStrings[0] . ' ' . $enStrings[1];
                        }
    
                        
                    }
                }

                

                // check if there is a variant with this en_short
                if (DB::table('pokemon_product_variants')->where('en_short', $enShort)->exists()) {
                    continue;
                }

                // if the en_string is only "Back to", skip this variant
                if ($enStrings === 'Back to') {
                    continue;
                }

                $variants[] = [
                    'product_type' => $details['product_type'],
                    'en_short' => $enShort,
                    'de_strings' => json_encode([]),
                    'en_strings' => json_encode([$enStrings]),
                    'en_name' => $title
                ];
            }

            

        }

        // save the variants
        foreach ($variants as $variant) {
            if (!DB::table('pokemon_product_variants')->where('en_short', $variant['en_short'])->exists()) {
                DB::table('pokemon_product_variants')->insert($variant);
            }
        } 

        // create a temporary file for use with the pokemon:import command
        file_put_contents(storage_path('tcg-processed.json'), json_encode($pokemonProducts));

        $this->call('pokemon:import', ['file' => storage_path('tcg-processed.json')]);

        $this->info('All operations completed successfully!');
        return Command::SUCCESS;
    }
}
