<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class IngredientAllergenTableSeeder extends Seeder
{
    public function run()
    {
        DB::table('ingredient_allergen')->insert([
            ['allergen_id' => 1, 'ingredient_id' => 1],
            ['allergen_id' => 1, 'ingredient_id' => 2],

      ]);
    }
}