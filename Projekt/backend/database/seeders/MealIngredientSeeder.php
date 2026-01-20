<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class MealIngredientSeeder extends Seeder
{
    public function run()
    {
        DB::table('meal_ingredients')->truncate();
        
        // Betöltjük az ételeket és összetevőket
        $meals = DB::table('meals')->get()->keyBy('mealName');
        $allIngredients = DB::table('ingredients')->get();
        
        // Készítünk egy helper tömböt a nevek alapján
        $ingredients = [];
        foreach ($allIngredients as $ingredient) {
            $ingredients[$ingredient->ingredientName] = $ingredient;
        }
        
        $mealIngredients = [];
        $missingIngredients = [];
        
        // Helper függvény az összetevő ellenőrzéséhez
        $getIngredient = function($name) use (&$ingredients, &$missingIngredients) {
            if (!isset($ingredients[$name])) {
                $missingIngredients[] = $name;
                return null;
            }
            return $ingredients[$name];
        };
        
        // ============ GOMBALEVES ============
        if (isset($meals['Gombaleves'])) {
            $gombalevesIngredients = [
                'Friss gomba' => ['amount' => 300.00, 'unit' => 'g'],
                'Hagyma' => ['amount' => 100.00, 'unit' => 'g'],
                'Tejföl' => ['amount' => 200.00, 'unit' => 'ml'],
                'Só' => ['amount' => 10.00, 'unit' => 'g'],
                'Bors' => ['amount' => 5.00, 'unit' => 'g'],
            ];
            
            foreach ($gombalevesIngredients as $ingName => $data) {
                $ingredient = $getIngredient($ingName);
                if ($ingredient) {
                    $mealIngredients[] = [
                        'meal_id' => $meals['Gombaleves']->id,
                        'ingredient_id' => $ingredient->id,
                        'amount' => $data['amount'],
                        'unit' => $data['unit'],

                    ];
                }
            }
        }
        
        // ============ MARHAPÖRKÖLT ============
        if (isset($meals['Marhapörkölt'])) {
            $marhaporkoltIngredients = [
                'Marhahús (comb)' => ['amount' => 500.00, 'unit' => 'g'],
                'Vöröshagyma' => ['amount' => 150.00, 'unit' => 'g'],
                'Piros paprika' => ['amount' => 100.00, 'unit' => 'g'],
                'Olívaolaj' => ['amount' => 30.00, 'unit' => 'ml'],
                'Só' => ['amount' => 15.00, 'unit' => 'g'],
                'Bors' => ['amount' => 8.00, 'unit' => 'g'],
            ];
            
            foreach ($marhaporkoltIngredients as $ingName => $data) {
                $ingredient = $getIngredient($ingName);
                if ($ingredient) {
                    $mealIngredients[] = [
                        'meal_id' => $meals['Marhapörkölt']->id,
                        'ingredient_id' => $ingredient->id,
                        'amount' => $data['amount'],
                        'unit' => $data['unit'],

                    ];
                }
            }
        }
        
        // ============ SAJTOS TÉSZT ============
        if (isset($meals['Sajtos tészta'])) {
            $sajtosTesztaIngredients = [
                'Tészta (spaghetti)' => ['amount' => 400.00, 'unit' => 'g'],
                'Trappista sajt' => ['amount' => 200.00, 'unit' => 'g'],
                'Tejszín' => ['amount' => 200.00, 'unit' => 'ml'],
                'Vaj' => ['amount' => 50.00, 'unit' => 'g'],
                'Só' => ['amount' => 10.00, 'unit' => 'g'],
            ];
            
            foreach ($sajtosTesztaIngredients as $ingName => $data) {
                $ingredient = $getIngredient($ingName);
                if ($ingredient) {
                    $mealIngredients[] = [
                        'meal_id' => $meals['Sajtos tészta']->id,
                        'ingredient_id' => $ingredient->id,
                        'amount' => $data['amount'],
                        'unit' => $data['unit'],

                    ];
                }
            }
        }
        
        // ============ LASAGNE ============
        if (isset($meals['Lasagne'])) {
            $lasagneIngredients = [
                'Lasagne lap' => ['amount' => 250.00, 'unit' => 'g'],
                'Darált marhahús' => ['amount' => 400.00, 'unit' => 'g'],
                'Paradicsom szósz' => ['amount' => 500.00, 'unit' => 'ml'],
                'Mozzarella sajt' => ['amount' => 250.00, 'unit' => 'g'],
                'Vöröshagyma' => ['amount' => 100.00, 'unit' => 'g'],
                'Fokhagyma' => ['amount' => 20.00, 'unit' => 'g'],
            ];
            
            foreach ($lasagneIngredients as $ingName => $data) {
                $ingredient = $getIngredient($ingName);
                if ($ingredient) {
                    $mealIngredients[] = [
                        'meal_id' => $meals['Lasagne']->id,
                        'ingredient_id' => $ingredient->id,
                        'amount' => $data['amount'],
                        'unit' => $data['unit'],
                    ];
                }
            }
        }
        
        // ============ HÚSLEVES ============
        if (isset($meals['Húsleves'])) {
            $huslevesIngredients = [
                'Csirkemell' => ['amount' => 600.00, 'unit' => 'g'],
                'Répa' => ['amount' => 200.00, 'unit' => 'g'],
                'Petrezselyem gyökér' => ['amount' => 100.00, 'unit' => 'g'],
                'Zeller' => ['amount' => 150.00, 'unit' => 'g'],
                'Hagyma' => ['amount' => 100.00, 'unit' => 'g'],
                'Só' => ['amount' => 15.00, 'unit' => 'g'],
            ];
            
            foreach ($huslevesIngredients as $ingName => $data) {
                $ingredient = $getIngredient($ingName);
                if ($ingredient) {
                    $mealIngredients[] = [
                        'meal_id' => $meals['Húsleves']->id,
                        'ingredient_id' => $ingredient->id,
                        'amount' => $data['amount'],
                        'unit' => $data['unit'],
                    ];
                }
            }
        }
        
        // ============ GRILLCSIRKE ============
        if (isset($meals['Grillcsirke'])) {
            $grillcsirkeIngredients = [
                'Csirkecomb' => ['amount' => 800.00, 'unit' => 'g'],
                'Olívaolaj' => ['amount' => 50.00, 'unit' => 'ml'],
                'Fokhagyma' => ['amount' => 30.00, 'unit' => 'g'],
                'Citromlé' => ['amount' => 50.00, 'unit' => 'ml'],
                'Só' => ['amount' => 20.00, 'unit' => 'g'],
                'Bors' => ['amount' => 10.00, 'unit' => 'g'],
            ];
            
            foreach ($grillcsirkeIngredients as $ingName => $data) {
                $ingredient = $getIngredient($ingName);
                if ($ingredient) {
                    $mealIngredients[] = [
                        'meal_id' => $meals['Grillcsirke']->id,
                        'ingredient_id' => $ingredient->id,
                        'amount' => $data['amount'],
                        'unit' => $data['unit'],
                    ];
                }
            }
        }
        
        // ============ GYÜMÖLCSALÁTA ============
        if (isset($meals['Gyümölcssaláta'])) {
            $gyumolcssalataIngredients = [
                'Alma' => ['amount' => 200.00, 'unit' => 'g'],
                'Banán' => ['amount' => 150.00, 'unit' => 'g'],
                'Narancs' => ['amount' => 200.00, 'unit' => 'g'],
                'Szőlő' => ['amount' => 150.00, 'unit' => 'g'],
                'Citromlé' => ['amount' => 20.00, 'unit' => 'ml'],
            ];
            
            foreach ($gyumolcssalataIngredients as $ingName => $data) {
                $ingredient = $getIngredient($ingName);
                if ($ingredient) {
                    $mealIngredients[] = [
                        'meal_id' => $meals['Gyümölcssaláta']->id,
                        'ingredient_id' => $ingredient->id,
                        'amount' => $data['amount'],
                        'unit' => $data['unit'],
                    ];
                }
            }
        }
        
        // Hibajelzés a hiányzó összetevőkről
        if (!empty($missingIngredients)) {
            $this->command->warn('Hiányzó összetevők: ' . implode(', ', array_unique($missingIngredients)));
            
            // Nézzük meg, milyen összetevők vannak
            $this->command->info('Elérhető összetevők:');
            $availableIngredients = array_keys($ingredients);
            sort($availableIngredients);
            foreach ($availableIngredients as $ingName) {
                $this->command->info("  - $ingName");
            }
        }
        
        if (!empty($mealIngredients)) {
            DB::table('meal_ingredients')->insert($mealIngredients);
            
            $this->command->info('Meal-ingredient kapcsolatok sikeresen létrehozva: ' . count($mealIngredients));
        } else {
            $this->command->warn('Nem sikerült meal-ingredient kapcsolatokat létrehozni!');
        }
    }
}