<?php

namespace App\Http\Controllers\Api;

use App\Models\Order;
use App\Models\MenuItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\Controller; 

class OrdersController extends Controller 
{
    // Helper metódusok
    protected function success($data = [], $message = 'Success')
    {
        return response()->json([
            'success' => true,
            'data' => $data,  
            'message' => $message
        ]);
    }
    
    protected function unauthorized($message = 'Unauthorized access')
    {
        return response()->json([
            'success' => false,
            'message' => $message
        ], 403);
    }
    
    /**
     * Összes rendelés listázása (konyha/admin)
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        if (!in_array($user->userType, ['Konyha', 'Admin'])) {
            return $this->unauthorized();
        }
        
        // Alap query
        $query = Order::with([
            'user:id,userType',
            'menuItem' => function($q) {
                $q->with(['soupMeal', 'optionAMeal', 'optionBMeal', 'otherMeal']);
            },
            'price'
        ]);
        
        // Szűrés státusz alapján
        if ($request->has('status') && $request->status !== 'all') {
            $query->where('orderStatus', $request->status);
        }
        
        // Szűrés dátum alapján
        if ($request->has('start_date') && $request->has('end_date')) {
            $query->whereBetween('orderDate', [$request->start_date, $request->end_date]);
        }
        
        // Szűrés hónap és év alapján
        if ($request->has('year') && $request->has('month')) {
            $startDate = "{$request->year}-{$request->month}-01";
            $endDate = date('Y-m-t', strtotime($startDate));
            $query->whereBetween('orderDate', [$startDate, $endDate]);
        }
        
        // Lapozás
        $pageSize = $request->per_page ?? 25;
        $orders = $query->orderBy('orderDate', 'desc')
            ->orderBy('created_at', 'desc')
            ->paginate($pageSize);
            
        // Összegző adatok
        $summary = $this->getSummary($orders->items());
        
        return $this->success([
            'orders' => $orders->items(),
            'pagination' => [
                'current_page' => $orders->currentPage(),
                'total' => $orders->total(),
                'per_page' => $orders->perPage(),
                'last_page' => $orders->lastPage()
            ],
            'summary' => $summary,
            'filters' => $request->all()
        ]);
    }
    
    /**
     * Dátum szerinti összesítés
     */
    public function getByDate($date)
{
    $user = Auth::user();
    if (!in_array($user->userType, ['Konyha', 'Admin'])) {
        return $this->unauthorized();
    }
    
    $orders = Order::whereDate('orderDate', $date)
        ->with([
            'user:id,userType',
            'menuItem' => function($q) {
                $q->with(['soupMeal', 'optionAMeal', 'optionBMeal', 'otherMeal']);
            },
            'price'
        ])
        ->get();
        
    $menu = MenuItem::whereDate('day', $date)
        ->with(['soupMeal', 'optionAMeal', 'optionBMeal', 'otherMeal'])
        ->first();
        
    $summary = $this->getDetailedSummary($orders);
    
    return $this->success([
        'date' => $date,
        'menu' => $menu,
        'orders' => $orders,
        'summary' => $summary,
        'total_count' => $orders->count(),
        'export_data' => $this->prepareExportData($orders, $menu)
    ]);
}
    
    /**
     * Mai rendelések összesítése
     */
    public function getTodaySummary()
    {
        $user = Auth::user();
        if (!in_array($user->userType, ['Konyha', 'Admin'])) {
            return $this->unauthorized();
        }
        
        $today = now()->format('Y-m-d');
        
        $orders = Order::whereDate('orderDate', $today)
            ->where('orderStatus', 'Rendelve')
            ->with(['user:id', 'price'])
            ->get();
            
        $menu = MenuItem::whereDate('day', $today)
            ->with(['soupMeal', 'optionAMeal', 'optionBMeal', 'otherMeal'])
            ->first();
            
        $summary = $this->getDetailedSummary($orders);
        
        return $this->success([
            'date' => $today,
            'menu' => $menu,
            'orders' => $orders,
            'summary' => $summary,
            'total_orders' => $orders->count(),
            'by_option' => $orders->groupBy('selectedOption')->map->count(),
        ]);
    }
    
    /**
     * Előkészületi lista generálása
     */
    public function getPreparationList($date)
    {
        $user = Auth::user();
        if (!in_array($user->userType, ['Konyha', 'Admin'])) {
            return $this->unauthorized();
        }
        
        $orders = Order::whereDate('orderDate', $date)
            ->where('orderStatus', 'Rendelve')
            ->with(['menuItem' => function($q) {
                $q->with(['soupMeal.ingredients', 'optionAMeal.ingredients', 'optionBMeal.ingredients']);
            }])
            ->get();
            
        // Ételek számlálása
        $mealCounts = [
            'soup' => [],
            'optionA' => [],
            'optionB' => []
        ];
        
        $ingredientRequirements = [];
        
        foreach ($orders as $order) {
            if (!$order->menuItem) continue;
            
            // Leves
            if ($order->menuItem->soup && $order->menuItem->soupMeal) {
                $soupId = $order->menuItem->soup;
                $mealCounts['soup'][$soupId] = ($mealCounts['soup'][$soupId] ?? 0) + 1;
                
                // Hozzávalók összegzése
                if ($order->menuItem->soupMeal && $order->menuItem->soupMeal->ingredients) {
                    $this->sumIngredients($ingredientRequirements, $order->menuItem->soupMeal, 1);
                }
            }
            
            // A opció
            if ($order->selectedOption === 'A' && $order->menuItem->optionA && $order->menuItem->optionAMeal) {
                $mealId = $order->menuItem->optionA;
                $mealCounts['optionA'][$mealId] = ($mealCounts['optionA'][$mealId] ?? 0) + 1;
                
                // Hozzávalók összegzése
                if ($order->menuItem->optionAMeal && $order->menuItem->optionAMeal->ingredients) {
                    $this->sumIngredients($ingredientRequirements, $order->menuItem->optionAMeal, 1);
                }
            }
            
            // B opció
            if ($order->selectedOption === 'B' && $order->menuItem->optionB && $order->menuItem->optionBMeal) {
                $mealId = $order->menuItem->optionB;
                $mealCounts['optionB'][$mealId] = ($mealCounts['optionB'][$mealId] ?? 0) + 1;
                
                // Hozzávalók összegzése
                if ($order->menuItem->optionBMeal && $order->menuItem->optionBMeal->ingredients) {
                    $this->sumIngredients($ingredientRequirements, $order->menuItem->optionBMeal, 1);
                }
            }
        }
        
        return $this->success([
            'date' => $date,
            'total_orders' => $orders->count(),
            'meal_counts' => $mealCounts,
            'ingredient_requirements' => array_values($ingredientRequirements),
            'summary_by_option' => [
                'A' => $orders->where('selectedOption', 'A')->count(),
                'B' => $orders->where('selectedOption', 'B')->count(),
                'soup' => $orders->where('selectedOption', 'soup')->count(),
                'other' => $orders->where('selectedOption', 'other')->count()
            ]
        ]);
    }
    
    /**
     * Havi előkészületi lista
     */
    public function getMonthlyPreparation($year, $month)
    {
        $user = Auth::user();
        if (!in_array($user->userType, ['Konyha', 'Admin'])) {
            return $this->unauthorized();
        }
        
        $startDate = "{$year}-{$month}-01";
        $endDate = date('Y-m-t', strtotime($startDate));
        
        // Összes rendelés az adott hónapban
        $orders = Order::whereDate('orderDate', '>=', $startDate)
            ->whereDate('orderDate', '<=', $endDate)
            ->where('orderStatus', 'Rendelve')
            ->with(['menuItem' => function($q) {
                $q->with([
                    'soupMeal.ingredients', 
                    'optionAMeal.ingredients', 
                    'optionBMeal.ingredients'
                ]);
            }])
            ->get();
        
        // 1. Ételenkénti összesítés
        $mealCounts = [
            'soups' => [],
            'optionA' => [],
            'optionB' => []
        ];
        
        // 2. Hozzávalónkénti összesítés
        $ingredientRequirements = [];
        
        foreach ($orders as $order) {
            if (!$order->menuItem) continue;
            
            $menuItem = $order->menuItem;
            
            // LEVES számolása
            if ($menuItem->soup && $menuItem->soupMeal) {
                $soupId = $menuItem->soup;
                $soupName = $menuItem->soupMeal->mealName;
                
                if (!isset($mealCounts['soups'][$soupId])) {
                    $mealCounts['soups'][$soupId] = [
                        'id' => $soupId,
                        'name' => $soupName,
                        'count' => 0
                    ];
                }
                $mealCounts['soups'][$soupId]['count']++;
                
                // Hozzávalók számolása a leveshez
                $this->sumIngredients($ingredientRequirements, $menuItem->soupMeal, 1);
            }
            
            // A OPCIÓ számolása
            if ($order->selectedOption === 'A' && $menuItem->optionA && $menuItem->optionAMeal) {
                $mealId = $menuItem->optionA;
                $mealName = $menuItem->optionAMeal->mealName;
                
                if (!isset($mealCounts['optionA'][$mealId])) {
                    $mealCounts['optionA'][$mealId] = [
                        'id' => $mealId,
                        'name' => $mealName,
                        'count' => 0
                    ];
                }
                $mealCounts['optionA'][$mealId]['count']++;
                
                // Hozzávalók számolása az A opcióhoz
                $this->sumIngredients($ingredientRequirements, $menuItem->optionAMeal, 1);
            }
            
            // B OPCIÓ számolása
            if ($order->selectedOption === 'B' && $menuItem->optionB && $menuItem->optionBMeal) {
                $mealId = $menuItem->optionB;
                $mealName = $menuItem->optionBMeal->mealName;
                
                if (!isset($mealCounts['optionB'][$mealId])) {
                    $mealCounts['optionB'][$mealId] = [
                        'id' => $mealId,
                        'name' => $mealName,
                        'count' => 0
                    ];
                }
                $mealCounts['optionB'][$mealId]['count']++;
                
                // Hozzávalók számolása a B opcióhoz
                $this->sumIngredients($ingredientRequirements, $menuItem->optionBMeal, 1);
            }
        }
        
        // Átalakítás tömbbé a könnyebb használathoz
        $mealCounts['soups'] = array_values($mealCounts['soups']);
        $mealCounts['optionA'] = array_values($mealCounts['optionA']);
        $mealCounts['optionB'] = array_values($mealCounts['optionB']);
        
        // Hozzávalók rendszerezése típus szerint
        $ingredientsByType = [];
        foreach ($ingredientRequirements as $ingredientId => $data) {
            $type = $data['ingredient']->ingredientType ?? 'Egyéb';
            
            if (!isset($ingredientsByType[$type])) {
                $ingredientsByType[$type] = [];
            }
            
            $ingredientsByType[$type][] = $data;
        }
        
        return $this->success([
            'month' => $month,
            'year' => $year,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'total_orders' => $orders->count(),
            'meal_counts' => $mealCounts,
            'ingredient_summary' => [
                'total_unique_ingredients' => count($ingredientRequirements),
                'ingredients_by_type' => $ingredientsByType,
                'all_ingredients' => array_values($ingredientRequirements)
            ],
            'summary_by_option' => [
                'A' => $orders->where('selectedOption', 'A')->count(),
                'B' => $orders->where('selectedOption', 'B')->count(),
                'total' => $orders->count()
            ]
        ]);
    }
    
    /**
     * Hozzávalók összegzése
     */
    private function sumIngredients(&$ingredientRequirements, $meal, $multiplier = 1)
    {
        if (!$meal || !$meal->relationLoaded('ingredients')) {
            return;
        }
        
        foreach ($meal->ingredients as $ingredientRelation) {
            $ingredient = $ingredientRelation->ingredient ?? null;
            
            if (!$ingredient) continue;
            
            $ingredientId = $ingredient->id;
            
            if (!isset($ingredientRequirements[$ingredientId])) {
                $ingredientRequirements[$ingredientId] = [
                    'ingredient' => $ingredient,
                    'total_amount' => 0,
                    'unit' => $ingredientRelation->unit ?? 'db',
                    'meal_count' => 0
                ];
            }
            
            // Mennyiség hozzáadása
            $amount = $ingredientRelation->amount ?? 0;
            $ingredientRequirements[$ingredientId]['total_amount'] += ($amount * $multiplier);
            $ingredientRequirements[$ingredientId]['meal_count'] += $multiplier;
        }
    }
    
    /**
     * Részletes összesítés számítása
     */
    private function getDetailedSummary($orders)
    {
        return [
            'total' => $orders->count(),
            'by_option' => $orders->groupBy('selectedOption')->map->count(),
            'by_user_type' => $orders->groupBy('user.userType')->map->count(),
            'total_revenue' => $orders->sum(function($order) {
                return $order->price->amount ?? 0;
            }),
            'average_order_value' => $orders->count() > 0 ? 
                round($orders->sum(function($order) {
                    return $order->price->amount ?? 0;
                }) / $orders->count(), 2) : 0
        ];
    }
    
    /**
     * Összegzés számítása
     */
    private function getSummary($orders)
    {
        $activeOrders = collect($orders)->where('orderStatus', 'Rendelve');
        
        return [
            'total_orders' => count($orders),
            'active_orders' => $activeOrders->count(),
            'cancelled_orders' => collect($orders)->where('orderStatus', 'Lemondva')->count(),
            'total_revenue' => $activeOrders->sum(function($order) {
                return $order->price->amount ?? 0;
            }),
            'option_distribution' => $activeOrders->groupBy('selectedOption')->map->count()
        ];
    }
    
    /**
     * Export adatok előkészítése
     */
    private function prepareExportData($orders, $menu)
    {
        return $orders->map(function($order) use ($menu) {
            $mainCourse = '';
            if ($order->selectedOption === 'A' && $menu && $menu->optionAMeal) {
                $mainCourse = $menu->optionAMeal->mealName ?? '';
            } elseif ($order->selectedOption === 'B' && $menu && $menu->optionBMeal) {
                $mainCourse = $menu->optionBMeal->mealName ?? '';
            }
            
            return [
                'order_id' => $order->id,
                'date' => $order->orderDate,
                'option' => $order->selectedOption,
                'soup' => $menu ? ($menu->soupMeal->mealName ?? '') : '',
                'main_course' => $mainCourse,
                'price' => $order->price->amount ?? 0,
                'order_time' => $order->created_at
            ];
        });
    }
}