<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ExternalShopsSeeder extends Seeder
{
    public function run()
    {
        $shops = [
            [
                'name' => 'TCG Shop',
                'base_url' => 'https://tcgshop.ch',
                'category_urls' => json_encode([
                    'https://www.tcgshop.ch/produkt-kategorie/pokemon/pokemon-en/booster-displays-pokemon-en/',
                    'https://www.tcgshop.ch/produkt-kategorie/pokemon/pokemon-en/decks-boxen-pokemon-en/',
                    'https://www.tcgshop.ch/produkt-kategorie/pokemon/pokemon-de/booster-displays-pokemon-de/',
                    'https://www.tcgshop.ch/produkt-kategorie/pokemon/pokemon-de/decks-boxen-pokemon-de/',
                    'https://www.tcgshop.ch/produkt-kategorie/pokemon/pokemon-jp/booster-displays-pokemon-jp/',
                    'https://www.tcgshop.ch/produkt-kategorie/pokemon/pokemon-jp/decks-boxen-pokemon-jp/',
                ]),
                'image' => 'tcgshop.png',
                'shop_type' => 'woocommerce',       
            ],
            [
                'name' => 'The Uncommon Shop',
                'base_url' => 'https://theuncommonshop.ch',
                'category_urls' => json_encode(['https://theuncommonshop.ch/shop/Top-Trainer-Boxen-c41085180',
                    'https://theuncommonshop.ch/shop/Booster-Packs-c129994504',
                    'https://theuncommonshop.ch/shop/Booster-Displays-c37574011',
                    'https://theuncommonshop.ch/shop/Boxen-&-Kollektionen-c123972502',
                    'https://theuncommonshop.ch/shop/Tins-c123971257',
                    'https://theuncommonshop.ch/shop/Blister-Packs-c129993752',
                    'https://theuncommonshop.ch/shop/Decks-c37574009'
                ]),
                'image' => 'theuncommonshop.png',
                'shop_type' => 'ecwid', 
            ],
            [
                'name' => 'Softridge',
                'base_url' => 'https://www.softridge.ch',
                'category_urls' => json_encode(['https://www.softridge.ch/api/shop/products?loadingType=79&languageId=2&navigationId=25783&filterByAllCategories=True&onlineExclusive=&displayType=1&sort=6&filter=null']),
                'image' => 'softridge.png',
                'shop_type' => 'softridge',
            ],
            [
                'name' => 'GoodGames Bern',
                'base_url' => 'https://www.goodgamesbern.ch',
                'category_urls' => json_encode(['https://www.goodgamesbern.ch/pokemon/?features_hash=7-155&items_per_page=2000&full_render=true&result_ids=pagination_contents&is_ajax=1']),
                'image' => 'goodgamesbern.png',
                'shop_type' => 'cs-cart',
            ],
            [
                'name' => 'RyuLand',
                'base_url' => 'https://ryu.land',
                'category_urls' => null,
                'image' => 'ryuland.png',
                'shop_type' => 'shopify',
            ],
            [
                'name' => 'world of games',
                'base_url' => 'https://www.wog.ch',
                'category_urls' => json_encode(['https://www.wog.ch/index.cfm/developers/type/Toys/developer/7688']),
                'image' => 'wog.png',
                'shop_type' => 'wog'
            ],
            [   
                'name' => 'Kidz.ch',
                'base_url' => 'https://www.kidz.ch',
                'category_urls' => json_encode(['https://kidz.ch/collections/lizenz-pokemon?filter.p.vendor=Pok%C3%A9mon&filter.v.availability=1&filter.v.price.gte=&filter.v.price.lte=&sort_by=best-selling']),
                'image' => 'kidz.png',
                'shop_type' => 'kidz',
            ],
            [
                'name' => 'Fluxed',
                'base_url' => 'https://www.fluxed.ch',
                'category_urls' => null,
                'image' => 'fluxed.png',
                'shop_type' => 'shopify',
            ],
            [
                'name' => 'GameGalaxy',
                'base_url' => 'https://gamegalaxy.ch',
                'category_urls' => json_encode(['https://gamegalaxy.ch/collections/pokemon?filter.v.availability=1&filter.v.price.gte=&filter.v.price.lte=&sort_by=best-selling']),
                'image' => 'gamegalaxy.png',
                'shop_type' => 'galaxy',
            ],
            [
                'name' => 'TwoMoons',
                'base_url' => 'https://www.twomoons.ch',
                'category_urls' => json_encode(['https://www.twomoons.ch/pokemon-tcg-karten-kaufen/']),
                'image' => 'twomoons.png',
                'shop_type' => 'shopware',
            ],
            [   
                'name' => 'Laschocards',
                'base_url' => 'https://laschocards.ch',
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
                'base_url' => 'https://pokecard.store',
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
                'base_url' => 'https://collectorsdeal.ch',
                'category_urls' => null,
                'image' => 'collectorsdeal.png',
                'shop_type' => 'shopify',
            ],
            [
                'name' => 'Poké Swiss',
                'base_url' => 'https://poke-swiss.ch',
                'category_urls' => null,
                'image' => 'pokeswiss.png',
                'shop_type' => 'shopify',
            ],
            [
                'name' => 'Kabooom',
                'base_url' => 'https://shop.kabooom.ch',
                'category_urls' => json_encode(['https://shop.kabooom.ch/trading-card-games-pokemon-9338/','https://shop.kabooom.ch/trading-card-games-tcg-preorders-153547/']),
                'image' => 'kabooom.png',
                'shop_type' => 'websell',
            ],
            [
                'name' => 'The Mana Shop',
                'base_url' => 'https://themanashop.ch',
                'category_urls' => json_encode(['https://themanashop.ch/en/205-pokemon?id_category=205']),
                'image' => 'themanashop.png',
                'shop_type' => 'prestashop',
            ],
            [
                'name' => 'Toytans',
                'base_url' => 'https://toytans.ch',
                'category_urls' => json_encode(['https://www.toytans.ch/en/245-pokemon']),
                'image' => 'toytans.png',
                'shop_type' => 'prestashop',
            ],
            [
                'name' => 'Maro Games',
                'base_url' => 'https://www.maro-shop.ch',
                'category_urls' => null,
                'image' => 'marogames.png',
                'shop_type' => 'shopify',
            ],
            [
                'name' => 'Card Treasure',
                'base_url' => 'https://cardtreasure.ch',
                'category_urls' => null,
                'image' => 'cardtreasure.png',
                'shop_type' => 'shopify',
            ],
            [
                'name' => 'Carab',
                'base_url' => 'https://carab.ch',
                'category_urls' => json_encode(['https://www.carab.ch/shop?category=3459']),
                'image' => 'carab.png',
                'shop_type' => 'pimcore',
            ],
            [
                'name' => 'Spielezar',
                'base_url' => 'https://spielezar.ch',
                'category_urls' => json_encode(['https://www.spielezar.ch/tcg/pokemon?filter=eyJmaWx0ZXJfMyI6WyIxIl19']),
                'image' => 'spielezar.png',
                'shop_type' => 'spielezar',
            ]
        ];

        foreach ($shops as $shop) {
            if (!DB::table('external_shops')->where('base_url', $shop['base_url'])->exists()) {
                DB::table('external_shops')->insert($shop);
            }
        }
    }
}
