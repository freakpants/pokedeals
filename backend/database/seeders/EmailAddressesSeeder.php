<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class EmailAddressesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('email_addresses')->insert([
            ['email' => 'freakpants@gmail.com'],
            ['email' => 'leimann.m@gmail.com'],
            ['email' => 'luny.92@hotmail.com'],
            ['email' => 'Naruto7@gmx.ch'],
            ['email' => 'Cedibat@hispeed.ch'],
            ['email' => 'lawrence.morillo@gmail.com'],
            ['email' => 'noah.paixao@gmail.com'],
            ['email' => 'janbaumann.basi@gmail.com'],
        ]);
    }
}
