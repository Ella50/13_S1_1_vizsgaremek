<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Enums\MealType;

class MealTableSeeder extends Seeder
{
    public function run()
    {
        DB::table('meal')->insert([
            [
            'mealName' => 'Sajtos tészta',
            'mealType' => MealType::FOETEL->value,
            'description' => 'Tészta sajttal',
            'picture' => '',
            'created_at' => now(),
            'updated_at' => now()
            ],

            [
            'mealName' => 'Marhapörkölt',
            'mealType' => MealType::FOETEL->value,
            'description' => 'Marhából készült pöri',
            'picture' => '',
            'created_at' => now(),
            'updated_at' => now()
            
            ],

            [
            'mealName' => 'Gombaleves',
            'mealType' => MealType::LEVES->value,
            'description' => 'Könnyű aromás, mennyei falat',
            'picture' => '',
            'created_at' => now(),
            'updated_at' => now()
            
            ],
        ]);
    }
}