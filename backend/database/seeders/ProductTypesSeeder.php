<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ProductTypesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $productTypes = [
            [
                'product_type' => 'display_box',
                'pack_count' => 36,
                'mixed_sets' => false,
                'en_name' => 'Display Box',
            ],
            [
                'product_type' => 'display_box_case',
                'pack_count' => 216,
                'mixed_sets' => false,
                'en_name' => 'Display Box Case',
            ],
            [
                'product_type' => 'half_booster_box',
                'pack_count' => 18,
                'mixed_sets' => false,
                'en_name' => 'Half Booster Box',
            ],
            [
                'product_type' => 'japanese_display_box',
                'pack_count' => 10,
                'mixed_sets' => false,
                'en_name' => 'Japanese Display Box',
            ],
            [
                'product_type' => 'booster_bundle_case',
                'pack_count' => 60,
                'mixed_sets' => false,
                'en_name' => 'Booster Bundle Case',
            ],
            [
                'product_type' => 'booster_bundle',
                'pack_count' => 6,
                'mixed_sets' => false,
                'en_name' => 'Booster Bundle',
            ],
            [
                'product_type' => 'booster_pack',
                'pack_count' => 1,
                'mixed_sets' => false,
                'en_name' => 'Booster Pack',
            ],
            [
                'product_type' => 'sleeved_booster_case',
                'pack_count' => 24,
                'mixed_sets' => false,
                'en_name' => 'Sleeved Booster Case',
            ],
            [
                'product_type' => 'sleeved_booster',
                'pack_count' => 1,
                'mixed_sets' => false,
                'en_name' => 'Sleeved Booster',
            ],
            [
                'product_type' => 'single_blister',
                'pack_count' => 1,
                'mixed_sets' => false,
                'en_name' => 'Single Blister',
            ],
            [
                'product_type' => 'three_pack_blister',
                'pack_count' => 3,
                'mixed_sets' => false,
                'en_name' => 'Three Pack Blister',
            ],
            [
                'product_type' => 'elite_trainer_box',
                'pack_count' => 9,
                'mixed_sets' => false,
                'swh_modifier' => -1,
                'en_name' => 'Elite Trainer Box',
            ],
            [
                'product_type' => 'stacking_tin',
                'pack_count' => 3,
                'mixed_sets' => false,
                'en_name' => 'Stacking Tin',
            ],
            [
                'product_type' => 'pencil_case',
                'pack_count' => 2,
                'mixed_sets' => false,
                'en_name' => 'Pencil Case', 
            ],
            [
                'product_type' => 'tin',
                'pack_count' => 4,
                'mixed_sets' => false,
                'en_name' => 'Tin',
            ],
            [
                'product_type' => 'five_booster_tin',
                'pack_count' => 5,
                'mixed_sets' => false,
                'en_name' => 'Five Booster Tin',
            ],
            [
                'product_type' => 'mini_tin',
                'pack_count' => 2,
                'mixed_sets' => false,
                'en_name' => 'Mini Tin',
            ],
            [
                'product_type' => 'poke_ball_tin',
                'pack_count' => 3,
                'mixed_sets' => false,
                'en_name' => 'Poke Ball Tin',
            ],
            [
                'product_type' => 'ultra_premium_collection',
                'pack_count' => 16,
                'mixed_sets' => false,
                'en_name' => 'Ultra-Premium Collection',
            ],
            [
                'product_type' => 'special_collection',
                'pack_count' => 4,
                'mixed_sets' => false,
                'en_name' => 'Special Collection',
            ],
            [
                'product_type' => 'super_premium_collection',
                'pack_count' => 10,
                'mixed_sets' => false,
                'en_name' => 'Super-Premium Collection',
            ],
            [
                'product_type' => 'premium_collection',
                'pack_count' => 5,
                'mixed_sets' => false,
                'en_name' => 'Premium Collection',
            ],
            [
                'product_type' => 'build_battle_stadium',
                'pack_count' => 11,  
                'mixed_sets' => false,
                'en_name' => 'Build & Battle Stadium',
            ],
            [
                'product_type' => 'build_battle_box',
                'pack_count' => 4,
                'mixed_sets' => false,
                'en_name' => 'Build & Battle Box',
            ],
            [
                'product_type' => 'collection_box',
                'pack_count' => 5,
                'mixed_sets' => false,
                'en_name' => 'Collection Box',
            ],
            [
                'product_type' => 'collection',
                'pack_count' => 4,
                'mixed_sets' => false,
                'en_name' => 'Collection',
            ],
            [
                'product_type' => 'premium_figure_collection',
                'pack_count' => 11,
                'mixed_sets' => false,
                'en_name' => 'Premium Figure Collection',
            ],
            [
                'product_type' => 'poster_collection',
                'pack_count' => 3,
                'mixed_sets' => false,
                'en_name' => 'Poster Collection',
            ],
            [
                'product_type' => 'binder_collection',
                'pack_count' => 5,
                'mixed_sets' => false,
                'en_name' => 'Binder Collection',
            ],
            [
                'product_type' => 'special_illustration_collection',
                'pack_count' => 5,
                'mixed_sets' => false,
                'en_name' => 'Special Illustration Collection',
            ],
            [
                'product_type' => 'illustration_collection',
                'pack_count' => 4,
                'mixed_sets' => false,
                'en_name' => 'Illustration Collection',
            ],
            [
                'product_type' => 'tech_sticker_collection', 
                'pack_count' => 3,
                'mixed_sets' => false,
                'en_name' => 'Tech Sticker Collection',
            ],
            [
                'product_type' => 'surprise_box',
                'pack_count' => 4,
                'mixed_sets' => false,
                'en_name' => 'Surprise Box',
            ],
            [
                'product_type' => 'pin_collection',
                'pack_count' => 3,
                'mixed_sets' => false,
                'en_name' => 'Pin Collection',
            ],
            [
                'product_type' => 'three_pack_blister_case',
                'pack_count' => 36,
                'mixed_sets' => false,
                'en_name' => 'Three Pack Blister Case',
            ],
            [
                'product_type' => 'ex_box',
                'pack_count' => 4,
                'mixed_sets' => false,
                'en_name' => 'Ex Box',
            ],
            [
                'product_type' => 'mini_tin_display',
                'pack_count' => 20,
                'mixed_sets' => false,
                'en_name' => 'Mini Tin Display',
            ],
            [
                'product_type' => 'binder_collection',
                'pack_count' => 5,
                'mixed_sets' => false,
                'en_name' => 'Binder Collection',
            ]
        ];

        foreach ($productTypes as $type) {
            if (!DB::table('product_types')->where('product_type', $type['product_type'])->exists()) {
                DB::table('product_types')->insert($type);
            }
        }
    }
}
