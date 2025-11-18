<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CountyTableSeeder extends Seeder
{
    public function run()
    {
        DB::table('county')->insert([
            ['countyName' => 'Pest'],
            ['countyName' => 'Csongrád-Csanád'],
        ]);
    }
}