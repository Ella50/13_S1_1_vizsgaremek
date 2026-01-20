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
        // Tábla ürítése (opcionális)
        DB::table('ingredient_allergens')->truncate();


        // Összetevők és allergénjeik definíciója
        $ingredientAllergens = [
            // TEJTERMÉKEK
            'Tej' => ['Tej'],
            'Tejföl' => ['Tej'],
            'Trappista sajt' => ['Tej'],
            'Tejszín' => ['Tej'],
            'Vaj' => ['Tej'],
            
            // TOJÁS
            'Tojás' => ['Tojás'],
            
            // GLUTÉN TARTALMÚAK
            'Liszt' => ['Glutén'],
            'Búzaliszt' => ['Glutén'],
            'Tészta (spaghetti)' => ['Glutén'],
            'Lasagne lap' => ['Glutén'],
            'Élesztő' => ['Glutén'],
            
            // HALAK
            'Hal' => ['Hal'],
            'Rák' => ['Rákfélék'],
            
            // DIÓFÉLÉK
            'Mogyoró' => ['Diófélék'],
            'Dió' => ['Diófélék'],
            'Mandula' => ['Diófélék'],
            'Földimogyoró' => ['Földimogyoró', 'Diófélék'],
            
            // ZELLER
            'Zeller' => ['Zeller'],
            
            // MUSTÁR
            'Mustár' => ['Mustár'],
            
            'Szójaszósz' => ['Szója'],
            // Jelenleg nincs explicit szója összetevő, de a paradicsom szószban lehet
            

            
            // A többi összetevő allergénmentes:
            // 'Friss gomba' => [],
            // 'Hagyma' => [],
            // 'Vöröshagyma' => [],
            // 'Piros paprika' => [],
            // 'Paradicsom szósz' => [],
            // 'Csirkemell' => [],
            // 'Répa' => [],
            // 'Petrezselyem gyökér' => [],
            // 'Csirkecomb' => [],
            // 'Olívaolaj' => [],
            // 'Fokhagyma' => [],
            // 'Citromlé' => [],
            // 'Alma' => [],
            // 'Banán' => [],
            // 'Narancs' => [],
            // 'Szőlő' => [],
            // 'Só' => [],
            // 'Bors' => [],
            // 'Cukor' => [],
            // 'Burgonya' => [],
            // 'Rizs' => [],
            // 'Paradicsom' => [],
            // 'Uborka' => [],
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
            
            // Keresd meg az allergéneket
            $allergens = Allergen::whereIn('allergenName', $allergenNames)->get();
            
            if ($allergens->isEmpty()) {
                $missingAllergens = array_merge($missingAllergens, $allergenNames);
                continue;
            }
            
            // Kapcsolatok létrehozása
            foreach ($allergens as $allergen) {
                // Ellenőrizzük, hogy már létezik-e a kapcsolat
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

        // Speciális eset: Paradicsom szósz (lehet, hogy glutént tartalmaz)
        $paradicsomSzosz = Ingredient::where('ingredientName', 'Paradicsom szósz')->first();
        $glutenAllergen = Allergen::where('allergenName', 'Glutén')->first();
        
        if ($paradicsomSzosz && $glutenAllergen) {
            $exists = DB::table('ingredient_allergens')
                ->where('ingredient_id', $paradicsomSzosz->id)
                ->where('allergen_id', $glutenAllergen->id)
                ->exists();
            
            if (!$exists) {
                DB::table('ingredient_allergens')->insert([
                    'ingredient_id' => $paradicsomSzosz->id,
                    'allergen_id' => $glutenAllergen->id,

                ]);
                $totalConnections++;
            }
        }

        // Hibajelzések
        if (!empty($missingIngredients)) {
            $this->command->warn('Hiányzó összetevők: ' . implode(', ', array_unique($missingIngredients)));
        }
        
        if (!empty($missingAllergens)) {
            $this->command->warn('Hiányzó allergének: ' . implode(', ', array_unique($missingAllergens)));
        }

        $this->command->info("Összesen {$totalConnections} összetevő-allergén kapcsolat");
        
    }
    
}