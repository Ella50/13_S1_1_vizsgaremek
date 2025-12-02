<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AllergenTableSeeder extends Seeder
{
    public function run()
    {
        DB::table('allergen')->insert([
            ['name' => 'glutén', 'icon' => 'url/valami1'],
            ['name' => 'tej', 'icon' => 'url/valami2'],
        ]);
    }
}