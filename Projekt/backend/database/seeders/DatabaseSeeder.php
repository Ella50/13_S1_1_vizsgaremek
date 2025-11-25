<?php

namespace Database\Seeders;

use App\Models\Meal;
use Illuminate\Database\Seeder;
use Illuminate\Validation\Rules\In;
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
            MealTableSeeder::class,
            MenuItemTableSeeder::class,
            AllergenTableSeeder::class,
            UserHealthRestrictionTableSeeder::class,
            IngredientTableSeeder::class,
            IngredientAllergenTableSeeder::class,
            
        ]);
    }
}