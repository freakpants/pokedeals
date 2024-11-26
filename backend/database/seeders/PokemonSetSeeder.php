<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\PokemonSet;

class PokemonSetSeeder extends Seeder
{
    public function run()
    {

        // Prismatic Evolutions
        PokemonSet::create([
            'identifier' => 'prismatic_evolutions',
            'title_en' => 'Prismatic Evolutions',
            'title_de' => 'Prismatische Evolutionen',
            'title_jp' => 'Terastal Festival',
            'shortcode' => 'sv08.5',
            'card_code_en' => 'PRE EN',
            'card_code_de' => 'PRE DE',
            'card_code_jp' => 'sv8a',
        ]);

        PokemonSet::create([
            'identifier' => 'surging_sparks',
            'title_en' => 'Surging Sparks',
            'title_de' => 'Stürmische Funken',
            'title_jp' => 'super electric breaker',
            'shortcode' => 'sv08',
            'card_code_en' => 'SSP EN',
            'card_code_de' => 'SSP DE',
            'card_code_jp' => 'sv8',
        ]);


        // stellar crown
        PokemonSet::create([
            'identifier' => 'stellar_crown',
            'title_en' => 'Stellar Crown',
            'title_de' => 'Sternenkrone',
            'title_jp' => 'Stella Miracle',
            'shortcode' => 'sv07',
            'card_code_en' => 'SCR EN',
            'card_code_de' => 'SCR DE',
            'card_code_jp' => 'sv7',
        ]);

        // Paradise Dragona (Japanese only)
        PokemonSet::create([
            'identifier' => 'paradise_dragona',
            'title_jp' => 'Paradise Dragona',
            'card_code_jp' => 'sv7a',
        ]);

        // Shrouded Fable
        PokemonSet::create([
            'identifier' => 'shrouded_fable',
            'title_en' => 'Shrouded Fable',
            'title_de' => 'Nebel der Sagen',
            'title_jp' => 'Night Wanderer',
            'shortcode' => 'sv07.5',
            'card_code_en' => 'SFA EN',
            'card_code_de' => 'SFA DE',
            'card_code_jp' => 'sv7a',
        ]);

        // Twilight Masquerade
        PokemonSet::create([
            'identifier' => 'twilight_masquerade',
            'title_en' => 'Twilight Masquerade',
            'title_de' => 'Zwielichtmaskerade',
            'title_jp' => 'Transformation Masquerade',
            'shortcode' => 'sv06',
            'card_code_en' => 'TWM EN',
            'card_code_de' => 'TWM DE',
            'card_code_jp' => 'sv6',
        ]);

        // Temporal Forces
        PokemonSet::create([
            'identifier' => 'temporal_forces',
            'title_en' => 'Temporal Forces',
            'title_de' => 'Gewalten der Zeit',
            'title_jp' => 'Cyber Judge',
            'shortcode' => 'sv05',
            'card_code_en' => 'TF EN',
            'card_code_de' => 'TF DE',
            'card_code_jp' => 'sv5',
        ]);



    }
}
