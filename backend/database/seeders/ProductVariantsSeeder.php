<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Enums\ProductTypes;

class ProductVariantsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {

        DB::table('pokemon_product_variants')->insert([
            'product_type' => ProductTypes::UltraPremiumCollection->value,
            'en_short' => 'terapagos_ex_ultra_premium_collection',
            'de_strings' => json_encode(['']),
            'en_strings' => json_encode(['terapagos ex']),
            'en_name' => 'Terapagos ex Ultra Premium Collection'
        ]);

        DB::table('pokemon_product_variants')->insert([
            'product_type' => ProductTypes::UltraPremiumCollection->value,
            'en_short' => 'celebrations_ultra_premium_collection',
            'de_strings' => json_encode(['']),
            'en_strings' => json_encode(['celebrations']),
            'en_name' => 'Celebrations Ultra Premium Collection'
        ]);

        DB::table('pokemon_product_variants')->insert([
            'product_type' => ProductTypes::UltraPremiumCollection->value,
            'en_short' => 'charizard_ultra_premium_collection',
            'de_strings' => json_encode(['']),
            'en_strings' => json_encode(['charizard']),
            'en_name' => 'Charizard Ultra Premium Collection'
        ]);

        DB::table('pokemon_product_variants')->insert([
            'product_type' => ProductTypes::UltraPremiumCollection->value,
            'en_short' => '151_ultra_premium_collection',
            'de_strings' => json_encode(['']),
            'en_strings' => json_encode(['151']),
            'en_name' => '151 Ultra Premium Collection'
        ]);

        DB::table('pokemon_product_variants')->insert([
            'product_type' => ProductTypes::PremiumCollection->value,
            'en_short' => 'charizard_ex_premium_collection',
            'de_strings' => json_encode(['glurak ex premium collection']),
            'en_strings' => json_encode(['charizard ex premium collection']),
            'en_name' => 'Charizard EX Premium Collection'
        ]);

        DB::table('pokemon_product_variants')->insert([
            'product_type' => ProductTypes::PremiumCollection->value,
            'en_short' => 'kleaveor_vstar_premium_collection',
            'de_strings' => json_encode(['']),
            'en_strings' => json_encode(['vstar premium collection kleavor','kleavor vstar' ]),
            'en_name' => 'Kleavor VSTAR Premium Collection'
        ]);

        DB::table('pokemon_product_variants')->insert([
            'product_type' => ProductTypes::PremiumCollection->value,
            'en_short' => 'ogerpon_ex_premium_collection',
            'de_strings' => json_encode(['']),
            'en_strings' => json_encode(['ogerpon ex']),
            'en_name' => 'Ogerpon ex Premium Collection'
        ]);

        DB::table('pokemon_product_variants')->insert([
            'product_type' => ProductTypes::PremiumCollection->value,
            'en_short' => 'combined_powers_premium_collection',
            'de_strings' => json_encode(['']),
            'en_strings' => json_encode(['combined powers']),
            'en_name' => 'Combined Powers Premium Collection'
        ]);

        DB::table('pokemon_product_variants')->insert([
            'product_type' => ProductTypes::PremiumCollection->value,
            'en_short' => 'meowscarada_ex_premium_collection',
            'de_strings' => json_encode(['']),
            'en_strings' => json_encode(['meowscarada']),
            'en_name' => 'Meowscarada ex Premium Collection'
        ]);

        DB::table('pokemon_product_variants')->insert([
            'product_type' => ProductTypes::PremiumCollection->value,
            'en_short' => 'quaquaval_ex_premium_collection',
            'de_strings' => json_encode(['']),
            'en_strings' => json_encode(['quaquaval']),
            'en_name' => 'Quaquaval ex Premium Collection ex Premium Collection'
        ]);

        DB::table('pokemon_product_variants')->insert([
            'product_type' => ProductTypes::PremiumCollection->value,
            'en_short' => 'skeledirge_ex_premium_collection',
            'de_strings' => json_encode(['']),
            'en_strings' => json_encode(['skeledirge']),
            'en_name' => 'Skeledirge ex Premium Collection ex Premium Collection'
        ]);

        DB::table('pokemon_product_variants')->insert([
            'product_type' => ProductTypes::PremiumCollection->value,
            'en_short' => 'combined_powers_premium_collection',
            'de_strings' => json_encode(['']),
            'en_strings' => json_encode(['combined powers']),
            'en_name' => 'Combined Powers Premium Collection'
        ]);

        DB::table('pokemon_product_variants')->insert([
            'product_type' => ProductTypes::PremiumCollection->value,
            'en_short' => 'armourage_ex_premium_collection',
            'de_strings' => json_encode(['']),
            'en_strings' => json_encode(['armourage']),
            'en_name' => 'Armourage ex Premium Collection'
        ]);

        DB::table('pokemon_product_variants')->insert([
            'product_type' => ProductTypes::PremiumCollection->value,
            'en_short' => 'radiant_eevee_premium_collection',
            'de_strings' => json_encode(['']),
            'en_strings' => json_encode(['radiant eevee']),
            'en_name' => 'Radiant Eevee Premium Collection'
        ]);

        DB::table('pokemon_product_variants')->insert([
            'product_type' => ProductTypes::PremiumCollection->value,
            'en_short' => 'flareon_vmax_premium_collection',
            'de_strings' => json_encode(['']),
            'en_strings' => json_encode(['flareon']),
            'en_name' => 'Flareon VMAX Premium Collection'
        ]);

        DB::table('pokemon_product_variants')->insert([
            'product_type' => ProductTypes::PremiumCollection->value,
            'en_short' => 'jolteon_vmax_premium_collection',
            'de_strings' => json_encode(['']),
            'en_strings' => json_encode(['jolteon']),
            'en_name' => 'Jolteon VMAX Premium Collection'
        ]);

        DB::table('pokemon_product_variants')->insert([
            'product_type' => ProductTypes::PremiumCollection->value,
            'en_short' => 'vaporeon_vmax_premium_collection',
            'de_strings' => json_encode(['']),
            'en_strings' => json_encode(['vaporeon']),
            'en_name' => 'Vaporeon VMAX Premium Collection'
        ]);

        DB::table('pokemon_product_variants')->insert([
            'product_type' => ProductTypes::PremiumCollection->value,
            'en_short' => 'tera_brawlers_premium_collection',
            'de_strings' => json_encode(['']),
            'en_strings' => json_encode(['tera brawlers']),
            'en_name' => 'Tera Brawlers Premium Collection'
        ]);

        DB::table('pokemon_product_variants')->insert([
            'product_type' => ProductTypes::MiniTin->value,
            'en_short' => 'vibrant_paldea_goomy_ceruledge_mini_tin',
            'de_strings' => json_encode(['mini tins juni 2024','farbenfrohes paldea']),
            'en_strings' => json_encode(['goomy','ceruledge']),
            'en_name' => 'Vibrant Paldea Mini Tin (Goomy & Ceruledge)'
        ]);

        DB::table('pokemon_product_variants')->insert([
            'product_type' => ProductTypes::MiniTin->value,
            'en_short' => 'vibrant_paldea_pachirisu_palafin_mini_tin',
            'de_strings' => json_encode(['mini tins juni 2024','farbenfrohes paldea']),
            'en_strings' => json_encode(['pachirisu','palafin']),
            'en_name' => 'Vibrant Paldea Mini Tin (Pachirisu & Palafin)'
        ]);

        DB::table('pokemon_product_variants')->insert([
            'product_type' => ProductTypes::MiniTin->value,
            'en_short' => 'vibrant_paldea_leafeon_arboliva_mini_tin',
            'de_strings' => json_encode(['mini tins juni 2024','farbenfrohes paldea']),
            'en_strings' => json_encode(['leafeon','arboliva']),
            'en_name' => 'Vibrant Paldea Mini Tin (Leafeon & Arboliva)'
        ]);

        DB::table('pokemon_product_variants')->insert([
            'product_type' => ProductTypes::MiniTin->value,
            'en_short' => 'vibrant_paldea_espathra_ampharos_mini_tin',
            'de_strings' => json_encode(['mini tins juni 2024','farbenfrohes paldea']),
            'en_strings' => json_encode(['espathra','ampharos']),
            'en_name' => 'Vibrant Paldea Mini Tin (Espathra & Ampharos)'
        ]);

        DB::table('pokemon_product_variants')->insert([
            'product_type' => ProductTypes::MiniTin->value,
            'en_short' => 'vibrant_paldea_oricorio_dachsbun_mini_tin',
            'de_strings' => json_encode(['mini tins juni 2024','farbenfrohes paldea']),
            'en_strings' => json_encode(['oricorio','dachsbun']),
            'en_name' => 'Vibrant Paldea Mini Tin (Oricorio & Dachsbun)'
        ]);

        DB::table('pokemon_product_variants')->insert([
            'product_type' => ProductTypes::DoubleBlister->value,
            'en_short' => 'tornadus_thundurus_landorus_double_blister',
            'de_strings' => json_encode(['']),
            'en_strings' => json_encode(['tornadus','thundurus','landorus']),
            'en_name' => 'Tornadus, Thundurus & Landorus Cards with 2 Booster Packs & Coin'
        ]);

        // koraidon etb
        DB::table('pokemon_product_variants')->insert([
            'product_type' => ProductTypes::EliteTrainerBox->value,
            'en_short' => 'koraidon_etb',
            'de_strings' => json_encode(['']),
            'en_strings' => json_encode(['koraidon']),
            'en_name' => 'Scarlet & Violet Elite Trainer Box (Koraidon)'
        ]);

        // miraidon etb
        DB::table('pokemon_product_variants')->insert([
            'product_type' => ProductTypes::EliteTrainerBox->value,
            'en_short' => 'miraidon_etb',
            'de_strings' => json_encode(['']),
            'en_strings' => json_encode(['miraidon']),
            'en_name' => 'Scarlet & Violet Elite Trainer Box (Miraidon)'
        ]);

        // iron leaves etb
        DB::table('pokemon_product_variants')->insert([
            'product_type' => ProductTypes::EliteTrainerBox->value,
            'en_short' => 'iron_leaves_etb',
            'de_strings' => json_encode(['eisendorn', 'Temporal Forces ETB - DE - Grün']),
            'en_strings' => json_encode(['iron leaves', 'Temporal Forces ETB - EN - Grün']),
            'en_name' => 'Scarlet & Violet-Temporal Forces Elite Trainer Box (Iron Leaves)'
        ]);

        // walking wake etb
        DB::table('pokemon_product_variants')->insert([
            'product_type' => ProductTypes::EliteTrainerBox->value,
            'en_short' => 'walking_wake_etb',
            'de_strings' => json_encode(['windewoge', 'Temporal Forces ETB - DE - blau']),
            'en_strings' => json_encode(['walking wake', 'Temporal Forces ETB - EN - blau']),
            'en_name' => 'Scarlet & Violet-Temporal Forces Elite Trainer Box (Walking Wake)'
        ]);

        // iron valiant etb
        DB::table('pokemon_product_variants')->insert([
            'product_type' => ProductTypes::EliteTrainerBox->value,
            'en_short' => 'iron_valiant_etb',
            'de_strings' => json_encode(['eisenkrieger', 'Paradox Rift ETB - Deutsch - Grün']),
            'en_strings' => json_encode(['iron valiant', 'Paradox Rift ETB - Englisch - Grün']),
            'en_name' => 'Scarlet & Violet-Paradox Rift Elite Trainer Box (Iron Valiant)'
        ]);

        // roaring moon etb
        DB::table('pokemon_product_variants')->insert([
            'product_type' => ProductTypes::EliteTrainerBox->value,
            'en_short' => 'roaring_moon_etb',
            'de_strings' => json_encode(['donnersichel', 'Paradox Rift ETB - Deutsch - Blau']),
            'en_strings' => json_encode(['roaring moon', 'Paradox Rift ETB - Englisch - Blau']),
            'en_name' => 'Scarlet & Violet-Paradox Rift Elite Trainer Box (Roaring Moon)'
        ]);

        // morpeko v-union premium collection
        DB::table('pokemon_product_variants')->insert([
            'product_type' => ProductTypes::PremiumCollection->value,
            'en_short' => 'morpeko_vunion_premium_collection',
            'de_strings' => json_encode(['']),
            'en_strings' => json_encode(['morpeko']),
            'en_name' => 'Morpeko V-Union Premium Collection'
        ]);

        // Crown Zenith Special Collection (Unown V & Lugia V) (EN)
        DB::table('pokemon_product_variants')->insert([
            'product_type' => ProductTypes::SpecialCollection->value,
            'en_short' => 'crown_zenith_special_collection',
            'de_strings' => json_encode(['']),
            'en_strings' => json_encode(['unown v','lugia v']),
            'en_name' => 'Crown Zenith Special Collection (Unown V & Lugia V)',
            'pack_count' => 5
        ]);

    }
}
