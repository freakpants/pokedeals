<?php

namespace App\Http\Controllers\Api;

use TCGdex\TCGdex;
use App\Http\Controllers\Controller;

class SetController extends Controller
{
    public function index()
    {
        try {
            // Fetch German sets
            $tcgdex = new TCGdex("de");
            $de_sets = $tcgdex->fetchSets();

            // Fetch English sets
            $tcgdex = new TCGdex("en");
            $en_sets = $tcgdex->fetchSets();

            // Index English sets by ID for faster lookups
            $en_sets_indexed = collect($en_sets)->keyBy(fn($set) => $set->id);

            // Combine sets
            $sets = collect($de_sets)->map(function ($set) use ($en_sets_indexed) {
                $en_set = $en_sets_indexed->get($set->id);
                // create a set_identifier from the english name
                $set_identifier = strtolower(str_replace(' ', '_', $en_set->name));
                if ($en_set) {
                    return [
                        'id' => $set->id,
                        'set_identifier' => $set_identifier,
                        'title_de' => $set->name,
                        'title_en' => $en_set->name,

                    ];
                }
                return null; // Skip if no English equivalent
            })->filter(); // Remove null entries

            return response()->json($sets->values());
        } catch (\Exception $e) {
            // Handle errors and return a meaningful response
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}
