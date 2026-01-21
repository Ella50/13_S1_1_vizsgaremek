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
        return response()->json(
            MenuItem::distinct()->pluck('day')

        );
    }

    // 🔹 Még szabad napok
    public function availableDates(Request $request)
    {
        $month = $request->query('month'); // pl: 2026-01
        $usedDays = MenuItem::pluck('day')->toArray();

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

    public function getTodayMenu(Request $request)
    {
        return response()->json([
        'HIT' => true,
        'FILE' => __FILE__,
        'METHOD' => __METHOD__,
    ], 200);
        $today = Carbon::today()->toDateString();

        // DEBUG csak ha kéred: /api/menu/today?debug=1
        if ($request->query('debug') == 1) {
            return response()->json([
                'ping' => true,
                'today' => $today,
                'now' => now()->toDateTimeString(),
                'timezone' => config('app.timezone'),
                'rows_today' => MenuItem::where('day', $today)->count(),
            ], 200);
        }

        $menu = MenuItem::query()
            ->where('day', $today) // elég sima where, mert day string "YYYY-MM-DD"
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



   


}
