<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class MenuController extends Controller
{
    // Mai menü lekérdezése
    public function getTodayMenu(Request $request)
    {
        try {
            $today = Carbon::today()->toDateString();
            
            // Dummy adatok - később cseréld ki valós adatokra
            $dummyMenu = [
                [
                    'id' => 1,
                    'mealype' => 'reggeli',
                    'meal' => [
                        'name' => 'Pogácsa tejjel',
                        'price' => 450,
                        'description' => 'Házi pogácsa friss tejjel',
                        'category' => 'Reggeli',
                        'allergens' => ['glutén', 'tej'],
                        'is_vegetarian' => true
                    ]
                ],
                [
                    'id' => 2,
                    'meal_type' => 'ebéd',
                    'meal' => [
                        'name' => 'Húsleves',
                        'price' => 750,
                        'description' => 'Házi húsleves cérnametélttel',
                        'category' => 'Leves',
                        'allergens' => [],
                        'is_vegetarian' => false
                    ]
                ],
                [
                    'id' => 3,
                    'meal_type' => 'ebéd',
                    'meal' => [
                        'name' => 'Rántott csirkemell',
                        'price' => 1200,
                        'description' => 'Rántott csirkemell hasábburgonyával',
                        'category' => 'Főétel',
                        'allergens' => ['glutén', 'tojás'],
                        'is_vegetarian' => false
                    ]
                ],
                [
                    'id' => 4,
                    'meal_type' => 'vacsora',
                    'meal' => [
                        'name' => 'Zöldséges quiche',
                        'price' => 950,
                        'description' => 'Zöldséges quiche friss salátával',
                        'category' => 'Főétel',
                        'allergens' => ['glutén', 'tej', 'tojás'],
                        'is_vegetarian' => true
                    ]
                ]
            ];
            
            // Csoportosítás típus szerint
            $groupedMenu = [
                'reggeli' => array_values(array_filter($dummyMenu, fn($item) => $item['meal_type'] === 'reggeli')),
                'ebéd' => array_values(array_filter($dummyMenu, fn($item) => $item['meal_type'] === 'ebéd')),
                'vacsora' => array_values(array_filter($dummyMenu, fn($item) => $item['meal_type'] === 'vacsora'))
            ];
            
            return response()->json([
                'success' => true,
                'today' => $today,
                'menu_items' => $dummyMenu,
                'grouped_menu' => $groupedMenu,
                'message' => 'Mai menü sikeresen betöltve'
            ]);
            
        } catch (\Exception $e) {
            Log::error('Menu fetch error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Hiba történt a menü betöltése során'
            ], 500);
        }
    }
    
    // Heti menü lekérdezése
    public function getWeeklyMenu(Request $request)
    {
        try {
            $startOfWeek = Carbon::now()->startOfWeek();
            $endOfWeek = Carbon::now()->endOfWeek();
            
            // Dummy heti menü
            $weeklyMenu = [];
            $currentDate = $startOfWeek->copy();
            
            while ($currentDate <= $endOfWeek) {
                $dayName = $currentDate->locale('hu')->dayName;
                $dateStr = $currentDate->toDateString();
                
                $weeklyMenu[$dateStr] = [
                    'day' => ucfirst($dayName),
                    'date' => $dateStr,
                    'menu' => [
                        [
                            'id' => rand(100, 999),
                            'meal_type' => 'ebéd',
                            'meal' => [
                                'name' => 'Ebéd ' . $dayName . ' napra',
                                'price' => 1000 + rand(0, 500),
                                'description' => 'Napi ajánlat',
                                'category' => 'Főétel'
                            ]
                        ],
                        [
                            'id' => rand(100, 999),
                            'meal_type' => 'reggeli',
                            'meal' => [
                                'name' => 'Reggeli ' . $dayName,
                                'price' => 500 + rand(0, 200),
                                'description' => 'Reggeli ajánlat',
                                'category' => 'Reggeli'
                            ]
                        ]
                    ]
                ];
                
                $currentDate->addDay();
            }
            
            return response()->json([
                'success' => true,
                'week_start' => $startOfWeek->toDateString(),
                'week_end' => $endOfWeek->toDateString(),
                'weekly_menu' => $weeklyMenu,
                'message' => 'Heti menü sikeresen betöltve'
            ]);
            
        } catch (\Exception $e) {
            Log::error('Weekly menu error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Hiba történt a heti menü betöltése során'
            ], 500);
        }
    }
}