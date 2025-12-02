<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class UserHealthRestrictionTableSeeder extends Seeder
{
    public function run()
    {
        DB::table('userHealthRestriction')->insert([
            ['user_id' => 3, 'allergen_id' => 1, 'hasDiabetes' => true],
            ['user_id' => 2, 'allergen_id' => 2, 'hasDiabetes' => false],
        ]);
    }
}