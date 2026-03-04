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
                'created_at' => now(),
                'updated_at' => now()
            ],

            [
                'mealName' => 'Marhapörkölt',
                'mealType' => MealType::FOETEL->value,
                'description' => 'Marhából készült pöri',
                'created_at' => now(),
                'updated_at' => now()
            ],

            [
                'mealName' => 'Gombaleves',
                'mealType' => MealType::LEVES->value,
                'description' => 'Könnyű aromás, mennyei falat',
    
                'created_at' => now(),
                'updated_at' => now()
            ],

            [
                'mealName' => 'Rántott csirke',
                'mealType' => MealType::FOETEL->value,
                'description' => 'Ropogós rántott csirkemell',
            
                'created_at' => now(),
                'updated_at' => now()
            ],

            [
                'mealName' => 'Húsleves',
                'mealType' => MealType::LEVES->value,
                'description' => 'Hagyományos húsleves',
       
                'created_at' => now(),
                'updated_at' => now()
            ],

            [
                'mealName' => 'Grillcsirke',
                'mealType' => MealType::FOETEL->value,
                'description' => 'Fűszeres grillcsirke',
               
                'created_at' => now(),
                'updated_at' => now()
            ],

            [
                'mealName' => 'Gyümölcssaláta',
                'mealType' => MealType::EGYEB->value,
                'description' => 'Friss gyümölcsökből',
      
                'created_at' => now(),
                'updated_at' => now()
            ],

            [
                'mealName' => 'Paradicsomleves',
                'mealType' => MealType::LEVES->value,
                'description' => 'Krém paradicsomleves',

                'created_at' => now(),
                'updated_at' => now()
            ],

            [
                'mealName' => 'Lasagne',
                'mealType' => MealType::FOETEL->value,
                'description' => 'Olasz lasagne',
            
                'created_at' => now(),
                'updated_at' => now()
            ],
                        [
                'mealName' => 'Rakott krumpli',
                'mealType' => MealType::FOETEL->value,
                'description' => 'Hagyományos rakott krumpli kolbásszal, tojással',
              
                'created_at' => now(),
                'updated_at' => now()
            ],

            [
                'mealName' => 'Túrós csusza',
                'mealType' => MealType::FOETEL->value,
                'description' => 'Tészta túróval, tejföllel, szalonna',
           
                'created_at' => now(),
                'updated_at' => now()
            ],

            [
                'mealName' => 'Csirkepaprikás',
                'mealType' => MealType::FOETEL->value,
                'description' => 'Szaftos csirkepaprikás galuskával',
            
                'created_at' => now(),
                'updated_at' => now()
            ],

            [
                'mealName' => 'Palacsinta',
                'mealType' => MealType::EGYEB->value,
                'description' => 'Házi palacsinta nutellás vagy lekváros töltelékkel',
               
                'created_at' => now(),
                'updated_at' => now()
            ],

            [
                'mealName' => 'Magyaros burgonyás pogácsa',
                'mealType' => MealType::FOETEL->value,
                'description' => 'Sós pogácsa burgonyával',
            
                'created_at' => now(),
                'updated_at' => now()
            ],

            [
                'mealName' => 'Cézár saláta',
                'mealType' => MealType::FOETEL->value,
                'description' => 'Ropogós saláta csirkével, sajttal, Cézár öntettel',
        
                'created_at' => now(),
                'updated_at' => now()
            ],

            [
                'mealName' => 'Almás pite',
                'mealType' => MealType::EGYEB->value,
                'description' => 'Omlós tészta, fahéjas almás töltelék',
           
                'created_at' => now(),
                'updated_at' => now()
            ],

            [
                'mealName' => 'Frankfurti leves',
                'mealType' => MealType::LEVES->value,
                'description' => 'Zöldséges leves virslivel',
           
                'created_at' => now(),
                'updated_at' => now()
            ],
        ]);
    }
}