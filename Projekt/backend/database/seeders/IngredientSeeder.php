<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class IngredientSeeder extends Seeder
{
    public function run()
    {
        DB::table('ingredients')->insert([
            // ============ GOMBALEVES ============
            [
                'name' => 'Friss gomba',
                'ingredientType' => 'Zöldség',
                'energy' => 22,
                'protein' => 3,
                'carbohydrate' => 3,
                'fat' => 0,
                'sodium' => 5,
                'sugar' => 2,
                'fiber' => 1,
                'isAvailable' => true
            ],
            [
                'name' => 'Hagyma',
                'ingredientType' => 'Zöldség',
                'energy' => 40,
                'protein' => 1,
                'carbohydrate' => 9,
                'fat' => 0,
                'sodium' => 4,
                'sugar' => 4,
                'fiber' => 2,
                'isAvailable' => true
            ],
            [
                'name' => 'Tejföl',
                'ingredientType' => 'Tejtermék',
                'energy' => 135,
                'protein' => 3,
                'carbohydrate' => 4,
                'fat' => 12,
                'sodium' => 40,
                'sugar' => 4,
                'fiber' => 0,
                'isAvailable' => true
            ],
            
            // ============ MARHAPÖRKÖLT ============
            [
                'name' => 'Marhahús (comb)',
                'ingredientType' => 'Hús',
                'energy' => 250,
                'protein' => 26,
                'carbohydrate' => 0,
                'fat' => 15,
                'sodium' => 70,
                'sugar' => 0,
                'fiber' => 0,
                'isAvailable' => true
            ],
            [
                'name' => 'Vöröshagyma',
                'ingredientType' => 'Zöldség',
                'energy' => 40,
                'protein' => 1,
                'carbohydrate' => 9,
                'fat' => 0,
                'sodium' => 4,
                'sugar' => 4,
                'fiber' => 2,
                'isAvailable' => true
            ],
            [
                'name' => 'Piros paprika',
                'ingredientType' => 'Zöldség',
                'energy' => 31,
                'protein' => 1,
                'carbohydrate' => 6,
                'fat' => 0,
                'sodium' => 4,
                'sugar' => 4,
                'fiber' => 2,
                'isAvailable' => true
            ],
            
            // ============ SAJTOS TÉSZT ============
            [
                'name' => 'Tészta (spaghetti)',
                'ingredientType' => 'Egyéb',
                'energy' => 131,
                'protein' => 5,
                'carbohydrate' => 25,
                'fat' => 1,
                'sodium' => 1,
                'sugar' => 1,
                'fiber' => 1,
                'isAvailable' => true
            ],
            [
                'name' => 'Trappista sajt',
                'ingredientType' => 'Tejtermék',
                'energy' => 350,
                'protein' => 25,
                'carbohydrate' => 2,
                'fat' => 28,
                'sodium' => 620,
                'sugar' => 1,
                'fiber' => 0,
                'isAvailable' => true
            ],
            [
                'name' => 'Tejszín',
                'ingredientType' => 'Tejtermék',
                'energy' => 345,
                'protein' => 2,
                'carbohydrate' => 3,
                'fat' => 36,
                'sodium' => 40,
                'sugar' => 3,
                'fiber' => 0,
                'isAvailable' => true
            ],
            
            // ============ LASAGNE ============
            [
                'name' => 'Lasagne lap',
                'ingredientType' => 'Egyéb',
                'energy' => 370,
                'protein' => 12,
                'carbohydrate' => 74,
                'fat' => 2,
                'sodium' => 10,
                'sugar' => 3,
                'fiber' => 3,
                'isAvailable' => true
            ],
            [
                'name' => 'Darált marhahús',
                'ingredientType' => 'Hús',
                'energy' => 250,
                'protein' => 26,
                'carbohydrate' => 0,
                'fat' => 16,
                'sodium' => 75,
                'sugar' => 0,
                'fiber' => 0,
                'isAvailable' => true
            ],
            [
                'name' => 'Paradicsom szósz',
                'ingredientType' => 'Egyéb',
                'energy' => 80,
                'protein' => 2,
                'carbohydrate' => 12,
                'fat' => 3,
                'sodium' => 400,
                'sugar' => 9,
                'fiber' => 2,
                'isAvailable' => true
            ],
            [
                'name' => 'Mozzarella sajt',
                'ingredientType' => 'Tejtermék',
                'energy' => 280,
                'protein' => 22,
                'carbohydrate' => 2,
                'fat' => 22,
                'sodium' => 400,
                'sugar' => 1,
                'fiber' => 0,
                'isAvailable' => true
            ],
            
            // ============ HÚSLEVES ============
            [
                'name' => 'Csirkemell',
                'ingredientType' => 'Hús',
                'energy' => 165,
                'protein' => 31,
                'carbohydrate' => 0,
                'fat' => 3,
                'sodium' => 74,
                'sugar' => 0,
                'fiber' => 0,
                'isAvailable' => true
            ],
            [
                'name' => 'Répa',
                'ingredientType' => 'Zöldség',
                'energy' => 41,
                'protein' => 1,
                'carbohydrate' => 10,
                'fat' => 0,
                'sodium' => 69,
                'sugar' => 5,
                'fiber' => 3,
                'isAvailable' => true
            ],
            [
                'name' => 'Petrezselyem gyökér',
                'ingredientType' => 'Zöldség',
                'energy' => 55,
                'protein' => 2,
                'carbohydrate' => 12,
                'fat' => 0,
                'sodium' => 60,
                'sugar' => 2,
                'fiber' => 4,
                'isAvailable' => true
            ],
            [
                'name' => 'Zeller',
                'ingredientType' => 'Zöldség',
                'energy' => 42,
                'protein' => 2,
                'carbohydrate' => 9,
                'fat' => 0,
                'sodium' => 80,
                'sugar' => 2,
                'fiber' => 2,
                'isAvailable' => true
            ],
            
            // ============ GRILLCSIRKE ============
            [
                'name' => 'Csirkecomb',
                'ingredientType' => 'Hús',
                'energy' => 240,
                'protein' => 27,
                'carbohydrate' => 0,
                'fat' => 14,
                'sodium' => 90,
                'sugar' => 0,
                'fiber' => 0,
                'isAvailable' => true
            ],
            [
                'name' => 'Olívaolaj',
                'ingredientType' => 'Egyéb',
                'energy' => 884,
                'protein' => 0,
                'carbohydrate' => 0,
                'fat' => 100,
                'sodium' => 0,
                'sugar' => 0,
                'fiber' => 0,
                'isAvailable' => true
            ],
            [
                'name' => 'Fokhagyma',
                'ingredientType' => 'Zöldség',
                'energy' => 149,
                'protein' => 6,
                'carbohydrate' => 33,
                'fat' => 0,
                'sodium' => 17,
                'sugar' => 1,
                'fiber' => 2,
                'isAvailable' => true
            ],
            [
                'name' => 'Citromlé',
                'ingredientType' => 'Egyéb',
                'energy' => 29,
                'protein' => 1,
                'carbohydrate' => 9,
                'fat' => 0,
                'sodium' => 1,
                'sugar' => 2,
                'fiber' => 0,
                'isAvailable' => true
            ],
            
            // ============ GYÜMÖLCSALÁTA ============
            [
                'name' => 'Alma',
                'ingredientType' => 'Gyümölcs',
                'energy' => 52,
                'protein' => 0,
                'carbohydrate' => 14,
                'fat' => 0,
                'sodium' => 1,
                'sugar' => 10,
                'fiber' => 2,
                'isAvailable' => true
            ],
            [
                'name' => 'Banán',
                'ingredientType' => 'Gyümölcs',
                'energy' => 89,
                'protein' => 1,
                'carbohydrate' => 23,
                'fat' => 0,
                'sodium' => 1,
                'sugar' => 12,
                'fiber' => 3,
                'isAvailable' => true
            ],
            [
                'name' => 'Narancs',
                'ingredientType' => 'Gyümölcs',
                'energy' => 47,
                'protein' => 1,
                'carbohydrate' => 12,
                'fat' => 0,
                'sodium' => 0,
                'sugar' => 9,
                'fiber' => 2,
                'isAvailable' => true
            ],
            [
                'name' => 'Szőlő',
                'ingredientType' => 'Gyümölcs',
                'energy' => 69,
                'protein' => 1,
                'carbohydrate' => 18,
                'fat' => 0,
                'sodium' => 2,
                'sugar' => 16,
                'fiber' => 1,
                'isAvailable' => true
            ],
            
            // ============ ÁLTALÁNOS ALAPANYAGOK ============
            [
                'name' => 'Só',
                'ingredientType' => 'Fűszer',
                'energy' => 0,
                'protein' => 0,
                'carbohydrate' => 0,
                'fat' => 0,
                'sodium' => 38758,
                'sugar' => 0,
                'fiber' => 0,
                'isAvailable' => true
            ],
            [
                'name' => 'Bors',
                'ingredientType' => 'Fűszer',
                'energy' => 251,
                'protein' => 10,
                'carbohydrate' => 64,
                'fat' => 3,
                'sodium' => 20,
                'sugar' => 1,
                'fiber' => 26,
                'isAvailable' => true
            ],

            [
                'name' => 'Liszt',
                'ingredientType' => 'Egyéb',
                'energy' => 364,
                'protein' => 10,
                'carbohydrate' => 76,
                'fat' => 1,
                'sodium' => 2,
                'sugar' => 0,
                'fiber' => 3,
                'isAvailable' => true
            ],
            [
                'name' => 'Tojás',
                'ingredientType' => 'Egyéb',
                'energy' => 155,
                'protein' => 13,
                'carbohydrate' => 1,
                'fat' => 11,
                'sodium' => 124,
                'sugar' => 1,
                'fiber' => 0,
                'isAvailable' => true
            ],
            [
                'name' => 'Vaj',
                'ingredientType' => 'Tejtermék',
                'energy' => 717,
                'protein' => 1,
                'carbohydrate' => 0,
                'fat' => 81,
                'sodium' => 11,
                'sugar' => 0,
                'fiber' => 0,
                'isAvailable' => true
            ],
            [
                'name' => 'Élesztő',
                'ingredientType' => 'Egyéb',
                'energy' => 105,
                'protein' => 8,
                'carbohydrate' => 18,
                'fat' => 2,
                'sodium' => 30,
                'sugar' => 0,
                'fiber' => 8,
                'isAvailable' => true
            ],
            [
                'name' => 'Cukor',
                'ingredientType' => 'Egyéb',
                'energy' => 387,
                'protein' => 0,
                'carbohydrate' => 100,
                'fat' => 0,
                'sodium' => 1,
                'sugar' => 100,
                'fiber' => 0,
                'isAvailable' => true
            ],
            [
                'name' => 'Burgonya',
                'ingredientType' => 'Zöldség',
                'energy' => 77,
                'protein' => 2,
                'carbohydrate' => 17,
                'fat' => 0,
                'sodium' => 6,
                'sugar' => 1,
                'fiber' => 2,
                'isAvailable' => true
            ],
            [
                'name' => 'Rizs',
                'ingredientType' => 'Egyéb',
                'energy' => 130,
                'protein' => 3,
                'carbohydrate' => 28,
                'fat' => 0,
                'sodium' => 1,
                'sugar' => 0,
                'fiber' => 0,
                'isAvailable' => true
            ]
        ]);
    }
}