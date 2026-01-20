<?php

namespace App\Http\Controllers\Api;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Models\Meal;
use App\Models\Ingredient;
use App\Models\Allergen;
use App\Enums\MealType;

class KitchenController extends Controller
{
    // Összes étel lekérdezése ALLERGÉNEKKEL
    public function getMeals(Request $request)
    {
        try {
            $query = Meal::query();
            
            // Keresés
            if ($request->has('search') && !empty($request->search)) {
                $search = $request->search;
                $query->where(function($q) use ($search) {
                    $q->where('mealName', 'like', "%{$search}%")
                      ->orWhere('description', 'like', "%{$search}%");
                });
            }
            
            // Kategória szűrés
            if ($request->has('category') && !empty($request->category)) {
                $query->where('mealType', $request->category);
            }
            
            // Allergének betöltése, ha kérték
            $withAllergens = $request->has('withAllergens') && $request->withAllergens === 'true';
            
            // Összetevők betöltése ALLERGÉNEKKEL
            $query->with(['ingredients' => function($q) use ($withAllergens) {
                $q->select([
                    'ingredients.id',
                    'ingredients.ingredientName',
                    'ingredients.ingredientType',
                    'ingredients.energy',
                    'ingredients.protein',
                    'ingredients.carbohydrate',
                    'ingredients.fat'
                ]);
                $q->withPivot('amount', 'unit');
                
                // Ha allergéneket is kérünk, betöltjük őket
                if ($withAllergens) {
                    $q->with(['allergens' => function($allergenQuery) {
                        $allergenQuery->select('id', 'allergenName', 'icon');
                    }]);
                }
            }]);
            
            // Rendezés
            $query->orderBy('mealName');
            
            $meals = $query->get();
            
            // Átalakítás a Vue komponens elvárásainak megfelelően
            $formattedMeals = $meals->map(function ($meal) {
                // Alap adatok
                $formattedMeal = [
                    'id' => $meal->id,
                    'mealName' => $meal->mealName,
                    'category' => $meal->mealType,
                    'description' => $meal->description,
                    'picture' => $meal->picture,
                    'created_at' => $meal->created_at,
                    'updated_at' => $meal->updated_at
                ];
                
                // Összetevők hozzáadása
                if ($meal->relationLoaded('ingredients')) {
                    $formattedMeal['ingredients'] = $meal->ingredients->map(function ($ingredient) {
                        $formattedIngredient = [
                            'id' => $ingredient->id,
                            'ingredientName' => $ingredient->ingredientName,
                            'ingredientType' => $ingredient->ingredientType,
                            'energy' => (int) $ingredient->energy,
                            'protein' => (int) $ingredient->protein,
                            'carbohydrate' => (int) $ingredient->carbohydrate,
                            'fat' => (int) $ingredient->fat,
                            'pivot' => [
                                'amount' => (float) ($ingredient->pivot->amount ?? 0),
                                'unit' => $ingredient->pivot->unit ?? 'g'
                            ]
                        ];
                        
                        // Ha van allergén, hozzáadjuk
                        if ($ingredient->relationLoaded('allergens')) {
                            $formattedIngredient['allergens'] = $ingredient->allergens->map(function ($allergen) {
                                return [
                                    'id' => $allergen->id,
                                    'allergenName' => $allergen->allergenName,
                                    'icon' => $allergen->icon,
                                    'icon_url' => $this->getAllergenIconUrl($allergen->icon)
                                ];
                            })->toArray();
                        }
                        
                        return $formattedIngredient;
                    });
                    
                    // Az étel összes allergénjének összegyűjtése
                    $allAllergens = collect();
                    foreach ($meal->ingredients as $ingredient) {
                        if ($ingredient->allergens && $ingredient->allergens->isNotEmpty()) {
                            $allAllergens = $allAllergens->merge($ingredient->allergens);
                        }
                    }
                    
                    // Egyedi allergének
                    $formattedMeal['allergens'] = $allAllergens->unique('id')->map(function ($allergen) {
                        return [
                            'id' => $allergen->id,
                            'allergenName' => $allergen->allergenName,
                            'icon' => $allergen->icon,
                            'icon_url' => $this->getAllergenIconUrl($allergen->icon)
                        ];
                    })->values()->toArray();
                }
                
                return $formattedMeal;
            });
            
            return response()->json([
                'success' => true,
                'meals' => $formattedMeals,
                'count' => $formattedMeals->count(),
                'message' => 'Ételek sikeresen betöltve.'
            ]);
            
        } catch (\Exception $e) {
            Log::error('Kitchen getMeals error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Hiba történt az ételek betöltésekor.',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }
    
    // Allergén ikon URL generálása
    private function getAllergenIconUrl($iconPath)
    {
        if (!$iconPath) {
            return null;
        }
        
        // Ha már teljes URL, akkor azt adjuk vissza
        if (filter_var($iconPath, FILTER_VALIDATE_URL)) {
            return $iconPath;
        }
        
        // Egyébként relatív útvonalként kezeljük
        return asset('storage/' . $iconPath);
    }
    
    /**
     * Egy étel összetevőinek lekérdezése ALLERGÉNEKKEL
     */
    public function getMealIngredients($id, Request $request = null)
    {
        try {
            Log::info('=== START getMealIngredients for ID: ' . $id . ' ===');
            
            // Paraméterek kezelése
            $withAllergens = true; // Mindig betöltjük az allergéneket
            
            // 1. Ellenőrizzük az ételt
            $meal = Meal::find($id);
            
            if (!$meal) {
                Log::warning('Meal not found with ID: ' . $id);
                return response()->json([
                    'success' => false,
                    'message' => 'Az étel nem található.'
                ], 404);
            }
            
            Log::info('Meal found: ' . $meal->id . ' - ' . $meal->mealName);
            
            // 2. Betöltjük az összetevőket allergénekkel
            $ingredients = $meal->ingredients()->with([
                'allergens' => function($query) {
                    $query->select('id', 'allergenName', 'icon');
                }
            ])->withPivot('amount', 'unit')->get();
            
            Log::info('Ingredients loaded with allergens: ' . $ingredients->count());
            
            // 3. Formázott válasz
            $formattedIngredients = $ingredients->map(function($ingredient) {
                Log::info('Processing ingredient: ' . $ingredient->id . ' - ' . $ingredient->ingredientName);
                
                $formattedIngredient = [
                    'id' => $ingredient->id,
                    'ingredientName' => $ingredient->ingredientName,
                    'ingredientType' => $ingredient->ingredientType,
                    'energy' => (int) $ingredient->energy,
                    'protein' => (int) $ingredient->protein,
                    'carbohydrate' => (int) $ingredient->carbohydrate,
                    'fat' => (int) $ingredient->fat,
                    'pivot' => [
                        'amount' => $ingredient->pivot ? (float) $ingredient->pivot->amount : 0,
                        'unit' => $ingredient->pivot ? $ingredient->pivot->unit : 'g'
                    ]
                ];
                
                // Allergének hozzáadása
                if ($ingredient->relationLoaded('allergens') && $ingredient->allergens->isNotEmpty()) {
                    $formattedIngredient['allergens'] = $ingredient->allergens->map(function($allergen) {
                        return [
                            'id' => $allergen->id,
                            'allergenName' => $allergen->allergenName,
                            'icon' => $allergen->icon,
                            'icon_url' => $this->getAllergenIconUrl($allergen->icon)
                        ];
                    })->toArray();
                } else {
                    $formattedIngredient['allergens'] = [];
                }
                
                return $formattedIngredient;
            });
            
            // 4. Az étel összes allergénjének összegyűjtése
            $allAllergens = collect();
            foreach ($ingredients as $ingredient) {
                if ($ingredient->allergens && $ingredient->allergens->isNotEmpty()) {
                    $allAllergens = $allAllergens->merge($ingredient->allergens);
                }
            }
            
            $uniqueAllergens = $allAllergens->unique('id')->map(function($allergen) {
                return [
                    'id' => $allergen->id,
                    'allergenName' => $allergen->allergenName,
                    'icon' => $allergen->icon,
                    'icon_url' => $this->getAllergenIconUrl($allergen->icon)
                ];
            })->values();
            
            Log::info('=== END getMealIngredients ===');
            
            return response()->json([
                'success' => true,
                'meal' => [
                    'id' => $meal->id,
                    'mealName' => $meal->mealName,
                    'category' => $meal->mealType,
                    'description' => $meal->description,
                    'allergens' => $uniqueAllergens
                ],
                'ingredients' => $formattedIngredients,
                'allergens' => $uniqueAllergens,
                'count' => $formattedIngredients->count(),
                'debug' => [
                    'meal_id' => $id,
                    'ingredients_count' => $formattedIngredients->count(),
                    'allergens_count' => $uniqueAllergens->count()
                ]
            ]);
            
        } catch (\Exception $e) {
            Log::error('CRITICAL ERROR in getMealIngredients: ' . $e->getMessage());
            Log::error('File: ' . $e->getFile());
            Log::error('Line: ' . $e->getLine());
            Log::error('Trace: ' . $e->getTraceAsString());
            
            return response()->json([
                'success' => false,
                'message' => 'Szerverhiba történt.',
                'error' => config('app.debug') ? $e->getMessage() : null,
                'debug_info' => config('app.debug') ? [
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                    'trace' => $e->getTrace()
                ] : null
            ], 500);
        }
    }
    
    /**
     * Összes hozzávaló lekérdezése
     */
    public function getAllIngredients(Request $request = null)
    {
        try {
            $query = Ingredient::query();
            
            // Opcionális: betölthetjük az allergéneket is
            if ($request && $request->has('withAllergens') && $request->withAllergens === 'true') {
                $query->with(['allergens' => function($q) {
                    $q->select('id', 'allergenName', 'icon');
                }]);
            }
            
            $ingredients = $query->orderBy('ingredientName')->get();
            
            // Átalakítás, ha allergéneket is kértünk
            $formattedIngredients = $ingredients->map(function($ingredient) use ($request) {
                $formatted = [
                    'id' => $ingredient->id,
                    'ingredientName' => $ingredient->ingredientName,
                    'ingredientType' => $ingredient->ingredientType,
                    'energy' => $ingredient->energy,
                    'protein' => $ingredient->protein,
                    'carbohydrate' => $ingredient->carbohydrate,
                    'fat' => $ingredient->fat,
                    'sodium' => $ingredient->sodium,
                    'sugar' => $ingredient->sugar,
                    'fiber' => $ingredient->fiber,
                    'isAvailable' => (bool) $ingredient->isAvailable
                ];
                
                if ($request && $request->has('withAllergens') && $request->withAllergens === 'true') {
                    if ($ingredient->relationLoaded('allergens') && $ingredient->allergens->isNotEmpty()) {
                        $formatted['allergens'] = $ingredient->allergens->map(function($allergen) {
                            return [
                                'id' => $allergen->id,
                                'allergenName' => $allergen->allergenName,
                                'icon' => $allergen->icon,
                                'icon_url' => $this->getAllergenIconUrl($allergen->icon)
                            ];
                        })->toArray();
                    } else {
                        $formatted['allergens'] = [];
                    }
                }
                
                return $formatted;
            });
            
            return response()->json([
                'success' => true,
                'ingredients' => $formattedIngredients,
                'count' => $ingredients->count()
            ]);
            
        } catch (\Exception $e) {
            Log::error('Get all ingredients error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Hiba történt a hozzávalók betöltésekor.'
            ], 500);
        }
    }
    
    // ... a többi metódus változatlan marad (storeMeal, updateMeal, deleteMeal, stb.)
    
    /**
     * Ételek listázása allergénekkel (speciális végpont)
     */
    public function mealsWithAllergens()
    {
        try {
            $meals = Meal::with(['ingredients.allergens' => function($query) {
                $query->select('id', 'allergenName', 'icon');
            }])
            ->get()
            ->map(function($meal) {
                // Összegyűjtjük az összes allergént
                $allAllergens = collect();
                foreach ($meal->ingredients as $ingredient) {
                    if ($ingredient->allergens && $ingredient->allergens->isNotEmpty()) {
                        $allAllergens = $allAllergens->merge($ingredient->allergens);
                    }
                }
                
                $meal->allergens = $allAllergens->unique('id')->map(function($allergen) {
                    return [
                        'id' => $allergen->id,
                        'allergenName' => $allergen->allergenName,
                        'icon' => $allergen->icon,
                        'icon_url' => $this->getAllergenIconUrl($allergen->icon)
                    ];
                })->values()->toArray();
                
                return $meal;
            });
            
            return response()->json([
                'success' => true,
                'data' => $meals,
                'count' => $meals->count()
            ]);
            
        } catch (\Exception $e) {
            Log::error('mealsWithAllergens error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Hiba történt az ételek allergénekkel történő betöltése során.'
            ], 500);
        }
    }
}