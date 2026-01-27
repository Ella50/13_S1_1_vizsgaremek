<?php

namespace Database\Seeders;

use App\Models\Meal;
use App\Models\Price;
use Illuminate\Database\Seeder;
use Illuminate\Validation\Rules\In;
use PHPUnit\Framework\Constraint\Count;
use Illuminate\Support\Facades\DB;

class DatabaseSeeder extends Seeder
{
    public function run()
    {

        DB::statement('SET FOREIGN_KEY_CHECKS=0;');

        $this->call([
            
            ClassSeeder::class,
            CityCountySeeder::class,
            UserSeeder::class,
            IngredientSeeder::class,
            MealSeeder::class,
            MealIngredientSeeder::class,
            AllergenSeeder::class,
            IngredientAllergenSeeder::class,
            MenuItemSeeder::class,
            PriceSeeder::class,
            OrderSeeder::class,
            
        ]);

         DB::statement('SET FOREIGN_KEY_CHECKS=1;');
    }
}