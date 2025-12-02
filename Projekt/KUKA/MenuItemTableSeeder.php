<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\Meal;
use App\Models\MenuItem;
use App\Enums\MealType;


class MenuItemTableSeeder extends Seeder
{
    public function run()
    {

        $meals = Meal::all();
        
        if ($meals->count() < 3) {
            // Ha nincs elég meal, hozzunk létre
            $this->call(MealTableSeeder::class);
            $meals = Meal::all();
        }

        // Vegyük az első 3 meal-t
        $meal1 = $meals[0]->id;
        $meal2 = $meals[1]->id;
        $meal3 = $meals[2]->id;

        DB::table('menuItem')->insert([
            [
                'day' => date('d.m.y'),
                'optionA' => $meal1,  // Létező meal ID
                'optionB' => $meal2,  // Létező meal ID
                'soup' => $meal3,     // Létező meal ID
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'day' => date('d.m.y'),
                'optionA' => $meal2,  // Létező meal ID
                'optionB' => $meal1,  // Létező meal ID
                'soup' => $meal3,     // Létező meal ID
                'created_at' => now(),
                'updated_at' => now(),
            ]
        ]);
    }
}