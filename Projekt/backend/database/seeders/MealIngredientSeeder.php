<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class MealIngredientSeeder extends Seeder
{
    public function run()
    {
        // Ürítjük a táblát, ha van adat
        DB::table('meal_ingredients')->truncate();
        
        // Ételek lekérése
        $meals = DB::table('meals')->get()->keyBy('mealName');
        $ingredients = DB::table('ingredients')->get()->keyBy('name');
        
        // Kapcsolatok definiálása
        $mealIngredients = [];
        
        // ============ GOMBALEVES ============
        if (isset($meals['Gombaleves'])) {
            $mealIngredients[] = [
                'meal_id' => $meals['Gombaleves']->id,
                'ingredient_id' => $ingredients['Friss gomba']->id,
                'amount' => 300.00,
                'unit' => 'g'
            ];
            $mealIngredients[] = [
                'meal_id' => $meals['Gombaleves']->id,
                'ingredient_id' => $ingredients['Hagyma']->id,
                'amount' => 100.00,
                'unit' => 'g'
            ];
            $mealIngredients[] = [
                'meal_id' => $meals['Gombaleves']->id,
                'ingredient_id' => $ingredients['Tejföl']->id,
                'amount' => 200.00,
                'unit' => 'ml'
            ];

            $mealIngredients[] = [
                'meal_id' => $meals['Gombaleves']->id,
                'ingredient_id' => $ingredients['Só']->id,
                'amount' => 10.00,
                'unit' => 'g'
            ];
            $mealIngredients[] = [
                'meal_id' => $meals['Gombaleves']->id,
                'ingredient_id' => $ingredients['Bors']->id,
                'amount' => 5.00,
                'unit' => 'g'
            ];
        }
        
        // ============ MARHAPÖRKÖLT ============
        if (isset($meals['Marhapörkölt'])) {
            $mealIngredients[] = [
                'meal_id' => $meals['Marhapörkölt']->id,
                'ingredient_id' => $ingredients['Marhahús (comb)']->id,
                'amount' => 500.00,
                'unit' => 'g'
            ];
            $mealIngredients[] = [
                'meal_id' => $meals['Marhapörkölt']->id,
                'ingredient_id' => $ingredients['Vöröshagyma']->id,
                'amount' => 150.00,
                'unit' => 'g'
            ];
            $mealIngredients[] = [
                'meal_id' => $meals['Marhapörkölt']->id,
                'ingredient_id' => $ingredients['Piros paprika']->id,
                'amount' => 100.00,
                'unit' => 'g'
            ];
            $mealIngredients[] = [
                'meal_id' => $meals['Marhapörkölt']->id,
                'ingredient_id' => $ingredients['Olívaolaj']->id,
                'amount' => 30.00,
                'unit' => 'ml'
            ];
            $mealIngredients[] = [
                'meal_id' => $meals['Marhapörkölt']->id,
                'ingredient_id' => $ingredients['Só']->id,
                'amount' => 15.00,
                'unit' => 'g'
            ];
            $mealIngredients[] = [
                'meal_id' => $meals['Marhapörkölt']->id,
                'ingredient_id' => $ingredients['Bors']->id,
                'amount' => 8.00,
                'unit' => 'g'
            ];
        }
        
        // ============ SAJTOS TÉSZT ============
        if (isset($meals['Sajtos tészta'])) {
            $mealIngredients[] = [
                'meal_id' => $meals['Sajtos tészta']->id,
                'ingredient_id' => $ingredients['Tészta (spaghetti)']->id,
                'amount' => 400.00,
                'unit' => 'g'
            ];
            $mealIngredients[] = [
                'meal_id' => $meals['Sajtos tészta']->id,
                'ingredient_id' => $ingredients['Trappista sajt']->id,
                'amount' => 200.00,
                'unit' => 'g'
            ];
            $mealIngredients[] = [
                'meal_id' => $meals['Sajtos tészta']->id,
                'ingredient_id' => $ingredients['Tejszín']->id,
                'amount' => 200.00,
                'unit' => 'ml'
            ];
            $mealIngredients[] = [
                'meal_id' => $meals['Sajtos tészta']->id,
                'ingredient_id' => $ingredients['Vaj']->id,
                'amount' => 50.00,
                'unit' => 'g'
            ];
            $mealIngredients[] = [
                'meal_id' => $meals['Sajtos tészta']->id,
                'ingredient_id' => $ingredients['Só']->id,
                'amount' => 10.00,
                'unit' => 'g'
            ];
        }
        
        // ============ LASAGNE ============
        if (isset($meals['Lasagne'])) {
            $mealIngredients[] = [
                'meal_id' => $meals['Lasagne']->id,
                'ingredient_id' => $ingredients['Lasagne lap']->id,
                'amount' => 250.00,
                'unit' => 'g'
            ];
            $mealIngredients[] = [
                'meal_id' => $meals['Lasagne']->id,
                'ingredient_id' => $ingredients['Darált marhahús']->id,
                'amount' => 400.00,
                'unit' => 'g'
            ];
            $mealIngredients[] = [
                'meal_id' => $meals['Lasagne']->id,
                'ingredient_id' => $ingredients['Paradicsom szósz']->id,
                'amount' => 500.00,
                'unit' => 'ml'
            ];
            $mealIngredients[] = [
                'meal_id' => $meals['Lasagne']->id,
                'ingredient_id' => $ingredients['Mozzarella sajt']->id,
                'amount' => 250.00,
                'unit' => 'g'
            ];
            $mealIngredients[] = [
                'meal_id' => $meals['Lasagne']->id,
                'ingredient_id' => $ingredients['Vöröshagyma']->id,
                'amount' => 100.00,
                'unit' => 'g'
            ];
            $mealIngredients[] = [
                'meal_id' => $meals['Lasagne']->id,
                'ingredient_id' => $ingredients['Fokhagyma']->id,
                'amount' => 20.00,
                'unit' => 'g'
            ];
        }
        
        // ============ HÚSLEVES ============
        if (isset($meals['Húsleves'])) {
            $mealIngredients[] = [
                'meal_id' => $meals['Húsleves']->id,
                'ingredient_id' => $ingredients['Csirkemell']->id,
                'amount' => 600.00,
                'unit' => 'g'
            ];
            $mealIngredients[] = [
                'meal_id' => $meals['Húsleves']->id,
                'ingredient_id' => $ingredients['Répa']->id,
                'amount' => 200.00,
                'unit' => 'g'
            ];
            $mealIngredients[] = [
                'meal_id' => $meals['Húsleves']->id,
                'ingredient_id' => $ingredients['Petrezselyem gyökér']->id,
                'amount' => 100.00,
                'unit' => 'g'
            ];
            $mealIngredients[] = [
                'meal_id' => $meals['Húsleves']->id,
                'ingredient_id' => $ingredients['Zeller']->id,
                'amount' => 150.00,
                'unit' => 'g'
            ];
            $mealIngredients[] = [
                'meal_id' => $meals['Húsleves']->id,
                'ingredient_id' => $ingredients['Hagyma']->id,
                'amount' => 100.00,
                'unit' => 'g'
            ];

            $mealIngredients[] = [
                'meal_id' => $meals['Húsleves']->id,
                'ingredient_id' => $ingredients['Só']->id,
                'amount' => 15.00,
                'unit' => 'g'
            ];
        }
        
        // ============ GRILLCSIRKE ============
        if (isset($meals['Grillcsirke'])) {
            $mealIngredients[] = [
                'meal_id' => $meals['Grillcsirke']->id,
                'ingredient_id' => $ingredients['Csirkecomb']->id,
                'amount' => 800.00,
                'unit' => 'g'
            ];
            $mealIngredients[] = [
                'meal_id' => $meals['Grillcsirke']->id,
                'ingredient_id' => $ingredients['Olívaolaj']->id,
                'amount' => 50.00,
                'unit' => 'ml'
            ];
            $mealIngredients[] = [
                'meal_id' => $meals['Grillcsirke']->id,
                'ingredient_id' => $ingredients['Fokhagyma']->id,
                'amount' => 30.00,
                'unit' => 'g'
            ];
            $mealIngredients[] = [
                'meal_id' => $meals['Grillcsirke']->id,
                'ingredient_id' => $ingredients['Citromlé']->id,
                'amount' => 50.00,
                'unit' => 'ml'
            ];
            $mealIngredients[] = [
                'meal_id' => $meals['Grillcsirke']->id,
                'ingredient_id' => $ingredients['Só']->id,
                'amount' => 20.00,
                'unit' => 'g'
            ];
            $mealIngredients[] = [
                'meal_id' => $meals['Grillcsirke']->id,
                'ingredient_id' => $ingredients['Bors']->id,
                'amount' => 10.00,
                'unit' => 'g'
            ];
        }
        
        // ============ GYÜMÖLCSALÁTA ============
        if (isset($meals['Gyümölcssaláta'])) {
            $mealIngredients[] = [
                'meal_id' => $meals['Gyümölcssaláta']->id,
                'ingredient_id' => $ingredients['Alma']->id,
                'amount' => 200.00,
                'unit' => 'g'
            ];
            $mealIngredients[] = [
                'meal_id' => $meals['Gyümölcssaláta']->id,
                'ingredient_id' => $ingredients['Banán']->id,
                'amount' => 150.00,
                'unit' => 'g'
            ];
            $mealIngredients[] = [
                'meal_id' => $meals['Gyümölcssaláta']->id,
                'ingredient_id' => $ingredients['Narancs']->id,
                'amount' => 200.00,
                'unit' => 'g'
            ];
            $mealIngredients[] = [
                'meal_id' => $meals['Gyümölcssaláta']->id,
                'ingredient_id' => $ingredients['Szőlő']->id,
                'amount' => 150.00,
                'unit' => 'g'
            ];
            $mealIngredients[] = [
                'meal_id' => $meals['Gyümölcssaláta']->id,
                'ingredient_id' => $ingredients['Citromlé']->id,
                'amount' => 20.00,
                'unit' => 'ml'
            ];
        }
        
        // Adatok beszúrása
        if (!empty($mealIngredients)) {
            DB::table('meal_ingredients')->insert($mealIngredients);
            
            $this->command->info('Meal ingredients seeded successfully!');
            $this->command->info('Total connections: ' . count($mealIngredients));
        } else {
            $this->command->warn('No meal ingredients were seeded. Check if meals and ingredients exist.');
        }
    }
}