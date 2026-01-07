<?php

namespace App\Http\Controllers\Api;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Models\Meal;
use App\Models\Ingredient;
use App\Enums\MealType;

class KitchenController extends Controller
{
    //Összes étel lekérdezése
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
                
                // Összetevők betöltése EGYÜTT
                $query->with(['ingredients' => function($q) {
                    $q->select([
                        'ingredients.id',
                        'ingredients.name',
                        'ingredients.ingredientType',
                        'ingredients.energy',
                        'ingredients.protein',
                        'ingredients.carbohydrate',
                        'ingredients.fat'
                    ]);
                    $q->withPivot('amount', 'unit');
                }]);
                
                // Rendezés
                $query->orderBy('mealName');
                
                $meals = $query->get();
                
                // Átalakítás a Vue komponens elvárásainak megfelelően
                $formattedMeals = $meals->map(function ($meal) {
                    // Alap adatok
                    $formattedMeal = [
                        'id' => $meal->id,
                        'name' => $meal->mealName,
                        'category' => $meal->mealType,
                        'description' => $meal->description,
                        'picture' => $meal->picture,
                        'created_at' => $meal->created_at,
                        'updated_at' => $meal->updated_at
                    ];
                    
                    // Összetevők hozzáadása
                    if ($meal->relationLoaded('ingredients')) {
                        $formattedMeal['ingredients'] = $meal->ingredients->map(function ($ingredient) {
                            return [
                                'id' => $ingredient->id,
                                'name' => $ingredient->name,
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
                        });
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

    /**
     * Új étel létrehozása
     */
    public function storeMeal(Request $request)
    {
        try {
            // Validáció
            $validator = Validator::make($request->all(), [
                'name' => 'required|string|max:255',
                'category' => 'required|string|in:' . implode(',', MealType::values()),
                'description' => 'nullable|string',
                'calories' => 'nullable|integer|min:0',
                'allergens' => 'nullable|array',
                'allergens.*' => 'string'
            ], [
                'name.required' => 'Az étel neve kötelező.',
                'name.max' => 'Az étel neve maximum 255 karakter lehet.',
                'category.required' => 'A kategória megadása kötelező.',
                'category.in' => 'Érvénytelen kategória.',
                'calories.min' => 'A kalóriaérték nem lehet negatív.'
            ]);
            
            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validációs hiba',
                    'errors' => $validator->errors()
                ], 422);
            }
            
            // Étel létrehozása
            $meal = Meal::create([
                'mealName' => $request->name,
                'mealType' => $request->category,
                'description' => $request->description,
                'picture' => $request->picture ?? null,
                // Ha van extra meződ, itt add hozzá
                // 'calories' => $request->calories,
                // 'allergens' => $request->allergens ? json_encode($request->allergens) : null
            ]);
            
            return response()->json([
                'success' => true,
                'message' => 'Étel sikeresen hozzáadva.',
                'meal' => [
                    'id' => $meal->id,
                    'name' => $meal->mealName,
                    'category' => $meal->mealType,
                    'description' => $meal->description,
                    'picture' => $meal->picture,
                    'created_at' => $meal->created_at,
                    'updated_at' => $meal->updated_at
                ]
            ], 201);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Hiba történt az étel hozzáadása során.',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Ételfrissítés
     */
    public function updateMeal(Request $request, $id)
    {
        try {
            // Étel megkeresése
            $meal = Meal::find($id);
            
            if (!$meal) {
                return response()->json([
                    'success' => false,
                    'message' => 'Az étel nem található.'
                ], 404);
            }
            
            // Validáció
            $validator = Validator::make($request->all(), [
                'name' => 'required|string|max:255',
                'category' => 'required|string|in:' . implode(',', MealType::values()),
                'description' => 'nullable|string',
                'calories' => 'nullable|integer|min:0',
                'allergens' => 'nullable|array',
                'allergens.*' => 'string'
            ], [
                'name.required' => 'Az étel neve kötelező.',
                'name.max' => 'Az étel neve maximum 255 karakter lehet.',
                'category.required' => 'A kategória megadása kötelező.',
                'category.in' => 'Érvénytelen kategória.',
                'calories.min' => 'A kalóriaérték nem lehet negatív.'
            ]);
            
            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validációs hiba',
                    'errors' => $validator->errors()
                ], 422);
            }
            
            // Frissítés
            $meal->update([
                'mealName' => $request->name,
                'mealType' => $request->category,
                'description' => $request->description,
                'picture' => $request->picture ?? $meal->picture,
                // Ha van extra meződ, itt add hozzá
                // 'calories' => $request->calories ?? $meal->calories,
                // 'allergens' => $request->has('allergens') ? json_encode($request->allergens) : $meal->allergens
            ]);
            
            return response()->json([
                'success' => true,
                'message' => 'Étel sikeresen frissítve.',
                'meal' => [
                    'id' => $meal->id,
                    'name' => $meal->mealName,
                    'category' => $meal->mealType,
                    'description' => $meal->description,
                    'picture' => $meal->picture,
                    'updated_at' => $meal->updated_at
                ]
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Hiba történt az étel frissítése során.',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Ételtörlés
     */
    public function deleteMeal($id)
    {
        try {
            // Étel megkeresése
            $meal = Meal::find($id);
            
            if (!$meal) {
                return response()->json([
                    'success' => false,
                    'message' => 'Az étel nem található.'
                ], 404);
            }
            
            // Ellenőrizzük, hogy nincs-e menüben használva
            // Ehhez szükséged lesz a menuItems modellre
            /*
            if ($meal->menuItems()->count() > 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'Az étel nem törölhető, mert már szerepel a menükben.'
                ], 400);
            }
            */
            
            // Törlés
            $meal->delete();
            
            return response()->json([
                'success' => true,
                'message' => 'Étel sikeresen törölve.'
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Hiba történt az étel törlése során.',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Egy étel lekérdezése
     */
    public function showMeal($id)
    {
        try {
            $meal = Meal::find($id);
            
            if (!$meal) {
                return response()->json([
                    'success' => false,
                    'message' => 'Az étel nem található.'
                ], 404);
            }
            
            return response()->json([
                'success' => true,
                'meal' => [
                    'id' => $meal->id,
                    'name' => $meal->mealName,
                    'category' => $meal->mealType,
                    'description' => $meal->description,
                    'picture' => $meal->picture,
                    'created_at' => $meal->created_at,
                    'updated_at' => $meal->updated_at
                ]
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Hiba történt az étel betöltése során.',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Kategóriák listája
     */
    public function getCategories()
    {
        try {
            $categories = MealType::values();
            
            return response()->json([
                'success' => true,
                'categories' => $categories
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Hiba történt a kategóriák betöltése során.'
            ], 500);
        }
    }


    public function getMealIngredients($id)
    {
        try {
            Log::info('=== START getMealIngredients for ID: ' . $id . ' ===');
            
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
            
            // 2. Ellenőrizzük a relációt
            $ingredientsCount = $meal->ingredients()->count();
            Log::info('Ingredients count via relationship: ' . $ingredientsCount);
            
            // 3. Kétféle módon próbáljuk meg
            if ($ingredientsCount > 0) {
                // 3a. Metódus 1: withPivot használatával
                $ingredients = $meal->ingredients()->withPivot('amount', 'unit')->get();
                
                Log::info('Ingredients loaded with pivot: ' . $ingredients->count());
                
                $formattedIngredients = $ingredients->map(function($ingredient) {
                    Log::info('Processing ingredient: ' . $ingredient->id . ' - ' . $ingredient->name);
                    
                    return [
                        'id' => $ingredient->id,
                        'name' => $ingredient->name,
                        'ingredientType' => $ingredient->ingredientType,
                        'energy' => $ingredient->energy,
                        'protein' => $ingredient->protein,
                        'carbohydrate' => $ingredient->carbohydrate,
                        'fat' => $ingredient->fat,
                        'pivot' => [
                            'amount' => $ingredient->pivot ? $ingredient->pivot->amount : 0,
                            'unit' => $ingredient->pivot ? $ingredient->pivot->unit : 'g'
                        ]
                    ];
                });
            } else {
                // 3b. Metódus 2: Keresztül a pivot táblán
                Log::info('No ingredients via relationship, trying direct query...');
                
                $pivotData = DB::table('meal_ingredients')
                            ->where('meal_id', $id)
                            ->join('ingredients', 'meal_ingredients.ingredient_id', '=', 'ingredients.id')
                            ->select(
                                'ingredients.*',
                                'meal_ingredients.amount',
                                'meal_ingredients.unit'
                            )
                            ->get();
                
                Log::info('Direct query result count: ' . $pivotData->count());
                
                $formattedIngredients = $pivotData->map(function($item) {
                    return [
                        'id' => $item->id,
                        'name' => $item->name,
                        'ingredientType' => $item->ingredientType,
                        'energy' => $item->energy,
                        'protein' => $item->protein,
                        'carbohydrate' => $item->carbohydrate,
                        'fat' => $item->fat,
                        'pivot' => [
                            'amount' => $item->amount,
                            'unit' => $item->unit
                        ]
                    ];
                });
            }
            
            Log::info('=== END getMealIngredients ===');
            
            return response()->json([
                'success' => true,
                'meal' => [
                    'id' => $meal->id,
                    'name' => $meal->mealName,
                    'category' => $meal->mealType,
                    'description' => $meal->description
                ],
                'ingredients' => $formattedIngredients,
                'count' => $formattedIngredients->count(),
                'debug' => [
                    'meal_id' => $id,
                    'ingredients_count' => $formattedIngredients->count()
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

    public function getAllIngredients()
    {
        try {
            $ingredients = Ingredient::orderBy('name')->get();
            
            return response()->json([
                'success' => true,
                'ingredients' => $ingredients,
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
    
    // ÚJ METÓDUS: Ételi összetevők mentése/frissítése
    public function updateMealIngredients(Request $request, $id)
    {
        try {
            Log::info('=== START updateMealIngredients for meal ID: ' . $id . ' ===');
            Log::info('Request data: ', $request->all());
            
            // Étel ellenőrzése
            $meal = Meal::find($id);
            
            if (!$meal) {
                Log::warning('Meal not found with ID: ' . $id);
                return response()->json([
                    'success' => false,
                    'message' => 'Az étel nem található.'
                ], 404);
            }
            
            // Validáció
            $validator = Validator::make($request->all(), [
                'ingredients' => 'required|array',
                'ingredients.*.ingredient_id' => 'required|integer|exists:ingredients,id',
                'ingredients.*.amount' => 'required|numeric|min:0',
                'ingredients.*.unit' => 'required|string|max:10'
            ], [
                'ingredients.required' => 'Az összetevők megadása kötelező.',
                'ingredients.*.ingredient_id.required' => 'A hozzávaló ID megadása kötelező.',
                'ingredients.*.ingredient_id.exists' => 'A megadott hozzávaló nem létezik.',
                'ingredients.*.amount.required' => 'A mennyiség megadása kötelező.',
                'ingredients.*.amount.min' => 'A mennyiség nem lehet negatív.',
                'ingredients.*.unit.required' => 'A mértékegység megadása kötelező.'
            ]);
            
            if ($validator->fails()) {
                Log::warning('Validation failed: ', $validator->errors()->toArray());
                return response()->json([
                    'success' => false,
                    'message' => 'Validációs hiba',
                    'errors' => $validator->errors()
                ], 422);
            }
            
            // Töröljük a régi összetevőket
            Log::info('Deleting old ingredients for meal ID: ' . $id);
            $deletedCount = DB::table('meal_ingredients')->where('meal_id', $id)->delete();
            Log::info('Deleted ' . $deletedCount . ' old ingredients');
            
            // Új összetevők hozzáadása
            $ingredientsData = [];
            foreach ($request->ingredients as $ingredient) {
                $ingredientsData[] = [
                    'meal_id' => $id,
                    'ingredient_id' => $ingredient['ingredient_id'],
                    'amount' => $ingredient['amount'],
                    'unit' => $ingredient['unit'],
                    // NE adjuk hozzá a created_at és updated_at mezőket, ha nincsenek a táblában
                    // 'created_at' => now(),
                    // 'updated_at' => now()
                ];
            }
            
            Log::info('Ingredients to insert: ', $ingredientsData);
            
            if (!empty($ingredientsData)) {
                try {
                    DB::table('meal_ingredients')->insert($ingredientsData);
                    Log::info('Successfully inserted ' . count($ingredientsData) . ' ingredients');
                } catch (\Exception $insertError) {
                    Log::error('Insert failed: ' . $insertError->getMessage());
                    Log::error('Insert error details: ', [
                        'error' => $insertError->getMessage(),
                        'trace' => $insertError->getTraceAsString()
                    ]);
                    throw $insertError;
                }
            }
            
            Log::info('=== END updateMealIngredients ===');
            
            return response()->json([
                'success' => true,
                'message' => 'Összetevők sikeresen frissítve.',
                'ingredients_count' => count($ingredientsData)
            ]);
            
        } catch (\Exception $e) {
            Log::error('CRITICAL ERROR in updateMealIngredients: ' . $e->getMessage());
            Log::error('File: ' . $e->getFile());
            Log::error('Line: ' . $e->getLine());
            Log::error('Trace: ' . $e->getTraceAsString());
            
            return response()->json([
                'success' => false,
                'message' => 'Hiba történt az összetevők frissítése során.',
                'error' => config('app.debug') ? $e->getMessage() : null,
                'debug_info' => config('app.debug') ? [
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                    'error_type' => get_class($e)
                ] : null
            ], 500);
        }
    }
    
    // ÚJ METÓDUS: Hozzávaló keresése
    public function searchIngredients(Request $request)
    {
        try {
            $query = Ingredient::query();
            
            if ($request->has('search') && !empty($request->search)) {
                $search = $request->search;
                $query->where(function($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhere('ingredientType', 'like', "%{$search}%");
                });
            }
            
            $ingredients = $query->orderBy('name')->limit(20)->get();
            
            return response()->json([
                'success' => true,
                'ingredients' => $ingredients,
                'count' => $ingredients->count()
            ]);
            
        } catch (\Exception $e) {
            Log::error('Search ingredients error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Hiba történt a keresés során.'
            ], 500);
        }
    }
}