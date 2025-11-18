<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use PHPUnit\Framework\Constraint\Count;

class DatabaseSeeder extends Seeder
{
    public function run()
    {
        $this->call([
            CountyTableSeeder::class,
            CityTableSeeder::class,
            ClassTableSeeder::class,
            GroupTableSeeder::class,
            RfidCardTableSeeder::class,
            UserTableSeeder::class,
            
        ]);
    }
}