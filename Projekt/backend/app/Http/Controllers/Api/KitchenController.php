<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Models\Meal;
use App\Enums\MealType;

class KitchenController extends Controller
{
    //Összes étel lekérdezése
    public function getMeals(Request $request)
    {
        try {
            $perPage = $request->get('per_page', 15);
            $search = $request->get('search', '');
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
            
            // Rendezés
            $query->orderBy('mealName');
            
            $meals = $query->get();
            
            // Átalakítás a Vue komponens elvárásainak megfelelően
            $formattedMeals = $meals->map(function ($meal) {
                return [
                    'id' => $meal->id,
                    'name' => $meal->mealName,
                    'category' => $meal->mealType,
                    'description' => $meal->description,
                    'picture' => $meal->picture,
                    'created_at' => $meal->created_at,
                    'updated_at' => $meal->updated_at
                ];
            });
            
            return response()->json([
                'success' => true,
                'meals' => $formattedMeals,
                'count' => $formattedMeals->count(),
                'message' => 'Ételek sikeresen betöltve.'
            ]);
            
        } catch (\Exception $e) {
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
}