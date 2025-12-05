<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Enums\IngredientType;

class IngredientTableSeeder extends Seeder
{
    public function run()
    {
        DB::table('ingredient')->insert([
            ['name' => 'Paprika',
            'type' => IngredientType::ZOLDSEG->value,
            'energy' => true,
            'protein'=> 10,
            'carbohydrate' => 10,
            'fat' => 10,
            'sodium' => 10,
            'sugar' => 10,
            'fiber' => 10,
            'isAvailable' => true
            ],

            ['name' => 'Marhahús',
            'type' => IngredientType::HUS->value,
            'energy' => true,
            'protein'=> 15,
            'carbohydrate' => 50,
            'fat' => 15,
            'sodium' => 15,
            'sugar' => 15,
            'fiber' => 15,
            'isAvailable' => false
            ],



      ]);
    }
}