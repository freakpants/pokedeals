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
                'name' => 'TwoMoons',
                'base_url' => 'https://www.twomoons.ch/',
                'category_urls' => json_encode(['https://www.twomoons.ch/pokemon-tcg-karten-kaufen/']),
                'image' => 'twomoons.png',
                'shop_type' => 'shopware',
            ],
            [   
                'name' => 'Laschocards',
                'base_url' => 'https://laschocards.ch/',
                'category_urls' => null,
                'image' => 'laschocards.png',
                'shop_type' => 'shopify',
            ],
            [   
                'name' => 'Card maniac',
                'base_url' => 'https://www.cardmaniac.ch',
                'category_urls' => null,
                'image' => 'cardmaniac.png',
                'shop_type' => 'shopify',
            ],
            [   
                'name' => 'Royal Cards',
                'base_url' => 'https://www.royalcards.ch',
                'category_urls' => null,
                'image' => 'royalcards.png',
                'shop_type' => 'shopify',
            ],
            [   
                'name' => 'TradingCardCave',
                'base_url' => 'https://www.tradingcardcave.ch',
                'category_urls' => null,
                'image' => 'tradingcardcave.png',
                'shop_type' => 'shopify',
            ],
            [
                'name' => 'Sparkleaf TCG',
                'base_url' => 'https://www.sparkleaf.ch',
                'category_urls' => null,
                'image' => 'sparkleaf.png',
                'shop_type' => 'shopify',
            ],
            [
                'name' => 'Wild-Cards',
                'base_url' => 'https://www.wild-cards.ch',
                'category_urls' => null,
                'image' => 'wildcards.png',
                'shop_type' => 'shopify',
            ],
            [
                'name' => 'Skyspell',
                'base_url' => 'https://www.skyspell.ch',
                'category_urls' => null,
                'image' => 'skyspell.png',
                'shop_type' => 'shopify',
            ],
            [
                'name' => 'Pikaversum',
                'base_url' => 'https://www.pikaversum.ch',
                'category_urls' => null,
                'image' => 'pikaversum.png',
                'shop_type' => 'shopify',
            ],
            [
                'name' => 'Pokecard Store',
                'base_url' => 'https://pokecard.store/',
                'category_urls' => null,
                'image' => 'pokecardstore.png',
                'shop_type' => 'shopify',
            ],
            [
                'name' => 'Zadoys',
                'base_url' => 'https://www.zadoys.ch',
                'category_urls' => null,
                'image' => 'zadoys.png',
                'shop_type' => 'shopify',
            ],
            [
                'name' => 'Collectors Deal',
                'base_url' => 'https://collectorsdeal.ch/',
                'category_urls' => null,
                'image' => 'collectorsdeal.png',
                'shop_type' => 'shopify',
            ],
            [
                'name' => 'RyuLand',
                'base_url' => 'https://ryu.land/',
                'category_urls' => null,
                'image' => 'ryuland.png',
                'shop_type' => 'shopify',
            ],
            [
                'name' => 'Poké Swiss',
                'base_url' => 'https://poke-swiss.ch/',
                'category_urls' => null,
                'image' => 'pokeswiss.png',
                'shop_type' => 'shopify',
            ],
            [
                'name' => 'Kabooom',
                'base_url' => 'https://shop.kabooom.ch/',
                'category_urls' => json_encode(['https://shop.kabooom.ch/trading-card-games-pokemon-9338/','https://shop.kabooom.ch/trading-card-games-tcg-preorders-153547/']),
                'image' => 'kabooom.png',
                'shop_type' => 'websell',
            ],
            [
                'name' => 'The Mana Shop',
                'base_url' => 'https://themanashop.ch/',
                'category_urls' => json_encode(['https://themanashop.ch/en/205-pokemon']),
                'image' => 'themanashop.png',
                'shop_type' => 'prestashop',
            ],
            [
                'name' => 'The uncommon shop',
                'base_url' => 'https://theuncommonshop.ch/',
                'category_urls' => json_encode(['https://theuncommonshop.ch/collections/pokemon']),
                'image' => 'theuncommonshop.png',
                'shop_type' => 'ecwid',
            ]
        ]);
    }
}
