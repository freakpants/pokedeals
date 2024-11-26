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
                'name' => 'Laschocards',
                'base_url' => 'https://laschocards.ch/',
                'image' => 'laschocards.png',
            ],
            [   
                'name' => 'Card maniac',
                'base_url' => 'https://www.cardmaniac.ch',
                'image' => 'cardmaniac.png',
            ],
            [   
                'name' => 'Royal Cards',
                'base_url' => 'https://www.royalcards.ch',
                'image' => 'royalcards.png',
            ],
            [   
                'name' => 'TradingCardCave',
                'base_url' => 'https://www.tradingcardcave.ch',
                'image' => 'tradingcardcave.png',
            ],
            [
                'name' => 'Sparkleaf TCG',
                'base_url' => 'https://www.sparkleaf.ch',
                'image' => 'sparkleaf.png'
            ],
            [
                'name' => 'Wild-Cards',
                'base_url' => 'https://www.wild-cards.ch',
                'image' => 'wildcards.png',
            ],
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
            [
                'name' => 'Poké Swiss',
                'base_url' => 'https://poke-swiss.ch/',
                'image' => 'pokeswiss.png',
            ]
        ]);
    }
}
