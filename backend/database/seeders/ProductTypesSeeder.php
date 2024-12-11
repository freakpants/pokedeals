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

        DB::table('product_types')->insert([
            'product_type' => 'display_box',
            'pack_count' => 36,
            'mixed_sets' => false,
        ]);

        DB::table('product_types')->insert([
            'product_type' => 'half_booster_box',
            'pack_count' => 18,
            'mixed_sets' => false,
        ]);

        DB::table('product_types')->insert([
            'product_type' => 'booster_bundle',
            'pack_count' => 6,
            'mixed_sets' => false,
        ]);

        DB::table('product_types')->insert([
            'product_type' => 'booster_pack',
            'pack_count' => 1,
            'mixed_sets' => false,
        ]);

        DB::table('product_types')->insert([
            'product_type' => 'sleeved_booster_case',
            'pack_count' => 24,
            'mixed_sets' => false,
        ]);

        DB::table('product_types')->insert([
            'product_type' => 'sleeved_booster',
            'pack_count' => 1,
            'mixed_sets' => false,
        ]);

        DB::table('product_types')->insert([
            'product_type' => 'single_blister',
            'pack_count' => 1,
            'mixed_sets' => false,
        ]);

        DB::table('product_types')->insert([
            'product_type' => 'three_pack_blister',
            'pack_count' => 3,
            'mixed_sets' => false,
        ]);

        DB::table('product_types')->insert([
            'product_type' => 'elite_trainer_box',
            'pack_count' => 9,
            'mixed_sets' => false,
        ]);

        DB::table('product_types')->insert([
            'product_type' => 'stacking_tin',
            'pack_count' => 3,
            'mixed_sets' => false,
        ]);

        DB::table('product_types')->insert([
            'product_type' => 'pencil_case',
            'pack_count' => 2,
            'mixed_sets' => false,
        ]);

        DB::table('product_types')->insert([
            'product_type' => 'tin',
            'pack_count' => 4,
            'mixed_sets' => false,
        ]);

        DB::table('product_types')->insert([
            'product_type' => 'mini_tin',
            'pack_count' => 2,
            'mixed_sets' => false,
        ]);

        DB::table('product_types')->insert([
            'product_type' => 'poke_ball_tin',
            'pack_count' => 3,
            'mixed_sets' => false,
        ]);

        // DB::table('product_types')->insert([
        //     'product_type' => 'ultra_premium_collection',
        //     'pack_count' => 1,
        //     'mixed_sets' => false,
        // ]);

        // DB::table('product_types')->insert([
        //     'product_type' => 'premium_collection',
        //     'pack_count' => 1,
        //     'mixed_sets' => false,
        // });

        DB::table('product_types')->insert([
            'product_type' => 'build_battle_stadium',
            'pack_count' => 11,  
            'mixed_sets' => false,
        ]);

        DB::table('product_types')->insert([
            'product_type' => 'build_battle_box',
            'pack_count' => 4,
            'mixed_sets' => false,
        ]);

        // DB::table('product_types')->insert([
        //     'product_type' => 'premium_figure_collection',
        //     'pack_count' => 1,
        //     'mixed_sets' => false,
        // ]);

        DB::table('product_types')->insert([
            'product_type' => 'poster_collection',
            'pack_count' => 3,
            'mixed_sets' => false,
        ]);

        DB::table('product_types')->insert([
            'product_type' => 'binder_collection',
            'pack_count' => 4,
            'mixed_sets' => false,
        ]);

        // DB::table('product_types')->insert([
        //     'product_type' => 'special_illustration_collection',
        //     'pack_count' => 1,
        //     'mixed_sets' => false,
        // ]);

        // DB::table('product_types')->insert([
        //     'product_type' => 'illustration_collection',
        //     'pack_count' => 1,
        //     'mixed_sets' => false,
        // ]);

        DB::table('product_types')->insert([
            'product_type' => 'tech_sticker_collection', 
            'pack_count' => 3,
            'mixed_sets' => false,
        ]);

        DB::table('product_types')->insert([
            'product_type' => 'surprise_box',
            'pack_count' => 4,
            'mixed_sets' => false,
        ]);




    }
}
