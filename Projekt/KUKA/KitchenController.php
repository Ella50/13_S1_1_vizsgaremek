<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class KitchenController extends Controller
{
    
    // Ételek listázása
    public function getMeals(Request $request)
    {
        try {
            // Dummy adatok
            $dummyMeals = [
                [
                    'id' => 1,
                    'name' => 'Pogácsa tejjel',
                    'category' => 'Reggeli',
                    'price' => 450,
                    'description' => 'Házi pogácsa friss tejjel',
                    'allergens' => ['glutén', 'tej'],
                    'is_vegetarian' => true,
                    'is_vegan' => false,
                    'is_gluten_free' => false,
                    'created_at' => '2024-01-15 10:30:00'
                ],
                [
                    'id' => 2,
                    'name' => 'Húsleves',
                    'category' => 'Leves',
                    'price' => 750,
                    'description' => 'Házi húsleves cérnametélttel',
                    'allergens' => [],
                    'is_vegetarian' => false,
                    'is_vegan' => false,
                    'is_gluten_free' => true,
                    'created_at' => '2024-01-15 11:00:00'
                ],
                [
                    'id' => 3,
                    'name' => 'Rántott csirkemell',
                    'category' => 'Főétel',
                    'price' => 1200,
                    'description' => 'Rántott csirkemell hasábburgonyával',
                    'allergens' => ['glutén', 'tojás'],
                    'is_vegetarian' => false,
                    'is_vegan' => false,
                    'is_gluten_free' => false,
                    'created_at' => '2024-01-14 09:45:00'
                ],
                [
                    'id' => 4,
                    'name' => 'Zöldséges quiche',
                    'category' => 'Főétel',
                    'price' => 950,
                    'description' => 'Zöldséges quiche friss salátával',
                    'allergens' => ['glutén', 'tej', 'tojás'],
                    'is_vegetarian' => true,
                    'is_vegan' => false,
                    'is_gluten_free' => false,
                    'created_at' => '2024-01-13 14:20:00'
                ]
            ];
            
            // Keresés
            $search = $request->get('search', '');
            $filteredMeals = $dummyMeals;
            
            if ($search) {
                $filteredMeals = array_filter($dummyMeals, function($meal) use ($search) {
                    return stripos($meal['name'], $search) !== false || 
                           stripos($meal['description'], $search) !== false;
                });
                $filteredMeals = array_values($filteredMeals);
            }
            
            // Kategória szűrés
            if ($request->has('category')) {
                $filteredMeals = array_filter($filteredMeals, function($meal) use ($request) {
                    return $meal['category'] === $request->category;
                });
                $filteredMeals = array_values($filteredMeals);
            }
            
            return response()->json([
                'success' => true,
                'meals' => $filteredMeals,
                'total' => count($filteredMeals),
                'message' => 'Ételek sikeresen betöltve'
            ]);
            
        } catch (\Exception $e) {
            Log::error('Kitchen meals error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Hiba történt az ételek betöltése során'
            ], 500);
        }
    }
    
    // Új étel létrehozása
    public function createMeal(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'name' => 'required|string|max:255',
                'category' => 'required|in:Reggeli,Leves,Főétel,Köret,Saláta,Desszert,Ital',
                'price' => 'required|numeric|min:0',
                'description' => 'nullable|string',
                'allergens' => 'nullable|array',
                'is_vegetarian' => 'boolean',
                'is_vegan' => 'boolean',
                'is_gluten_free' => 'boolean',
            ]);
            
            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'errors' => $validator->errors()
                ], 422);
            }
            
            // Mivel nincs Meal modell, csak visszaadjuk az adatokat
            $meal = array_merge($request->all(), [
                'id' => rand(1000, 9999),
                'created_at' => now()->toDateTimeString(),
                'updated_at' => now()->toDateTimeString()
            ]);
            
            Log::info('Meal created (dummy)', [
                'meal' => $meal,
                'created_by' => $request->user()->id
            ]);
            
            return response()->json([
                'success' => true,
                'message' => 'Étel sikeresen létrehozva',
                'meal' => $meal
            ], 201);
            
        } catch (\Exception $e) {
            Log::error('Create meal error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Hiba történt az étel létrehozása során'
            ], 500);
        }
    }
    
    // Mai rendelések lekérdezése
    public function getTodayOrders(Request $request)
    {
        try {
            // Dummy rendelések
            $dummyOrders = [
                'reggeli' => [
                    [
                        'id' => 101,
                        'user' => [
                            'id' => 1,
                            'firstName' => 'János',
                            'lastName' => 'Kovács',
                            'class' => '10.A'
                        ],
                        'meal' => [
                            'id' => 1,
                            'name' => 'Pogácsa tejjel',
                            'price' => 450
                        ],
                        'status' => 'pending',
                        'special_requests' => 'Extra tej legyen',
                        'created_at' => today()->setTime(7, 30)->toDateTimeString()
                    ]
                ],
                'ebéd' => [
                    [
                        'id' => 102,
                        'user' => [
                            'id' => 2,
                            'firstName' => 'Anna',
                            'lastName' => 'Nagy',
                            'class' => '11.B'
                        ],
                        'meal' => [
                            'id' => 2,
                            'name' => 'Húsleves',
                            'price' => 750
                        ],
                        'status' => 'confirmed',
                        'special_requests' => null,
                        'created_at' => today()->setTime(10, 15)->toDateTimeString()
                    ],
                    [
                        'id' => 103,
                        'user' => [
                            'id' => 3,
                            'firstName' => 'Bence',
                            'lastName' => 'Tóth',
                            'class' => '9.A'
                        ],
                        'meal' => [
                            'id' => 3,
                            'name' => 'Rántott csirkemell',
                            'price' => 1200
                        ],
                        'status' => 'pending',
                        'special_requests' => 'Extra rizs',
                        'created_at' => today()->setTime(9, 45)->toDateTimeString()
                    ]
                ],
                'vacsora' => [
                    [
                        'id' => 104,
                        'user' => [
                            'id' => 4,
                            'firstName' => 'Erika',
                            'lastName' => 'Kiss',
                            'class' => '12.A'
                        ],
                        'meal' => [
                            'id' => 4,
                            'name' => 'Zöldséges quiche',
                            'price' => 950
                        ],
                        'status' => 'prepared',
                        'special_requests' => 'Vega opció',
                        'created_at' => today()->setTime(16, 20)->toDateTimeString()
                    ]
                ]
            ];
            
            // Összesítés
            $summary = [
                'reggeli' => count($dummyOrders['reggeli']),
                'ebéd' => count($dummyOrders['ebéd']),
                'vacsora' => count($dummyOrders['vacsora']),
                'total' => count($dummyOrders['reggeli']) + 
                          count($dummyOrders['ebéd']) + 
                          count($dummyOrders['vacsora']),
                'by_status' => [
                    'pending' => 2,
                    'confirmed' => 1,
                    'prepared' => 1,
                    'delivered' => 0,
                    'cancelled' => 0
                ]
            ];
            
            return response()->json([
                'success' => true,
                'date' => now()->toDateString(),
                'summary' => $summary,
                'orders' => $dummyOrders,
                'message' => 'Mai rendelések sikeresen betöltve'
            ]);
            
        } catch (\Exception $e) {
            Log::error('Today orders error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Hiba történt a rendelések betöltése során'
            ], 500);
        }
    }
}