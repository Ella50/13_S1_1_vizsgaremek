<?php

namespace App\Http\Controllers\Api;

use App\Models\Order;
use App\Models\MenuItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class OrdersController extends Controller
{
    /**
     * Összes rendelés listázása (konyha/admin)
     */
    public function index(Request $request)
    {
        // Jogosultság ellenőrzése
        $user = Auth::user();
        if (!in_array($user->userType, ['Konyha', 'Admin'])) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
        
        $query = Order::with([
            'user:id,name,student_class,user_type',
            'menuItem' => function($q) {
                $q->with(['soup', 'optionA', 'optionB', 'other']);
            }
        ]);
        
        // Dátum szűrés
        if ($request->has('date')) {
            $query->whereDate('order_date', $request->date);
        }
        
        // Hónap szűrés
        if ($request->has('month')) {
            $query->whereMonth('order_date', $request->month);
        }
        
        // Év szűrés
        if ($request->has('year')) {
            $query->whereYear('order_date', $request->year);
        }
        
        // Státusz szűrés
        if ($request->has('status')) {
            $query->where('status', $request->status);
        } else {
            $query->where('status', 'ordered'); // alapértelmezett: aktív rendelések
        }
        
        // Opció szerinti szűrés
        if ($request->has('option')) {
            $query->where('selected_option', $request->option);
        }
        
        // Osztály szerinti szűrés
        if ($request->has('class')) {
            $query->whereHas('user', function($q) use ($request) {
                $q->where('student_class', $request->class);
            });
        }
        
        $orders = $query->orderBy('order_date', 'desc')
            ->orderBy('created_at', 'desc')
            ->paginate(50);
            
        // Összegző adatok
        $summary = $this->getSummary($query->get());
        
        return response()->json([
            'success' => true,
            'data' => [
                'orders' => $orders->items(),
                'pagination' => [
                    'current_page' => $orders->currentPage(),
                    'total' => $orders->total(),
                    'per_page' => $orders->perPage(),
                    'last_page' => $orders->lastPage()
                ],
                'summary' => $summary,
                'filters' => $request->all()
            ]
        ]);
    }
    
    /**
     * Mai rendelések összesítése
     */
    public function getTodaySummary()
    {
        $user = Auth::user();
        if (!in_array($user->userType, ['Konyha', 'Admin'])) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
        
        $today = now()->format('Y-m-d');
        
        $orders = Order::whereDate('order_date', $today)
            ->where('status', 'ordered')
            ->with(['user:id,name,student_class'])
            ->get();
            
        // Menü adatok
        $menu = MenuItem::whereDate('menu_date', $today)
            ->with(['soup', 'optionA', 'optionB'])
            ->first();
            
        // Részletes összesítés
        $summary = $this->getDetailedSummary($orders);
        
        return response()->json([
            'success' => true,
            'data' => [
                'date' => $today,
                'menu' => $menu,
                'orders' => $orders,
                'summary' => $summary,
                'total_orders' => $orders->count(),
                'by_option' => $orders->groupBy('selected_option')->map->count(),
                'by_class' => $orders->groupBy('user.student_class')->map->count()
            ]
        ]);
    }
    
    /**
     * Dátum szerinti összesítés
     */
    public function getByDate($date)
    {
        $user = Auth::user();
        if (!in_array($user->userType, ['Konyha', 'Admin'])) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
        
        $orders = Order::whereDate('order_date', $date)
            ->where('status', 'ordered')
            ->with(['user:id,name,student_class,user_type'])
            ->get();
            
        $menu = MenuItem::whereDate('menu_date', $date)
            ->with(['soup', 'optionA', 'optionB', 'other'])
            ->first();
            
        $summary = $this->getDetailedSummary($orders);
        
        return response()->json([
            'success' => true,
            'data' => [
                'date' => $date,
                'menu' => $menu,
                'orders' => $orders,
                'summary' => $summary,
                'total_count' => $orders->count(),
                'export_data' => $this->prepareExportData($orders, $menu)
            ]
        ]);
    }
    
    /**
     * Heti összesítés
     */
    public function getWeeklySummary(Request $request)
    {
        $user = Auth::user();
        if (!in_array($user->userType, ['Konyha', 'Admin'])) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
        
        $startDate = $request->input('start_date', now()->startOfWeek()->format('Y-m-d'));
        $endDate = $request->input('end_date', now()->endOfWeek()->format('Y-m-d'));
        
        $orders = Order::whereBetween('order_date', [$startDate, $endDate])
            ->where('status', 'ordered')
            ->with(['user:id,name,student_class'])
            ->get();
            
        // Napok szerinti csoportosítás
        $dailySummary = [];
        $currentDate = $startDate;
        
        while ($currentDate <= $endDate) {
            $dayOrders = $orders->where('order_date', $currentDate);
            
            $dailySummary[$currentDate] = [
                'date' => $currentDate,
                'total' => $dayOrders->count(),
                'options' => $dayOrders->groupBy('selected_option')->map->count(),
                'classes' => $dayOrders->groupBy('user.student_class')->map->count()
            ];
            
            $currentDate = date('Y-m-d', strtotime($currentDate . ' +1 day'));
        }
        
        // Összesített adatok
        $totalSummary = [
            'total_orders' => $orders->count(),
            'total_revenue' => $orders->sum('price'),
            'by_option' => $orders->groupBy('selected_option')->map->count(),
            'by_class' => $orders->groupBy('user.student_class')->map->count(),
            'by_user_type' => $orders->groupBy('user.user_type')->map->count()
        ];
        
        return response()->json([
            'success' => true,
            'data' => [
                'period' => ['start' => $startDate, 'end' => $endDate],
                'daily_summary' => array_values($dailySummary),
                'total_summary' => $totalSummary,
                'top_classes' => $orders->groupBy('user.student_class')
                    ->map->count()
                    ->sortDesc()
                    ->take(5)
            ]
        ]);
    }
    
    /**
     * Előkészületi lista generálása
     */
    public function getPreparationList($date)
    {
        $user = Auth::user();
        if (!in_array($user->userType, ['Konyha', 'Admin'])) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
        
        $orders = Order::whereDate('order_date', $date)
            ->where('status', 'ordered')
            ->with(['menuItem' => function($q) {
                $q->with(['soup.ingredients', 'optionA.ingredients', 'optionB.ingredients']);
            }])
            ->get();
            
        // Ételek számlálása
        $mealCounts = [];
        $ingredientRequirements = [];
        
        foreach ($orders as $order) {
            // Leves
            if ($order->menuItem->soup) {
                $soupId = $order->menuItem->soup_id;
                $mealCounts['soup'][$soupId] = ($mealCounts['soup'][$soupId] ?? 0) + 1;
                
                // Összetevők összegzése
                foreach ($order->menuItem->soup->ingredients as $ingredient) {
                    $ingredientId = $ingredient->id;
                    $amount = $ingredient->pivot->amount;
                    
                    if (!isset($ingredientRequirements[$ingredientId])) {
                        $ingredientRequirements[$ingredientId] = [
                            'ingredient' => $ingredient,
                            'total_amount' => 0,
                            'unit' => $ingredient->pivot->unit
                        ];
                    }
                    
                    $ingredientRequirements[$ingredientId]['total_amount'] += $amount;
                }
            }
            
            // Főétel
            if ($order->selected_option === 'A' && $order->menuItem->optionA) {
                $mealId = $order->menuItem->option_a_id;
                $mealCounts['option_a'][$mealId] = ($mealCounts['option_a'][$mealId] ?? 0) + 1;
                
                foreach ($order->menuItem->optionA->ingredients as $ingredient) {
                    $ingredientId = $ingredient->id;
                    $amount = $ingredient->pivot->amount;
                    
                    if (!isset($ingredientRequirements[$ingredientId])) {
                        $ingredientRequirements[$ingredientId] = [
                            'ingredient' => $ingredient,
                            'total_amount' => 0,
                            'unit' => $ingredient->pivot->unit
                        ];
                    }
                    
                    $ingredientRequirements[$ingredientId]['total_amount'] += $amount;
                }
            }
            
            if ($order->selected_option === 'B' && $order->menuItem->optionB) {
                $mealId = $order->menuItem->option_b_id;
                $mealCounts['option_b'][$mealId] = ($mealCounts['option_b'][$mealId] ?? 0) + 1;
                
                foreach ($order->menuItem->optionB->ingredients as $ingredient) {
                    $ingredientId = $ingredient->id;
                    $amount = $ingredient->pivot->amount;
                    
                    if (!isset($ingredientRequirements[$ingredientId])) {
                        $ingredientRequirements[$ingredientId] = [
                            'ingredient' => $ingredient,
                            'total_amount' => 0,
                            'unit' => $ingredient->pivot->unit
                        ];
                    }
                    
                    $ingredientRequirements[$ingredientId]['total_amount'] += $amount;
                }
            }
        }
        
        return response()->json([
            'success' => true,
            'data' => [
                'date' => $date,
                'total_orders' => $orders->count(),
                'meal_counts' => $mealCounts,
                'ingredient_requirements' => array_values($ingredientRequirements),
                'summary_by_option' => [
                    'A' => $orders->where('selected_option', 'A')->count(),
                    'B' => $orders->where('selected_option', 'B')->count(),
                    'soup' => $orders->where('selected_option', 'soup')->count(),
                    'other' => $orders->where('selected_option', 'other')->count()
                ]
            ]
        ]);
    }
    
    /**
     * CSV export adatok előkészítése
     */
    public function exportOrders(Request $request)
    {
        $user = Auth::user();
        if (!in_array($user->userType, ['Konyha', 'Admin'])) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
        
        $date = $request->input('date', now()->format('Y-m-d'));
        
        $orders = Order::whereDate('order_date', $date)
            ->where('status', 'ordered')
            ->with(['user', 'menuItem.soup', 'menuItem.optionA', 'menuItem.optionB'])
            ->get()
            ->map(function($order) {
                return [
                    'Név' => $order->user->name,
                    'Osztály' => $order->user->student_class,
                    'Felhasználó típus' => $order->user->user_type,
                    'Dátum' => $order->order_date,
                    'Opció' => $this->getOptionDisplay($order->selected_option),
                    'Leves' => $order->menuItem->soup->mealName ?? '',
                    'A opció' => $order->selected_option === 'A' ? ($order->menuItem->optionA->mealName ?? '') : '',
                    'B opció' => $order->selected_option === 'B' ? ($order->menuItem->optionB->mealName ?? '') : '',
                    'Ár' => $order->price . ' Ft',
                    'Rendelés ideje' => $order->created_at->format('Y-m-d H:i:s'),
                    'Státusz' => $order->status
                ];
            });
            
        return response()->json([
            'success' => true,
            'data' => $orders,
            'filename' => "rendelesek_{$date}.csv",
            'count' => $orders->count()
        ]);
    }
    
    /**
     * Részletes összesítés számítása
     */
    private function getDetailedSummary($orders)
    {
        return [
            'total' => $orders->count(),
            'by_option' => $orders->groupBy('selected_option')->map->count(),
            'by_class' => $orders->groupBy('user.student_class')->map->count(),
            'by_user_type' => $orders->groupBy('user.user_type')->map->count(),
            'total_revenue' => $orders->sum('price'),
            'average_order_value' => $orders->count() > 0 ? round($orders->sum('price') / $orders->count(), 2) : 0
        ];
    }
    
    /**
     * Összegzés számítása
     */
    private function getSummary($orders)
    {
        return [
            'total_orders' => $orders->count(),
            'active_orders' => $orders->where('status', 'ordered')->count(),
            'cancelled_orders' => $orders->where('status', 'cancelled')->count(),
            'total_revenue' => $orders->where('status', 'ordered')->sum('price'),
            'option_distribution' => $orders->where('status', 'ordered')
                ->groupBy('selected_option')
                ->map->count()
        ];
    }
    
    /**
     * Export adatok előkészítése
     */
    private function prepareExportData($orders, $menu)
    {
        return $orders->map(function($order) use ($menu) {
            return [
                'order_id' => $order->id,
                'user_name' => $order->user->name,
                'class' => $order->user->student_class,
                'date' => $order->order_date,
                'option' => $order->selected_option,
                'soup' => $menu->soup->mealName ?? '',
                'main_course' => $order->selected_option === 'A' 
                    ? ($menu->optionA->mealName ?? '')
                    : ($menu->optionB->mealName ?? ''),
                'price' => $order->price,
                'order_time' => $order->created_at
            ];
        });
    }
    
    /**
     * Opció megjelenítési neve
     */
    private function getOptionDisplay($option)
    {
        $display = [
            'A' => 'A opció',
            'B' => 'B opció', 
            'soup' => 'Leves',
            'other' => 'Egyéb'
        ];
        
        return $display[$option] ?? $option;
    }
}