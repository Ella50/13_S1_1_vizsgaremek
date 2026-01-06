<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Enums\MealType;

class MealSeeder extends Seeder
{
    public function run()
    {
        DB::table('meals')->insert([
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

            [
                'mealName' => 'Rántott csirke',
                'mealType' => MealType::FOETEL->value,
                'description' => 'Ropogós rántott csirkemell',
                'picture' => '',
                'created_at' => now(),
                'updated_at' => now()
            ],

            [
                'mealName' => 'Húsleves',
                'mealType' => MealType::LEVES->value,
                'description' => 'Hagyományos húsleves',
                'picture' => '',
                'created_at' => now(),
                'updated_at' => now()
            ],

            [
                'mealName' => 'Grillcsirke',
                'mealType' => MealType::FOETEL->value,
                'description' => 'Fűszeres grillcsirke',
                'picture' => '',
                'created_at' => now(),
                'updated_at' => now()
            ],

            [
                'mealName' => 'Gyümölcssaláta',
                'mealType' => MealType::EGYEB->value,
                'description' => 'Friss gyümölcsökből',
                'picture' => '',
                'created_at' => now(),
                'updated_at' => now()
            ],

            [
                'mealName' => 'Paradicsomleves',
                'mealType' => MealType::LEVES->value,
                'description' => 'Krém paradicsomleves',
                'picture' => '',
                'created_at' => now(),
                'updated_at' => now()
            ],

            [
                'mealName' => 'Lasagne',
                'mealType' => MealType::FOETEL->value,
                'description' => 'Olasz lasagne',
                'picture' => '',
                'created_at' => now(),
                'updated_at' => now()
            ]
        ]);
    }
}