<?php

namespace Database\Seeders;

use App\Models\Meal;
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
            /*CountyTableSeeder::class,
            CityTableSeeder::class,

            GroupTableSeeder::class,
            RfidCardTableSeeder::class,
            MealTableSeeder::class,
            MenuItemTableSeeder::class,
            AllergenTableSeeder::class,
            UserHealthRestrictionTableSeeder::class,
            IngredientTableSeeder::class,
            IngredientAllergenTableSeeder::class,
            InvoiceTableSeeder::class,
            MealIngredientTableSeeder::class,*/
            
        ]);

         DB::statement('SET FOREIGN_KEY_CHECKS=1;');
    }
}