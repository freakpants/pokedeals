<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use TCGdex\TCGdex;
use Illuminate\Support\Facades\DB;

class PokemonSeriesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
            // Fetch German sets
            $tcgdex = new TCGdex("de");
            $de_series = $tcgdex->fetchSeries();
    
            // Fetch English sets
            $tcgdex = new TCGdex("en");
            $en_series = $tcgdex->fetchSeries();

            // group series by identifier
            $series = [];
            foreach ($en_series as $en) {
                // loop the german set, selecting the de if its there
                $current_de = null;
                foreach ($de_series as $de) {
                    if($de->id == $en->id) {
                        $current_de = $de;
                        break;
                    }
                }
                if($current_de) {
                    // select the object
                    $series[$en->id] = [
                        "name_de" => $current_de->name,
                        "name_en" => $en->name,
                        "name_ja" => null
                    ];
                } else {
                    $series[$en->id] = [
                        "name_de" => null,
                        "name_en" => $en->name,
                        "name_ja" => null
                    ];

                }

            }

            // insert into series table
            foreach ($series as $id => $data) {
                DB::table('pokemon_series')->insert([
                    'id' => $id,
                    'name_de' => $data['name_de'],
                    'name_en' => $data['name_en'],
                    'name_ja' => $data['name_ja']
                ]);
            }


    }
}
