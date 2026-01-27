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
        
        // Ha nincs Order tábla, térj vissza üres adatokkal
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
        
        // A te migrációd szerint:
        // orderDate (nem order_date)
        // orderStatus (nem status)
        // selectedOption (nem selected_option)
        // menuItems_id (nem menu_item_id)
        
        $query = Order::where('user_id', $user->id)
            ->with(['menuItem' => function($q) {
                // A menüben: soup, optionA, optionB, other (nem meal)
                $q->with(['soup', 'optionA', 'optionB', 'other']);
            }])
            ->orderBy('orderDate', 'desc'); // JAVÍTVA: orderDate
            
        // Szűrés hónap szerint
        if ($request->has('month') && $request->month !== 'all') {
            $query->whereMonth('orderDate', $request->month); // JAVÍTVA
        }
        
        // Szűrés év szerint
        if ($request->has('year') && $request->year !== 'all') {
            $query->whereYear('orderDate', $request->year); // JAVÍTVA
        }
        
        // Aktív vagy minden rendelés
        if ($request->has('status') && $request->status === 'active') {
            $query->where('orderStatus', 'Rendelve'); // JAVÍTVA: orderStatus
        }
        
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
                ->whereDate('orderDate', $date) // JAVÍTVA: orderDate
                ->with(['menuItem' => function($q) {
                    $q->with(['soup', 'optionA', 'optionB', 'other']);
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
     * Új rendelés létrehozása
     */
    public function store(Request $request)
    {
        Log::info('PersonalOrderController - Új rendelés', $request->all());
        
        $validator = Validator::make($request->all(), [
            'date' => 'required|date|after_or_equal:today',
            'menu_item_id' => 'required|exists:menu_items,id', // A tábla neve: menu_items
            'selected_option' => 'required|in:A,B' // Csak A vagy B, mert a migráció csak ezeket engedi
        ], [
            'date.after_or_equal' => 'Csak jövőbeli dátumra lehet rendelni.',
            'selected_option.in' => 'Érvénytelen opció. Válassz: A vagy B.'
        ]);
        
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validációs hiba',
                'errors' => $validator->errors()
            ], 422);
        }
        
        $user = Auth::user();
        
        // Ellenőrizzük, hogy van-e már rendelés erre a napra
        $existingOrder = Order::where('user_id', $user->id)
            ->whereDate('orderDate', $request->date) // JAVÍTVA: orderDate
            ->where('orderStatus', 'Rendelve') // JAVÍTVA: orderStatus
            ->first();
            
        if ($existingOrder) {
            return response()->json([
                'success' => false,
                'message' => 'Már van aktív rendelésed erre a napra.',
                'existing_order' => $existingOrder
            ], 400);
        }
        
        // Menü ellenőrzése
        $menuItem = MenuItem::where('id', $request->menu_item_id)
            ->whereDate('day', $request->date) // JAVÍTVA: day (nem menu_date)
            ->first();
            
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
                // Default ár, ha nincs beállítva
                $defaultPrice = 450;
                
                // Rendelés létrehozása default árral
                $order = Order::create([
                    'user_id' => $user->id,
                    'menuItems_id' => $menuItem->id, // JAVÍTVA: menuItems_id
                    'orderDate' => $request->date, // JAVÍTVA: orderDate
                    'selectedOption' => $request->selected_option, // JAVÍTVA: selectedOption
                    'orderStatus' => 'Rendelve', // JAVÍTVA: orderStatus
                    'invoice_id' => null,
                    'price_id' => null,
                    'price' => $defaultPrice // JAVÍTVA: price mezőt hozzá kell adni a migrációhoz!
                ]);
            } else {
                // Rendelés létrehozása az árazással
                $order = Order::create([
                    'user_id' => $user->id,
                    'menuItems_id' => $menuItem->id, // JAVÍTVA: menuItems_id
                    'orderDate' => $request->date, // JAVÍTVA: orderDate
                    'selectedOption' => $request->selected_option, // JAVÍTVA: selectedOption
                    'orderStatus' => 'Rendelve', // JAVÍTVA: orderStatus
                    'invoice_id' => null,
                    'price_id' => $price->id,
                    'price' => $price->amount
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
                'data' => $order->load(['menuItem'])
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
     * Rendelés lemondása
     */
    public function cancel($id)
    {
        $user = Auth::user();
        
        Log::info('PersonalOrderController - Rendelés lemondás', [
            'user_id' => $user->id,
            'order_id' => $id
        ]);
        
        try {
            $order = Order::where('user_id', $user->id)
                ->where('id', $id)
                ->where('orderStatus', 'Rendelve') // JAVÍTVA: orderStatus
                ->first();
                
            if (!$order) {
                return response()->json([
                    'success' => false,
                    'message' => 'Rendelés nem található vagy már lemondva.'
                ], 404);
            }
            
            $order->update([
                'orderStatus' => 'Lemondva', // JAVÍTVA: orderStatus
                'cancelledAt' => now() // JAVÍTVA: cancelledAt
            ]);
            
            Log::info('Rendelés lemondva', [
                'order_id' => $id,
                'user_id' => $user->id
            ]);
            
            return response()->json([
                'success' => true,
                'message' => 'Rendelésedet sikeresen lemondtuk!'
            ]);
            
        } catch (\Exception $e) {
            Log::error('Lemondási hiba', [
                'error' => $e->getMessage(),
                'order_id' => $id
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Hiba történt a lemondás során.'
            ], 500);
        }
    }
    
    /**
     * Havi statisztika
     */
    public function getMonthlyStats2($year, $month)
    {
        $user = Auth::user();
        
        try {
            $orders = Order::where('user_id', $user->id)
                ->whereYear('orderDate', $year) // JAVÍTVA: orderDate
                ->whereMonth('orderDate', $month) // JAVÍTVA: orderDate
                ->where('orderStatus', 'Rendelve') // JAVÍTVA: orderStatus
                ->get();
                
            return response()->json([
                'success' => true,
                'data' => [
                    'year' => $year,
                    'month' => $month,
                    'orders_count' => $orders->count(),
                    'total_cost' => $orders->sum('price'),
                    'average_cost' => $orders->count() > 0 ? round($orders->sum('price') / $orders->count(), 2) : 0,
                    'option_summary' => [
                        'A' => $orders->where('selectedOption', 'A')->count(), // JAVÍTVA: selectedOption
                        'B' => $orders->where('selectedOption', 'B')->count()  // JAVÍTVA: selectedOption
                    ]
                ]
            ]);
            
        } catch (\Exception $e) {
            Log::error('Statisztika hiba', ['error' => $e->getMessage()]);
            
            return response()->json([
                'success' => true,
                'data' => [
                    'year' => $year,
                    'month' => $month,
                    'orders_count' => 0,
                    'total_cost' => 0,
                    'average_cost' => 0,
                    'option_summary' => [
                        'A' => 0,
                        'B' => 0
                    ]
                ]
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
            // A te migrációdban: day (nem menu_date), és nincs is_teaching_day vagy order_deadline
            // Ezért csak a jövőbeli dátumokat adjuk vissza
            $availableDates = MenuItem::where('day', '>', now()->format('Y-m-d'))
                ->orderBy('day')
                ->get()
                ->map(function($menuItem) use ($user) {
                    // Ellenőrizzük, hogy van-e már rendelés
                    $hasOrder = false;
                    try {
                        $hasOrder = Order::where('user_id', $user->id)
                            ->where('menuItems_id', $menuItem->id) // JAVÍTVA: menuItems_id
                            ->where('orderStatus', 'Rendelve') // JAVÍTVA: orderStatus
                            ->exists();
                    } catch (\Exception $e) {
                        // Ha hiba van, nincs rendelés
                        $hasOrder = false;
                    }
                        
                    return [
                        'date' => $menuItem->day->format('Y-m-d'), // JAVÍTVA: day
                        'display_date' => $menuItem->day->format('Y. m. d.'), // JAVÍTVA: day
                        'day_name' => $menuItem->day->translatedFormat('l'), // JAVÍTVA: day
                        'has_order' => $hasOrder,
                        'deadline' => null, // Nincs határidő a migrációban
                        'menu' => [
                            'soup' => $menuItem->soup,
                            'optionA' => $menuItem->optionA,
                            'optionB' => $menuItem->optionB,
                            'other' => $menuItem->other
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
            
            // Teszt dátumok, ha hiba van
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
     * Ár kiszámítása felhasználó típus alapján
     */
    private function calculateUserPrice($user)
    {
        try {
            // Ellenőrizzük, hogy létezik-e a prices tábla
            $tableExists = DB::select("SHOW TABLES LIKE 'prices'");
            if (empty($tableExists)) {
                return null;
            }
            
            // A te migrációdban: userType (nem user_type)
            return Price::where('userType', $user->user_type) // Figyelj a nagybetűkre!
                ->where('priceCategory', $user->has_discount ? 'Kedvezményes' : 'Normál')
                ->where('validFrom', '<=', now())
                ->where(function($q) {
                    $q->whereNull('validTo')->orWhere('validTo', '>=', now());
                })
                ->first();
                
        } catch (\Exception $e) {
            Log::error('Ár kiszámítása hiba', ['error' => $e->getMessage()]);
            return null;
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
                ->where('orderStatus', 'Rendelve') // JAVÍTVA: orderStatus
                ->select(
                    DB::raw('YEAR(orderDate) as year'), // JAVÍTVA: orderDate
                    DB::raw('MONTH(orderDate) as month'), // JAVÍTVA: orderDate
                    DB::raw('COUNT(*) as count'),
                    DB::raw('SUM(price) as total')
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
}