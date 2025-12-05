<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Enums\IngredientType;

class MealIngredientTableSeeder extends Seeder
{
    public function run()
    {
        DB::table('meal_ingredient')->insert([
            ['meal_id' => 1,
            'ingredient_id' => 2,
            'amount' => 20,
            'unit' => 'kg'
            ],

            ['meal_id' => 1,
            'ingredient_id' => 1,
            'amount' => 5,
            'unit' => 'l'
            ],




      ]);
    }
}