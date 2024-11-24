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
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
}
