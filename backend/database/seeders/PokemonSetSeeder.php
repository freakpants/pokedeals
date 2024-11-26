<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\PokemonSet;
use TCGdex\TCGdex;

class PokemonSetSeeder extends Seeder
{
    public function run()
    {

    // Fetch German sets
    $tcgdex = new TCGdex("de");
    $de_sets = $tcgdex->fetchSets();

    // Fetch English sets
    $tcgdex = new TCGdex("en");
    $en_sets = $tcgdex->fetchSets();

    // Index English sets by ID for faster lookups
    $en_sets_indexed = collect($en_sets)->keyBy(fn($set) => $set->id);

    // Combine sets
    $sets = collect($de_sets)->map(function ($set) use ($en_sets_indexed, $tcgdex) {
        $en_set = $en_sets_indexed->get($set->id);
        // create a set_identifier from the english name
        $set_identifier = strtolower(str_replace(' ', '_', $en_set->name));
        // also replace _& with nothing
        $set_identifier = str_replace('&', '', $set_identifier);
        if ($en_set) {
            // fetch the full set so we can access release date
            $en_set = $tcgdex->fetch('sets', $en_set->id);
            return [
                'id' => $set->id,
                'set_identifier' => $set_identifier,
                'title_de' => $set->name,
                'title_en' => $en_set->name,    
                'release_date' => $en_set->releaseDate,

            ];
        }
        return null; // Skip if no English equivalent
        })->filter(); // Remove null entries

        // manually add prismatic evolutions
        $sets->push([
            'id' => 'sv08.5',
            'set_identifier' => 'prismatic_evolutions',
            'title_de' => 'Prismatische Evolutionen',
            'title_en' => 'Prismatic Evolutions',
            'release_date' => '2025-01-17',
        ]);

        // Create a set for each entry
        foreach ($sets as $set) {
            PokemonSet::create($set);
        }

    }
}
