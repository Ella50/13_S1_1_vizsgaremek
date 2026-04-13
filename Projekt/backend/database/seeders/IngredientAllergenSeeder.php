<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\Ingredient;
use App\Models\Allergen;

class IngredientAllergenSeeder extends Seeder
{
    public function run()
    {
        DB::table('ingredient_allergens')->truncate();

        $ingredientAllergens = [
            'Tej' => ['Tej'],
            'Tejföl' => ['Tej'],
            'Trappista sajt' => ['Tej'],
            'Tejszín' => ['Tej'],
            'Vaj' => ['Tej'],
            
            'Tojás' => ['Tojás'],
            
            'Liszt' => ['Glutén'],
            'Búzaliszt' => ['Glutén'],
            'Tészta (spaghetti)' => ['Glutén'],
            'Lasagne lap' => ['Glutén'],
            'Élesztő' => ['Glutén'],
            
            'Hal' => ['Hal'],
            'Rák' => ['Rákfélék'],
            
            'Mogyoró' => ['Diófélék'],
            'Dió' => ['Diófélék'],
            'Mandula' => ['Diófélék'],
            'Földimogyoró' => ['Földimogyoró', 'Diófélék'],

            'Zeller' => ['Zeller'],
            'Mustár' => ['Mustár'],
            
        ];

        $totalConnections = 0;
        $missingIngredients = [];
        $missingAllergens = [];

        foreach ($ingredientAllergens as $ingredientName => $allergenNames) {

            $ingredient = Ingredient::where('ingredientName', $ingredientName)->first();
            
            if (!$ingredient) {
                $missingIngredients[] = $ingredientName;
                continue;
            }
            
            if (empty($allergenNames)) {
                continue;
            }
            
            $allergens = Allergen::whereIn('allergenName', $allergenNames)->get();
            
            if ($allergens->isEmpty()) {
                $missingAllergens = array_merge($missingAllergens, $allergenNames);
                continue;
            }
            
            foreach ($allergens as $allergen) {
                $exists = DB::table('ingredient_allergens')
                    ->where('ingredient_id', $ingredient->id)
                    ->where('allergen_id', $allergen->id)
                    ->exists();
                
                if (!$exists) {
                    DB::table('ingredient_allergens')->insert([
                        'ingredient_id' => $ingredient->id,
                        'allergen_id' => $allergen->id,

                    ]);
                    $totalConnections++;
                    
                }
            }
        }



        $this->command->info("Összesen {$totalConnections} összetevő-allergén kapcsolat");
        
    }
    
}