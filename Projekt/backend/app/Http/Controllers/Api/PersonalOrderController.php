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
        
        $query = Order::where('user_id', $user->id)
            ->with([
                        'menuItem' => function($q) {
                            $q->with(['soupMeal', 'optionAMeal', 'optionBMeal', 'otherMeal']);
                        },
                        'price'
                    ])
                    ->orderBy('orderDate', 'desc');
        
        
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
            Log::info('getAvailableDates - kezdés', ['user_id' => $user->id]);
            
            // Először ellenőrizzük a MenuItem-eket
            $menuItemsCount = MenuItem::count();
            Log::info('MenuItem-ek száma', ['count' => $menuItemsCount]);
            
            $availableDates = MenuItem::where('day', '>', now()->format('Y-m-d'))
                ->with(['soupMeal', 'optionAMeal', 'optionBMeal', 'otherMeal'])
                ->orderBy('day')
                ->get()
                ->map(function($menuItem) use ($user) {
                    Log::debug('MenuItem feldolgozása', [
                        'id' => $menuItem->id,
                        'day' => $menuItem->day,
                        'soup_id' => $menuItem->soup,
                        'optionA_id' => $menuItem->optionA,
                        'optionB_id' => $menuItem->optionB,
                        'has_soupMeal' => $menuItem->relationLoaded('soupMeal'),
                        'soup_meal' => $menuItem->soupMeal ? $menuItem->soupMeal->mealName : null
                    ]);
                    
                    // Ellenőrizzük, hogy van-e már rendelés
                    $hasOrder = Order::where('user_id', $user->id)
                        ->where('menuItems_id', $menuItem->id)
                        ->where('orderStatus', 'Rendelve')
                        ->exists();
                    
                    $order = null;
                    if ($hasOrder) {
                        $order = Order::where('user_id', $user->id)
                            ->where('menuItems_id', $menuItem->id)
                            ->where('orderStatus', 'Rendelve')
                            ->first();
                    }
                        
                    return [
                        'date' => $menuItem->day->format('Y-m-d'),
                        'display_date' => $menuItem->day->format('Y. m. d.'),
                        'day_name' => $menuItem->day->translatedFormat('l'),
                        'has_order' => $hasOrder,
                        'menu_item_id' => $menuItem->id,
                        'order_id' => $order ? $order->id : null,
                        'selected_option' => $order ? $order->selectedOption : null,
                        'menu' => $menuItem->getMenuData(),
                        'can_order' => $this->canOrderForDate($menuItem->day),
                        'order_deadline' => $this->getOrderDeadline($menuItem->day)
                    ];
                });
                
            Log::info('getAvailableDates - sikeres', ['count' => $availableDates->count()]);
            
            return response()->json([
                'success' => true,
                'data' => $availableDates,
                'count' => $availableDates->count(),
                'debug' => [
                    'menu_items_count' => $menuItemsCount,
                    'user_id' => $user->id
                ]
            ]);
            
        } catch (\Exception $e) {
            Log::error('Elérhető dátumok hiba', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Hiba az elérhető dátumok lekérdezésekor.',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }
    private function canOrderForDate($date)
    {
        $orderDate = \Carbon\Carbon::parse($date);
        $deadline = $orderDate->copy()->subDay()->setTime(10, 0, 0); // előző nap 10:00-ig
        return now() <= $deadline;
    }

    private function getOrderDeadline($date)
    {
        $orderDate = \Carbon\Carbon::parse($date);
        return $orderDate->copy()->subDay()->setTime(10, 0, 0)->format('Y-m-d H:i:s');
    }

    
    /**
     * Havi statisztika számítás
     */
    // PersonalOrderController.php-be
        private function getMonthlyStats1($userId)
        {
            try {
                $currentMonth = now()->format('Y-m');
                
                $stats = Order::where('user_id', $userId)
                    ->where('orderStatus', 'Rendelve')
                    ->with('price') // Betöltjük az árat
                    ->select(
                        DB::raw('YEAR(orderDate) as year'),
                        DB::raw('MONTH(orderDate) as month'),
                        DB::raw('COUNT(*) as count'),
                        DB::raw('SUM(
                            CASE 
                                WHEN prices.id IS NOT NULL THEN prices.amount 
                                ELSE 0 
                            END
                        ) as total')
                    )
                    ->leftJoin('prices', 'orders.price_id', '=', 'prices.id')
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
    

    public function store(Request $request)
    {
        $user = Auth::user();
        
        $validator = Validator::make($request->all(), [
            'date' => 'required|date',
            'menuitems_id' => 'required|exists:menuItems,id',
            'selectedOption' => 'required|in:A,B,soup,other'
        ]);
        
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Érvénytelen adatok',
                'errors' => $validator->errors()
            ], 422);
        }
        
        $menuItem = MenuItem::find($request->menuitems_id);
        
        if (!$menuItem) {
            return response()->json([
                'success' => false,
                'message' => 'Nem található menü erre a napra.'
            ], 404);
        }
        
        // Ellenőrizzük, hogy van-e már rendelés erre a napra
        $existingOrder = Order::where('user_id', $user->id)
            ->where('menuItems_id', $request->menuitems_id)
            ->where('orderStatus', 'Rendelve')
            ->first();
            
        if ($existingOrder) {
            return response()->json([
                'success' => false,
                'message' => 'Már van aktív rendelése erre a napra.',
                'data' => $existingOrder
            ], 409);
        }
        
        // Ellenőrizzük a rendelési határidőt
        if (!$this->canOrderForDate($menuItem->day)) {
            return response()->json([
                'success' => false,
                'message' => 'A rendelési határidő lejárt erre a napra.',
                'deadline' => $this->getOrderDeadline($menuItem->day)
            ], 400);
        }
        
        try {
            // Ár meghatározása - userType és hasDiscount alapján
            $price = $this->calculateUserPrice($user);
            
            // Ha nincs ár, hozzunk létre egy alapértelmezettet
            if (!$price) {
                $priceCategory = $user->hasDiscount ? 'Kedvezményes' : 'Normál';
                
                // Alapértelmezett árak
                $defaultAmounts = [
                    'Tanuló' => ['Normál' => 450, 'Kedvezményes' => 300],
                    'Tanár' => ['Normál' => 650, 'Kedvezményes' => 400],
                    'Dolgozó' => ['Normál' => 600, 'Kedvezményes' => 400],
                    'Külsős' => ['Normál' => 750, 'Kedvezményes' => 500],
                ];
                
                $amount = $defaultAmounts[$user->userType][$priceCategory] ?? 450;
                
                $price = Price::create([
                    'userType' => $user->userType,
                    'priceCategory' => $priceCategory,
                    'amount' => $amount,
                    'validFrom' => now()->toDateString(),
                    'validTo' => null
                ]);
                
                Log::warning('Alapértelmezett ár létrehozva', [
                    'user_id' => $user->id,
                    'price_id' => $price->id,
                    'amount' => $amount
                ]);
            }
            
            $order = Order::create([
                'user_id' => $user->id,
                'menuItems_id' => $menuItem->id,
                'orderDate' => $request->date,
                'selectedOption' => $request->selectedOption,
                'orderStatus' => 'Rendelve',
                'invoice_id' => null,
                'price_id' => $price->id,
                'created_at' => now(),
                'updated_at' => now()
            ]);
            
            Log::info('Rendelés létrehozva', [
                'order_id' => $order->id,
                'user_id' => $user->id,
                'user_type' => $user->userType,
                'has_discount' => $user->hasDiscount,
                'date' => $request->date,
                'menu_item_id' => $menuItem->id,
                'price_id' => $order->price_id,
                'amount' => $price->amount,
                'price_category' => $price->priceCategory
            ]);
            
            return response()->json([
                'success' => true,
                'message' => 'Rendelésed sikeresen rögzítettük!',
                'data' => $order->load(['menuItem' => function($q) {
                    $q->with(['soupMeal', 'optionAMeal', 'optionBMeal', 'otherMeal']);
                }, 'price'])
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

/**
 * Felhasználó árának kiszámítása
 */
/**
 * Felhasználó árának kiszámítása
 */
    private function calculateUserPrice($user)
    {
        try {
            // Meghatározzuk a kategóriát a hasDiscount alapján
            $priceCategory = $user->hasDiscount ? 'Kedvezményes' : 'Normál';
            
            Log::debug('Árkalkuláció', [
                'user_id' => $user->id,
                'userType' => $user->userType,
                'hasDiscount' => $user->hasDiscount,
                'priceCategory' => $priceCategory
            ]);
            
            // Keresés a prices táblában
            $price = Price::where('userType', $user->userType)
                ->where('priceCategory', $priceCategory)
                ->where('validFrom', '<=', now()->toDateString())
                ->where(function($query) {
                    $query->where('validTo', '>=', now()->toDateString())
                        ->orWhereNull('validTo');
                })
                ->orderBy('validFrom', 'desc') // Legújabb ár
                ->first();
                
            if (!$price) {
                Log::warning('Nem található ár a felhasználóhoz', [
                    'userType' => $user->userType,
                    'priceCategory' => $priceCategory,
                    'user_id' => $user->id
                ]);
                
                // Visszaesés: próbáljunk csak userType alapján
                $price = Price::where('userType', $user->userType)
                    ->orderBy('validFrom', 'desc')
                    ->first();
            }
                
            return $price;
        } catch (\Exception $e) {
            Log::error('Árképzési hiba', [
                'error' => $e->getMessage(),
                'user_id' => $user->id,
                'trace' => $e->getTraceAsString()
            ]);
            return null;
        }
    }



    private function canCancelOrder($order)
    {
        try {
            $orderDate = \Carbon\Carbon::parse($order->orderDate);
            $deadline = $orderDate->copy()->setTime(8, 0, 0); // aznap 8:00-ig
            return now() <= $deadline;
        } catch (\Exception $e) {
            Log::error('Lemondhatóság ellenőrzése hiba', [
                'error' => $e->getMessage(),
                'order_id' => $order->id
            ]);
            return false;
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
    // Adj hozzá egy új metódust a törölt rendelések újrarendeléséhez
public function reorder(Request $request, $orderId)
{
    $user = Auth::user();
    
    $validator = Validator::make($request->all(), [
        'selectedOption' => 'required|in:A,B,soup,other'
    ]);
    
    if ($validator->fails()) {
        return response()->json([
            'success' => false,
            'message' => 'Érvénytelen adatok',
            'errors' => $validator->errors()
        ], 422);
    }
    
    try {
        // Megkeressük a régi rendelést
        $oldOrder = Order::where('id', $orderId)
            ->where('user_id', $user->id)
            ->first();
            
        if (!$oldOrder) {
            return response()->json([
                'success' => false,
                'message' => 'A rendelés nem található.'
            ], 404);
        }
        
        // Ellenőrizzük a rendelési határidőt
        $menuItem = MenuItem::find($oldOrder->menuItems_id);
        if (!$this->canOrderForDate($menuItem->day)) {
            return response()->json([
                'success' => false,
                'message' => 'A rendelési határidő lejárt erre a napra.',
                'deadline' => $this->getOrderDeadline($menuItem->day)
            ], 400);
        }
        
        // Ellenőrizzük, hogy van-e már aktív rendelés
        $existingOrder = Order::where('user_id', $user->id)
            ->where('menuItems_id', $oldOrder->menuItems_id)
            ->where('orderStatus', 'Rendelve')
            ->first();
            
        if ($existingOrder) {
            return response()->json([
                'success' => false,
                'message' => 'Már van aktív rendelése erre a napra.',
                'data' => $existingOrder
            ], 409);
        }
        
        // Új rendelés létrehozása
        $price = $this->calculateUserPrice($user);
        
        if (!$price) {
            $priceCategory = $user->hasDiscount ? 'Kedvezményes' : 'Normál';
            $amount = 450; // Alapértelmezett ár
            
            $price = Price::create([
                'userType' => $user->userType,
                'priceCategory' => $priceCategory,
                'amount' => $amount,
                'validFrom' => now()->toDateString(),
                'validTo' => null
            ]);
        }
        
        $order = Order::create([
            'user_id' => $user->id,
            'menuItems_id' => $oldOrder->menuItems_id,
            'orderDate' => $oldOrder->orderDate,
            'selectedOption' => $request->selectedOption,
            'orderStatus' => 'Rendelve',
            'invoice_id' => null,
            'price_id' => $price->id,
            'created_at' => now(),
            'updated_at' => now()
        ]);
        
        Log::info('Újrarendelés létrehozva', [
            'old_order_id' => $oldOrder->id,
            'new_order_id' => $order->id,
            'user_id' => $user->id
        ]);
        
        return response()->json([
            'success' => true,
            'message' => 'Rendelésed sikeresen újrarögzítettük!',
            'data' => $order->load(['menuItem', 'price'])
        ], 201);
        
    } catch (\Exception $e) {
        Log::error('Újrarendelési hiba', [
            'error' => $e->getMessage(),
            'user_id' => $user->id,
            'order_id' => $orderId
        ]);
        
        return response()->json([
            'success' => false,
            'message' => 'Hiba történt az újrarendelés során.',
            'error' => config('app.debug') ? $e->getMessage() : null
        ], 500);
    }
}


}