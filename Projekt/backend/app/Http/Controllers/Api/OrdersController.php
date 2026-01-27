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
        $user = Auth::user();
        if (!in_array($user->userType, ['Konyha', 'Admin'])) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
        
        $query = Order::with([
            'user:id,name,student_class,userType',
            'menuItem' => function($q) {
                // **JAVÍTVA**
                $q->with(['soupMeal', 'optionAMeal', 'optionBMeal', 'otherMeal']);
            }
        ]);
        
        // ... (szűrések változatlanok) ...
        
        $orders = $query->orderBy('orderDate', 'desc')
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
        
        $orders = Order::whereDate('orderDate', $today)
            ->where('orderStatus', 'Rendelve')
            ->with(['user:id,name,student_class'])
            ->get();
            
        // **JAVÍTVA: day, és soupMeal stb.**
        $menu = MenuItem::whereDate('day', $today)
            ->with(['soupMeal', 'optionAMeal', 'optionBMeal', 'otherMeal'])
            ->first();
            
        $summary = $this->getDetailedSummary($orders);
        
        return response()->json([
            'success' => true,
            'data' => [
                'date' => $today,
                'menu' => $menu,
                'orders' => $orders,
                'summary' => $summary,
                'total_orders' => $orders->count(),
                'by_option' => $orders->groupBy('selectedOption')->map->count(),
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
        
        $orders = Order::whereDate('orderDate', $date)
            ->where('orderStatus', 'Rendelve')
            ->with(['user:id,name,student_class,userType'])
            ->get();
            
        // **JAVÍTVA**
        $menu = MenuItem::whereDate('day', $date)
            ->with(['soupMeal', 'optionAMeal', 'optionBMeal', 'otherMeal'])
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
     * Előkészületi lista generálása
     */
    public function getPreparationList($date)
    {
        $user = Auth::user();
        if (!in_array($user->userType, ['Konyha', 'Admin'])) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
        
        $orders = Order::whereDate('orderDate', $date)
            ->where('orderStatus', 'Rendelve')
            ->with(['menuItem' => function($q) {
                // **JAVÍTVA**
                $q->with(['soupMeal.ingredients', 'optionAMeal.ingredients', 'optionBMeal.ingredients']);
            }])
            ->get();
            
            
        // Ételek számlálása
        $mealCounts = [];
        $ingredientRequirements = [];
        
        foreach ($orders as $order) {
            // Leves
            if ($order->menuItem->soup) {
                $soupId = $order->menuItem->soup; // JAVÍTVA: soup, nem soup_id
                $mealCounts['soup'][$soupId] = ($mealCounts['soup'][$soupId] ?? 0) + 1;
                
                // Összetevők összegzése - HA VAN ingredient kapcsolat
                // Ez egy másik probléma lehet, ha nincs ingredients reláció
            }
            
            // Főétel
            if ($order->selectedOption === 'A' && $order->menuItem->optionA) { // JAVÍTVA: selectedOption
                $mealId = $order->menuItem->optionA; // JAVÍTVA: optionA, nem option_a_id
                $mealCounts['optionA'][$mealId] = ($mealCounts['optionA'][$mealId] ?? 0) + 1;
            }
            
            if ($order->selectedOption === 'B' && $order->menuItem->optionB) { // JAVÍTVA: selectedOption
                $mealId = $order->menuItem->optionB; // JAVÍTVA: optionB, nem option_b_id
                $mealCounts['optionB'][$mealId] = ($mealCounts['optionB'][$mealId] ?? 0) + 1;
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
                    'A' => $orders->where('selectedOption', 'A')->count(), // JAVÍTVA: selectedOption
                    'B' => $orders->where('selectedOption', 'B')->count(), // JAVÍTVA: selectedOption
                    'soup' => $orders->where('selectedOption', 'soup')->count(), // JAVÍTVA: selectedOption
                    'other' => $orders->where('selectedOption', 'other')->count() // JAVÍTVA: selectedOption
                ]
            ]
        ]);
    }
    
    /**
     * Részletes összesítés számítása
     */
    private function getDetailedSummary($orders)
    {
        return [
            'total' => $orders->count(),
            'by_option' => $orders->groupBy('selectedOption')->map->count(),
            'by_user_type' => $orders->groupBy('user.userType')->map->count(), // JAVÍTVA: userType
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
            'active_orders' => $orders->where('orderStatus', 'Rendelve')->count(), // JAVÍTVA: orderStatus
            'cancelled_orders' => $orders->where('orderStatus', 'Lemondva')->count(), // JAVÍTVA: orderStatus
            'total_revenue' => $orders->where('orderStatus', 'Rendelve')->sum('price'), // JAVÍTVA: orderStatus
            'option_distribution' => $orders->where('orderStatus', 'Rendelve') // JAVÍTVA: orderStatus
                ->groupBy('selectedOption') // JAVÍTVA: selectedOption
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
                'date' => $order->orderDate, // JAVÍTVA: orderDate, nem order_date
                'option' => $order->selectedOption, // JAVÍTVA: selectedOption, nem selected_option
                'soup' => $menu->soup->mealName ?? '',
                'main_course' => $order->selectedOption === 'A'  // JAVÍTVA: selectedOption
                    ? ($menu->optionA->mealName ?? '')
                    : ($menu->optionB->mealName ?? ''),
                'price' => $order->price,
                'order_time' => $order->created_at
            ];
        });
    }
}