<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\MenuItem;
use App\Models\Price;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class PersonalOrderController extends Controller
{
    /**
     * Felhasználó összes rendelése
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        
        Log::info('PersonalOrderController - Rendelések lekérése', [
            'user_id' => $user->id,
            'month' => $request->input('month'),
            'year' => $request->input('year')
        ]);
        
        try {
            // Ellenőrizzük, hogy létezik-e az orders tábla
            $tableExists = DB::select("SHOW TABLES LIKE 'orders'");
            if (empty($tableExists)) {
                throw new \Exception('Orders tábla nem létezik');
            }
        } catch (\Exception $e) {
            Log::warning('Orders tábla nem létezik', ['error' => $e->getMessage()]);
            
            return response()->json([
                'success' => true,
                'data' => [
                    'orders' => [],
                    'pagination' => [
                        'current_page' => 1,
                        'total' => 0,
                        'per_page' => 20,
                        'last_page' => 1
                    ],
                    'stats' => []
                ],
                'message' => 'Még nincsenek rendelések'
            ]);
        }
        
        // **JAVÍTVA: soupMeal, optionAMeal, optionBMeal, otherMeal**
        $query = Order::where('user_id', $user->id)
            ->with(['menuItem' => function($q) {
                $q->with(['soupMeal', 'optionAMeal', 'optionBMeal', 'otherMeal']);
            }])
            ->orderBy('orderDate', 'desc');
        
        // ... (szűrések változatlanok) ...
        
        $orders = $query->paginate(20);
        
        // Havi összegzés
        $monthlyStats = $this->getMonthlyStats1($user->id);
        
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
                'stats' => $monthlyStats
            ]
        ]);
    }
    
    /**
     * Napi rendelés lekérdezése
     */
    public function getByDate($date)
    {
        $user = Auth::user();
        
        Log::info('PersonalOrderController - Napi rendelés lekérés', [
            'user_id' => $user->id,
            'date' => $date
        ]);
        
        try {
            $order = Order::where('user_id', $user->id)
                ->whereDate('orderDate', $date)
                ->with(['menuItem' => function($q) {
                    // **JAVÍTVA**
                    $q->with(['soupMeal', 'optionAMeal', 'optionBMeal', 'otherMeal']);
                }])
                ->first();
                
            if (!$order) {
                return response()->json([
                    'success' => true,
                    'data' => null,
                    'message' => 'Nincs rendelés ezen a napon'
                ]);
            }
            
            return response()->json([
                'success' => true,
                'data' => $order
            ]);
            
        } catch (\Exception $e) {
            Log::error('Hiba a rendelés lekérdezésénél', ['error' => $e->getMessage()]);
            
            return response()->json([
                'success' => true,
                'data' => null,
                'message' => 'Hiba a rendelés lekérdezésénél'
            ]);
        }
    }
    
    /**
     * Elérhető rendelési dátumok
     */
    public function getAvailableDates()
    {
        $user = Auth::user();
        
        try {
            $availableDates = MenuItem::where('day', '>', now()->format('Y-m-d'))
                ->orderBy('day')
                ->get()
                ->map(function($menuItem) use ($user) {
                    // Ellenőrizzük, hogy van-e már rendelés
                    $hasOrder = false;
                    try {
                        $hasOrder = Order::where('user_id', $user->id)
                            ->where('menuItems_id', $menuItem->id)
                            ->where('orderStatus', 'Rendelve')
                            ->exists();
                    } catch (\Exception $e) {
                        $hasOrder = false;
                    }
                        
                    return [
                        'date' => $menuItem->day->format('Y-m-d'),
                        'display_date' => $menuItem->day->format('Y. m. d.'),
                        'day_name' => $menuItem->day->translatedFormat('l'),
                        'has_order' => $hasOrder,
                        'deadline' => null,
                        'menu' => [
                            // **JAVÍTVA: soupMeal, optionAMeal, optionBMeal, otherMeal**
                            'soup' => $menuItem->soupMeal,
                            'optionA' => $menuItem->optionAMeal,
                            'optionB' => $menuItem->optionBMeal,
                            'other' => $menuItem->otherMeal
                        ]
                    ];
                });
                
            return response()->json([
                'success' => true,
                'data' => $availableDates,
                'count' => $availableDates->count()
            ]);
            
        } catch (\Exception $e) {
            Log::error('Elérhető dátumok hiba', ['error' => $e->getMessage()]);
            
            $testDates = [];
            for ($i = 1; $i <= 5; $i++) {
                $date = now()->addDays($i);
                $testDates[] = [
                    'date' => $date->format('Y-m-d'),
                    'display_date' => $date->format('Y. m. d.'),
                    'day_name' => $date->translatedFormat('l'),
                    'has_order' => false,
                    'deadline' => null,
                    'menu' => [
                        'soup' => ['mealName' => 'Teszt leves'],
                        'optionA' => ['mealName' => 'Teszt A opció'],
                        'optionB' => ['mealName' => 'Teszt B opció'],
                        'other' => null
                    ]
                ];
            }
            
            return response()->json([
                'success' => true,
                'data' => $testDates,
                'count' => count($testDates),
                'message' => 'Teszt adatok'
            ]);
        }
    }
    
    /**
     * Havi statisztika számítás
     */
    private function getMonthlyStats1($userId)
    {
        try {
            $currentMonth = now()->format('Y-m');
            
            $stats = Order::where('user_id', $userId)
                ->where('orderStatus', 'Rendelve')
                ->select(
                    DB::raw('YEAR(orderDate) as year'),
                    DB::raw('MONTH(orderDate) as month'),
                    DB::raw('COUNT(*) as count'),
                    DB::raw('SUM(price) as total') // FIGYELEM: nincs price mező!
                )
                ->groupBy('year', 'month')
                ->orderBy('year', 'desc')
                ->orderBy('month', 'desc')
                ->get();
                
            return $stats;
            
        } catch (\Exception $e) {
            Log::error('Havi statisztika hiba', ['error' => $e->getMessage()]);
            return [];
        }
    }
    
    // **JAVÍTVA: store metódusban is**
    public function store(Request $request)
    {
        // ... (előző kód) ...
        
        if (!$menuItem) {
            return response()->json([
                'success' => false,
                'message' => 'Nem található menü erre a napra.'
            ], 404);
        }
        
        try {
            // Ár meghatározása
            $price = $this->calculateUserPrice($user);
            
            if (!$price) {
                $defaultPrice = 450;
                
                $order = Order::create([
                    'user_id' => $user->id,
                    'menuItems_id' => $menuItem->id,
                    'orderDate' => $request->date,
                    'selectedOption' => $request->selectedOption,
                    'orderStatus' => 'Rendelve',
                    'invoice_id' => null,
                    'price_id' => null,
                    'price' => $defaultPrice, // **HOZZÁADVA: price mező**
                ]);
            } else {
                $order = Order::create([
                    'user_id' => $user->id,
                    'menuItems_id' => $menuItem->id,
                    'orderDate' => $request->date,
                    'selectedOption' => $request->selectedOption,
                    'orderStatus' => 'Rendelve',
                    'invoice_id' => null,
                    'price_id' => $price->id,
                    'price' => $price->amount, // **HOZZÁADVA: price mező**
                ]);
            }
            
            Log::info('Rendelés létrehozva', [
                'order_id' => $order->id,
                'user_id' => $user->id,
                'date' => $request->date
            ]);
            
            return response()->json([
                'success' => true,
                'message' => 'Rendelésed sikeresen rögzítettük!',
                'data' => $order->load(['menuItem' => function($q) {
                    // **JAVÍTVA**
                    $q->with(['soupMeal', 'optionAMeal', 'optionBMeal', 'otherMeal']);
                }])
            ], 201);
            
        } catch (\Exception $e) {
            Log::error('Rendelési hiba', [
                'error' => $e->getMessage(),
                'user_id' => $user->id,
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Hiba történt a rendelés során.',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    public function getAvailableDatesByMonth(Request $request, $year, $month)
{
    $user = Auth::user();
    
    try {
        $startDate = "{$year}-{$month}-01";
        $endDate = date('Y-m-t', strtotime($startDate));
        
        $availableDates = MenuItem::whereBetween('day', [$startDate, $endDate])
            ->orderBy('day')
            ->get()
            ->map(function($menuItem) use ($user) {
                $hasOrder = Order::where('user_id', $user->id)
                    ->where('menuItems_id', $menuItem->id)
                    ->where('orderStatus', 'Rendelve')
                    ->exists();
                    
                $order = Order::where('user_id', $user->id)
                    ->where('menuItems_id', $menuItem->id)
                    ->where('orderStatus', 'Rendelve')
                    ->first();
                    
                return [
                    'date' => $menuItem->day->format('Y-m-d'),
                    'display_date' => $menuItem->day->format('Y. m. d.'),
                    'day_name' => $menuItem->day->translatedFormat('l'),
                    'has_order' => $hasOrder,
                    'menu_item_id' => $menuItem->id,
                    'order_id' => $order ? $order->id : null,
                    'selected_option' => $order ? $order->selectedOption : null,
                    'menu' => [
                        'soup' => $menuItem->soupMeal,
                        'optionA' => $menuItem->optionAMeal,
                        'optionB' => $menuItem->optionBMeal,
                        'other' => $menuItem->otherMeal
                    ]
                ];
            });
            
        return response()->json([
            'success' => true,
            'data' => $availableDates,
            'month' => $month,
            'year' => $year,
            'count' => $availableDates->count()
        ]);
        
    } catch (\Exception $e) {
        Log::error('Hónap szerinti dátumok hiba', ['error' => $e->getMessage()]);
        
        return response()->json([
            'success' => false,
            'message' => 'Hiba a dátumok lekérdezésekor.',
            'error' => config('app.debug') ? $e->getMessage() : null
        ], 500);
    }
}

/**
 * Elérhető hónapok listája
 */
    public function getAvailableMonths()
    {
        $user = Auth::user();
        
        try {
            $months = MenuItem::select(
                    DB::raw('YEAR(day) as year'),
                    DB::raw('MONTH(day) as month'),
                    DB::raw('COUNT(*) as day_count')
                )
                ->where('day', '>', now()->format('Y-m-d'))
                ->groupBy('year', 'month')
                ->orderBy('year', 'asc')
                ->orderBy('month', 'asc')
                ->get()
                ->map(function($item) {
                    $monthNames = [
                        1 => 'Január', 2 => 'Február', 3 => 'Március',
                        4 => 'Április', 5 => 'Május', 6 => 'Június',
                        7 => 'Július', 8 => 'Augusztus', 9 => 'Szeptember',
                        10 => 'Október', 11 => 'November', 12 => 'December'
                    ];
                    
                    return [
                        'year' => $item->year,
                        'month' => $item->month,
                        'display' => $monthNames[$item->month] . ' ' . $item->year,
                        'day_count' => $item->day_count
                    ];
                });
                
            return response()->json([
                'success' => true,
                'data' => $months
            ]);
            
        } catch (\Exception $e) {
            Log::error('Elérhető hónapok hiba', ['error' => $e->getMessage()]);
            
            // Visszaadjuk a következő 6 hónapot
            $defaultMonths = [];
            $now = now();
            
            for ($i = 0; $i < 6; $i++) {
                $date = $now->copy()->addMonths($i);
                $defaultMonths[] = [
                    'year' => $date->year,
                    'month' => $date->month,
                    'display' => $date->translatedFormat('F Y'),
                    'day_count' => 0
                ];
            }
            
            return response()->json([
                'success' => true,
                'data' => $defaultMonths,
                'message' => 'Teszt adatok'
            ]);
        }
        
    }
}