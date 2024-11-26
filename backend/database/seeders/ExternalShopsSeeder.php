<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ExternalShopsSeeder extends Seeder
{
    public function run()
    {
        DB::table('external_shops')->insert([
            [
                'name' => 'Skyspell',
                'base_url' => 'https://www.skyspell.ch',
                'image' => 'skyspell.png',
            ],
            [
                'name' => 'Pikaversum',
                'base_url' => 'https://www.pikaversum.ch',
                'image' => 'pikaversum.png',
            ],
            [
                'name' => 'Pokecard Store',
                'base_url' => 'https://pokecard.store/',
                'image' => 'pokecardstore.png',
            ],
            [
                'name' => 'Zadoys',
                'base_url' => 'https://www.zadoys.ch',
                'image' => 'zadoys.png',
            ],
            [
                'name' => 'Collectors Deal',
                'base_url' => 'https://collectorsdeal.ch/',
                'image' => 'collectorsdeal.png',
            ],
            [
                'name' => 'RyuLand',
                'base_url' => 'https://ryu.land/',
                'image' => 'ryuland.png',
            ],
        ]);
    }
}
