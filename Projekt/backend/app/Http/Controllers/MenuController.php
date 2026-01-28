<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\MenuItem;
use Carbon\Carbon;

class MenuController extends Controller
{
    // 🔹 Már létező menüs napok
    public function existingDates()
    {
        $dates = MenuItem::query()
            ->orderBy('day')
            ->pluck('day')
            ->map(fn($d) => Carbon::parse($d)->format('Y-m-d'))
            ->values();
            
        return response()->json($dates);
    }

    // 🔹 Még szabad napok
    public function availableDates(Request $request)
    {
        $month = $request->query('month'); // pl: 2026-01
        $usedDays = MenuItem::query()
            ->pluck('day')
            ->map(fn($d) => Carbon::parse($d)->toDateString())
            ->toArray();

        if ($month) {
            $start = Carbon::parse($month . '-01');
            $end = $start->copy()->endOfMonth();
        } else {
            $start = Carbon::today();
            $end = $start->copy()->addMonth();
        }

        $available = [];

        for ($d = $start->copy(); $d->lte($end); $d->addDay()) {
            if (!in_array($d->toDateString(), $usedDays)) {
                $available[] = $d->toDateString();
            }
        }

        return response()->json($available);
    }

    // 🔹 Menü lekérése adott napra
    public function getMenuByDate($date)
    {
        $menu = MenuItem::where('day', $date)
            ->with(['soupMeal', 'optionAMeal', 'optionBMeal', 'otherMeal'])
            ->first();

        if (!$menu) {
            return response()->json(null, 200);
        }

        return response()->json($menu->getMenuWithMeals());
    }

    // 🔹 Menü mentése (CREATE + UPDATE egyben)
    public function saveMenu(Request $request)
    {
        $request->validate([
            'day' => 'required|date',
            'soup' => 'required|exists:meals,id',
            'optionA' => 'required|exists:meals,id',
            'optionB' => 'required|exists:meals,id',
            'other' => 'nullable|exists:meals,id'
        ]);

        $menu = MenuItem::updateOrCreate(
            ['day' => $request->day],
            [
                'soup' => $request->soup,
                'optionA' => $request->optionA,
                'optionB' => $request->optionB,
                'other' => $request->other
            ]
        );

        return response()->json([
            'success' => true, 
            'message' => 'Menü sikeresen mentve.',
            'menu' => $menu->getMenuWithMeals()
        ]);
    }

    public function getTodayMenu()
    {
        $today = Carbon::today()->toDateString();

        $menu = MenuItem::where('day', $today)
            ->with(['soupMeal', 'optionAMeal', 'optionBMeal', 'otherMeal'])
            ->first();

        if (!$menu) {
            return response()->json([
                'success' => true,
                'data' => null,
                'message' => 'Nincs menü a mai napra.'
            ], 200);
        }

        return response()->json([
            'success' => true,
            'data' => $menu->getMenuWithMeals()
        ], 200);
    }

    public function getWeeklyMenu(Request $request)
    {
        $from = $request->query('from');
        
        $start = $from
            ? Carbon::parse($from)->startOfWeek(Carbon::MONDAY)
            : Carbon::now()->startOfWeek(Carbon::MONDAY);
            
        $end = $start->copy()->addDays(6);

        $menus = MenuItem::whereBetween('day', [$start->toDateString(), $end->toDateString()])
            ->with(['soupMeal', 'optionAMeal', 'optionBMeal', 'otherMeal'])
            ->get()
            ->keyBy(fn($item) => $item->day->format('Y-m-d'));

        $out = [];
        for ($i = 0; $i < 7; $i++) {
            $day = $start->copy()->addDays($i)->format('Y-m-d');
            $menu = $menus->get($day);
            
            $out[] = [
                'day' => $day,
                'day_name' => $this->getHungarianDayName($day),
                'menu' => $menu ? $menu->getMenuWithMeals() : null
            ];
        }

        return response()->json([
            'success' => true,
            'data' => $out,
            'week_start' => $start->format('Y-m-d'),
            'week_end' => $end->format('Y-m-d')
        ], 200);
    }
    
    private function getHungarianDayName($date)
    {
        $days = [
            'Monday' => 'Hétfő',
            'Tuesday' => 'Kedd',
            'Wednesday' => 'Szerda',
            'Thursday' => 'Csütörtök',
            'Friday' => 'Péntek',
            'Saturday' => 'Szombat',
            'Sunday' => 'Vasárnap'
        ];
        
        $carbonDate = Carbon::parse($date);
        return $days[$carbonDate->format('l')] ?? $carbonDate->format('l');
    }
}

/*
class MenuController extends Controller
{
    // 🔹 Már létező menüs napok
    public function existingDates()
    {
        return response()->json(
            MenuItem::query()
                ->orderBy('day')
                ->pluck('day')
                ->map(fn($d) => substr((string)$d, 0, 10))
                ->values()
        );
    }


    // 🔹 Még szabad napok
    public function availableDates(Request $request)
    {
        $month = $request->query('month'); // pl: 2026-01
        $usedDays = MenuItem::query()
            ->pluck('day')
            ->map(fn($d) => \Carbon\Carbon::parse($d)->toDateString())
            ->toArray();

        if ($month) {
            $start = Carbon::parse($month . '-01');
            $end = $start->copy()->endOfMonth();
        } else {
            $start = Carbon::today();
            $end = $start->copy()->addMonth();
        }

        $available = [];

        for ($d = $start->copy(); $d->lte($end); $d->addDay()) {
            if (!in_array($d->toDateString(), $usedDays)) {
                $available[] = $d->toDateString();
            }
        }

        return response()->json($available);
    }

    // 🔹 Menü lekérése adott napra
    public function getMenuByDate($date)
    {
        $menu = MenuItem::where('day', $date)
            ->with(['soupMeal', 'optionAMeal', 'optionBMeal'])
            ->first();

        return response()->json($menu);
    }

    // 🔹 Menü mentése (CREATE + UPDATE egyben)
    public function saveMenu(Request $request)
    {
        $request->validate([
            'day' => 'required|date',
            'soup' => 'required|exists:meals,id',
            'optionA' => 'required|exists:meals,id',
            'optionB' => 'required|exists:meals,id',
        ]);

        $menu = MenuItem::updateOrCreate(
            ['day' => $request->day],
            [
                'soup' => $request->soup,
                'optionA' => $request->optionA,
                'optionB' => $request->optionB,
            ]
        );

        return response()->json(['success' => true, 'menu' => $menu]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'day' => 'required|date',
            'soup' => 'required|exists:meals,id',
            'optionA' => 'required|exists:meals,id',
            'optionB' => 'required|exists:meals,id',
        ]);

        MenuItem::create([
            'day' => $request->day,
            'soup' => $request->soup,
            'optionA' => $request->optionA,
            'optionB' => $request->optionB,
        ]);

        return response()->json(['success' => true]);
    }

    public function update(Request $request, MenuItem $menu)
    {
        $validated = $request->validate([
            'day' => 'required|date',
            'soup' => 'required|exists:meals,id',
            'optionA' => 'required|exists:meals,id',
            'optionB' => 'required|exists:meals,id',
        ]);

        $menu->update([
            'day' => $validated['day'],
            'soup' => $validated['soup'],
            'optionA' => $validated['optionA'],
            'optionB' => $validated['optionB'],
        ]);

        return response()->json($menu);
    }

    public function getTodayMenu()
    {
        $today = Carbon::today()->toDateString();

        $menu = MenuItem::query()
            ->whereDate('day', $today)
            ->with(['soupMeal', 'optionAMeal', 'optionBMeal', 'otherMeal'])
            ->first();

        if (!$menu) {
            return response()->json(null, 200);
        }

        return response()->json([
            'id' => $menu->id,
            'day' => $menu->day,
            'soup' => $menu->soupMeal,
            'optionA' => $menu->optionAMeal,
            'optionB' => $menu->optionBMeal,
            'other' => $menu->otherMeal,
        ], 200);
    }

    public function getWeeklyMenu(Request $request)
    {
        // from=YYYY-MM-DD (hétfő). Ha nincs, most hétfőjétől számoljuk.
        $from = $request->query('from');

        $start = $from
            ? Carbon::parse($from)->startOfDay()
            : Carbon::now()->startOfWeek(Carbon::MONDAY)->startOfDay();

        $end = $start->copy()->addDays(6)->endOfDay();

        // Lekérjük az adott hét menüit
        $menus = MenuItem::query()
            ->whereBetween('day', [$start->toDateString(), $end->toDateString()])
            ->with(['soupMeal', 'optionAMeal', 'optionBMeal', 'otherMeal'])
            ->get()
            ->keyBy('day');

        // Fix 7 napos lista: ha nincs menü, menu=null
        $out = [];
        for ($i = 0; $i < 7; $i++) {
            $day = $start->copy()->addDays($i)->toDateString();
            $m = $menus->get($day);

            $out[] = [
                'day' => $day,
                'menu' => $m ? [
                    'id' => $m->id,
                    'day' => $m->day,
                    'soup' => $m->soupMeal,
                    'optionA' => $m->optionAMeal,
                    'optionB' => $m->optionBMeal,
                    'other' => $m->otherMeal,
                ] : null
            ];
        }

        return response()->json($out, 200);
    }

}*/
