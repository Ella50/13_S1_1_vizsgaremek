<?php
//Kitchen: ételek kezelése (hozzáadás, allergének, összetevők szerkesztése)
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
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use App\Models\MenuItem;


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
    // FONTOS: Ellenőrizd, hogy a storage link megfelelően van-e beállítva!
    return asset('storage/' . ltrim($iconPath, '/'));
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
    

    
    /**
     * Új étel létrehozása
     */
    public function storeMeal(Request $request)
    {
        try {
            Log::info('=== START storeMeal ===');
            Log::info('Request data:', $request->all());
            
            // Validáció alapadatokhoz
            $validator = Validator::make($request->all(), [
                'mealName' => 'required|string|max:255',
                'category' => 'required|string|in:Leves,Főétel,Egyéb',
                'description' => 'nullable|string',
                // Összetevők validálása, ha küldik
                'ingredients' => 'nullable|array',
                'ingredients.*.ingredient_id' => 'exists:ingredients,id',
                'ingredients.*.amount' => 'numeric|min:0.1',
                'ingredients.*.unit' => 'string|max:10'
            ], [
                'mealName.required' => 'Az étel neve kötelező.',
                'mealName.max' => 'Az étel neve maximum 255 karakter lehet.',
                'category.required' => 'A kategória megadása kötelező.',
                'category.in' => 'Érvénytelen kategória. Érvényes értékek: Leves, Főétel, Egyéb'
            ]);
            
            if ($validator->fails()) {
                Log::warning('Validation failed:', $validator->errors()->toArray());
                return response()->json([
                    'success' => false,
                    'message' => 'Validációs hibák',
                    'errors' => $validator->errors()
                ], 422);
            }
            
            // Étel létrehozása
            $meal = Meal::create([
                'mealName' => $request->mealName,
                'mealType' => $request->category,
                'description' => $request->description ?? '',
                'picture' => null,
                'isAvailable' => true
            ]);
            
            Log::info('Meal created successfully:', ['id' => $meal->id, 'name' => $meal->mealName]);
            
            // Ha vannak összetevők, hozzáadjuk őket
            $ingredientsCount = 0;
            if ($request->has('ingredients') && is_array($request->ingredients) && count($request->ingredients) > 0) {
                $ingredientsData = [];
                foreach ($request->ingredients as $ingredient) {
                    if (isset($ingredient['ingredient_id']) && isset($ingredient['amount'])) {
                        $ingredientsData[$ingredient['ingredient_id']] = [
                            'amount' => $ingredient['amount'],
                            'unit' => $ingredient['unit'] ?? 'g'
                        ];
                    }
                }
                
                if (!empty($ingredientsData)) {
                    $meal->ingredients()->attach($ingredientsData);
                    $ingredientsCount = count($ingredientsData);
                    Log::info('Ingredients attached to meal:', [
                        'meal_id' => $meal->id,
                        'ingredients_count' => $ingredientsCount
                    ]);
                }
            }
            
            return response()->json([
                'success' => true,
                'message' => 'Étel sikeresen hozzáadva.' . ($ingredientsCount > 0 ? " ($ingredientsCount összetevő hozzáadva)" : ''),
                'meal' => [
                    'id' => $meal->id,
                    'mealName' => $meal->mealName,
                    'category' => $meal->mealType,
                    'description' => $meal->description,
                    'ingredients_count' => $ingredientsCount,
                    'created_at' => $meal->created_at,
                    'updated_at' => $meal->updated_at
                ]
            ], 201);
            
        } catch (\Exception $e) {
            Log::error('CRITICAL ERROR in storeMeal: ' . $e->getMessage());
            Log::error('File: ' . $e->getFile());
            Log::error('Line: ' . $e->getLine());
            Log::error('Trace: ' . $e->getTraceAsString());
            
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
                'mealName' => 'required|string|max:255',
                'category' => 'required|string|in:Leves,Főétel,Egyéb' ,//. implode(',', MealType::values()),
                'description' => 'nullable|string',
                'calories' => 'nullable|integer|min:0',
                'allergens' => 'nullable|array',
                'allergens.*' => 'string'
            ], [
                'mealName.required' => 'Az étel neve kötelező.',
                'mealName.max' => 'Az étel neve maximum 255 karakter lehet.',
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
                'mealName' => $request->mealName,
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
                    'mealName' => $meal->mealName,
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
            Log::info('=== START deleteMeal ===');
            Log::info('Attempting to delete meal with ID: ' . $id);
            
            // Étel megkeresése
            $meal = Meal::find($id);
            
            if (!$meal) {
                Log::warning('Meal not found with ID: ' . $id);
                return response()->json([
                    'success' => false,
                    'message' => 'Az étel nem található.'
                ], 404);
            }
            
            // Ellenőrizzük, hogy szerepel-e menükben
            // Itt használjuk a Meal modell új metódusát vagy direkt query-t
            $menuItemCount = MenuItem::where('soup', $id)
                ->orWhere('optionA', $id)
                ->orWhere('optionB', $id)
                ->orWhere('other', $id)
                ->count();
            
            if ($menuItemCount > 0) {
                // Megkeressük a konkrét dátumokat
                $menuItems = MenuItem::where('soup', $id)
                    ->orWhere('optionA', $id)
                    ->orWhere('optionB', $id)
                    ->orWhere('other', $id)
                    ->get(['day']);
                    
                $dates = $menuItems->pluck('day')->map(function($date) {
                    return \Carbon\Carbon::parse($date)->format('Y-m-d');
                })->toArray();
                
                $datesStr = implode(', ', array_slice($dates, 0, 5)); // Max 5 dátumot mutatunk
                
                if (count($dates) > 5) {
                    $datesStr .= '... (+' . (count($dates) - 5) . ' további)';
                }
                
                Log::warning('Cannot delete meal: it is used in menu items', [
                    'meal_id' => $meal->id,
                    'meal_name' => $meal->mealName,
                    'menu_items_count' => $menuItemCount,
                    'menu_dates' => $dates
                ]);
                
                return response()->json([
                    'success' => false,
                    'message' => 'Az étel nem törölhető, mert szerepel ' . $menuItemCount . 
                            ' menüben. (Dátumok: ' . $datesStr . ')'
                ], 400);
            }
            
            // Először töröljük az összetevőket
            if ($meal->ingredients()->count() > 0) {
                $ingredientsCount = $meal->ingredients()->count();
                $meal->ingredients()->detach();
                Log::info('Detached ingredients from meal', [
                    'meal_id' => $meal->id,
                    'ingredients_count' => $ingredientsCount
                ]);
            }
            
            // Az étel adatainak mentése logoláshoz
            $mealData = [
                'id' => $meal->id,
                'name' => $meal->mealName,
                'category' => $meal->mealType,
                'created_at' => $meal->created_at,
                'ingredients_count' => $ingredientsCount ?? 0
            ];
            
            // Törlés
            $meal->delete();
            
            Log::info('Meal deleted successfully', $mealData);
            Log::info('=== END deleteMeal ===');
            
            return response()->json([
                'success' => true,
                'message' => 'Étel sikeresen törölve.',
                'deleted_meal' => $mealData
            ]);
            
        } catch (\Exception $e) {
            Log::error('CRITICAL ERROR in deleteMeal: ' . $e->getMessage());
            Log::error('File: ' . $e->getFile());
            Log::error('Line: ' . $e->getLine());
            Log::error('Trace: ' . $e->getTraceAsString());
            
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
                    'mealName' => $meal->mealName,
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

    public function updateMealIngredients($id, Request $request)
{
    try {
        Log::info('=== START updateMealIngredients ===');
        Log::info('Meal ID: ' . $id);
        Log::info('Request data:', $request->all());
        
        // Étellellenőrzés
        $meal = Meal::find($id);
        
        if (!$meal) {
            return response()->json([
                'success' => false,
                'message' => 'Az étel nem található.'
            ], 404);
        }
        
        // Validáció
        $validator = Validator::make($request->all(), [
            'ingredients' => 'required|array',
            'ingredients.*.ingredient_id' => 'required|exists:ingredients,id',
            'ingredients.*.amount' => 'required|numeric|min:0.1',
            'ingredients.*.unit' => 'required|string|max:10'
        ]);
        
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validációs hibák',
                'errors' => $validator->errors()
            ], 422);
        }
        
        // Meglévő összetevők törlése
        $meal->ingredients()->detach();
        
        // Új összetevők hozzáadása
        $ingredientsData = [];
        foreach ($request->ingredients as $ingredient) {
            $ingredientsData[$ingredient['ingredient_id']] = [
                'amount' => $ingredient['amount'],
                'unit' => $ingredient['unit']
            ];
        }
        
        $meal->ingredients()->attach($ingredientsData);
        
        Log::info('Meal ingredients updated successfully:', [
            'meal_id' => $meal->id,
            'ingredients_count' => count($ingredientsData)
        ]);
        
        // Betöltjük a frissített adatokat allergénekkel
        $updatedMeal = Meal::with(['ingredients.allergens' => function($query) {
            $query->select('id', 'allergenName', 'icon');
        }])->find($id);
        
        // Összegyűjtjük az allergéneket
        $allAllergens = collect();
        foreach ($updatedMeal->ingredients as $ingredient) {
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
        })->values()->toArray();
        
        return response()->json([
            'success' => true,
            'message' => 'Összetevők sikeresen frissítve.',
            'ingredients_count' => count($ingredientsData),
            'allergens' => $uniqueAllergens  // Visszaadjuk az allergéneket is
        ]);
        
    } catch (\Exception $e) {
        Log::error('CRITICAL ERROR in updateMealIngredients: ' . $e->getMessage());
        return response()->json([
            'success' => false,
            'message' => 'Hiba történt az összetevők frissítése során.',
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
    

    //Hozzávalók kezelése (ingredient.vue)

     public function getIngredientsList(Request $request)
    {
        // Csak konyha és admin jogosultság
        $user = Auth::user();
        if (!in_array($user->userType, ['Konyha', 'Admin'])) {
            return response()->json([
                'message' => 'Unauthorized'
            ], Response::HTTP_FORBIDDEN);
        }

        $query = Ingredient::query();

        // Szűrés típus szerint
        if ($request->has('type') && $request->type !== 'all') {
            $query->where('ingredientType', $request->type);
        }

        // Szűrés elérhetőség szerint
        if ($request->has('availability')) {
            $query->where('isAvailable', $request->availability === 'available');
        }

        // Keresés név szerint
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('ingredientName', 'LIKE', "%{$search}%");
            });
        }

        // Rendezés
        $sortBy = $request->get('sort_by', 'ingredientName');
        $sortOrder = $request->get('sort_order', 'asc');
        $query->orderBy($sortBy, $sortOrder);

        $perPage = $request->get('per_page', 20);
        $ingredients = $query->paginate($perPage);

        return response()->json([
            'data' => $ingredients->items(),
            'meta' => [
                'current_page' => $ingredients->currentPage(),
                'last_page' => $ingredients->lastPage(),
                'per_page' => $ingredients->perPage(),
                'total' => $ingredients->total(),
            ]
        ]);
    }

    /**
     * Új hozzávaló létrehozása
     */
    public function createIngredient(Request $request)
    {
        // Csak konyha és admin jogosultság
        $user = Auth::user();
        if (!in_array($user->userType, ['Konyha', 'Admin'])) {
            return response()->json([
                'message' => 'Unauthorized'
            ], Response::HTTP_FORBIDDEN);
        }

        $validated = $request->validate([
            'ingredientName' => 'required|string|max:255|unique:ingredients',
            'ingredientType' => 'required|in:Egyéb,Hús,Hal,Tejtermék,Zöldség,Gyümölcs,Fűszer',
            'energy' => 'nullable|integer|min:0',
            'protein' => 'nullable|integer|min:0',
            'carbohydrate' => 'nullable|integer|min:0',
            'fat' => 'nullable|integer|min:0',
            'sodium' => 'nullable|integer|min:0',
            'sugar' => 'nullable|integer|min:0',
            'fiber' => 'nullable|integer|min:0',
            'isAvailable' => 'boolean'
        ]);

        try {
            $ingredient = Ingredient::create($validated);

            return response()->json([
                'message' => 'Hozzávaló sikeresen létrehozva',
                'data' => $ingredient
            ], Response::HTTP_CREATED);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Hiba történt a hozzávaló létrehozása során',
                'error' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Egy hozzávaló megjelenítése (új név)
     */
    public function showIngredientDetail($id)
    {
        // Csak konyha és admin jogosultság
        $user = Auth::user();
        if (!in_array($user->userType, ['Konyha', 'Admin'])) {
            return response()->json([
                'message' => 'Unauthorized'
            ], Response::HTTP_FORBIDDEN);
        }

        $ingredient = Ingredient::findOrFail($id);

        return response()->json([
            'data' => $ingredient
        ]);
    }

    /**
     * Hozzávaló frissítése
     */
    public function updateIngredientItem(Request $request, $id)
    {
        // Csak konyha és admin jogosultság
        $user = Auth::user();
        if (!in_array($user->userType, ['Konyha', 'Admin'])) {
            return response()->json([
                'message' => 'Unauthorized'
            ], Response::HTTP_FORBIDDEN);
        }

        $ingredient = Ingredient::findOrFail($id);

        $validated = $request->validate([
            'ingredientName' => 'sometimes|required|string|max:255|unique:ingredients,ingredientName,' . $id,
            'ingredientType' => 'sometimes|required|in:Egyéb,Hús,Hal,Tejtermék,Zöldség,Gyümölcs,Fűszer',
            'energy' => 'nullable|integer|min:0',
            'protein' => 'nullable|integer|min:0',
            'carbohydrate' => 'nullable|integer|min:0',
            'fat' => 'nullable|integer|min:0',
            'sodium' => 'nullable|integer|min:0',
            'sugar' => 'nullable|integer|min:0',
            'fiber' => 'nullable|integer|min:0',
            'isAvailable' => 'boolean'
        ]);

        try {
            $ingredient->update($validated);

            return response()->json([
                'message' => 'Hozzávaló sikeresen frissítve',
                'data' => $ingredient
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Hiba történt a hozzávaló frissítése során',
                'error' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Hozzávaló törlése (új név)
     */
    public function deleteIngredientItem($id)
    {
        // Csak konyha és admin jogosultság
        $user = Auth::user();
        if (!in_array($user->userType, ['Konyha', 'Admin'])) {
            return response()->json([
                'message' => 'Unauthorized'
            ], Response::HTTP_FORBIDDEN);
        }

        $ingredient = Ingredient::findOrFail($id);

        // Ellenőrizzük, hogy használatban van-e
        if ($ingredient->meals()->count() > 0) {
            return response()->json([
                'message' => 'Nem törölhető, mert használatban van étel(ek)ben',
                'count' => $ingredient->meals()->count()
            ], Response::HTTP_CONFLICT);
        }

        try {
            $ingredient->delete();

            return response()->json([
                'message' => 'Hozzávaló sikeresen törölve'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Hiba történt a hozzávaló törlése során',
                'error' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Tömeges elérhetőség frissítés
     */
    public function bulkUpdateIngredientAvailability(Request $request)
    {
        // Csak konyha és admin jogosultság
        $user = Auth::user();
        if (!in_array($user->userType, ['Konyha', 'Admin'])) {
            return response()->json([
                'message' => 'Unauthorized'
            ], Response::HTTP_FORBIDDEN);
        }

        $validated = $request->validate([
            'ingredient_ids' => 'required|array',
            'ingredient_ids.*' => 'exists:ingredients,id',
            'isAvailable' => 'required|boolean'
        ]);

        try {
            $updatedCount = Ingredient::whereIn('id', $validated['ingredient_ids'])
                ->update(['isAvailable' => $validated['isAvailable']]);

            return response()->json([
                'message' => "{$updatedCount} hozzávaló elérhetősége frissítve",
                'updated_count' => $updatedCount
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Hiba történt a tömeges frissítés során',
                'error' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }


}