<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CityTableSeeder extends Seeder
{
    public function run()
    {
        DB::table('city')->insert([
            ['cityName' => 'Budapest', 'zipCode' => 6000, 'county_id' => 2],
            ['cityName' => 'Szeged', 'zipCode' => 6010, 'county_id' => 1],
        ]);
    }
}