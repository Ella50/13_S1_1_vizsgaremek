<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\MenuItem;
use Carbon\Carbon;

class MenuController extends Controller
{
    // Már létező menüs napok
    public function existingDates()
    {
        $dates = MenuItem::query()
            ->orderBy('day')
            ->pluck('day')
            ->map(fn($d) => Carbon::parse($d)->format('Y-m-d'))
            ->values();
            
        return response()->json($dates);
    }

    // Még szabad napok
    public function availableDates(Request $request)
    {
        $month = $request->query('month');
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

    // Menü lekérése adott napra
    public function getMenuByDate($date)
    {
        $menu = MenuItem::where('day', $date)
            ->with(['soupMeal', 'optionAMeal', 'optionBMeal', 'otherMeal'])
            ->first();

        if (!$menu) {
            return response()->json([
                'success' => true,
                'data' => null,
                'message' => 'Nincs menü erre a napra.'
            ], 200);
        }

        return response()->json([
            'success' => true,
            'data' => $menu->getMenuWithMeals()
        ]);
    }

    // Menü mentése (CREATE + UPDATE egyben)
    public function saveMenu(Request $request, $id = null)
    {
        $request->validate([
            'day' => 'required|date',
            'soup' => 'required|exists:meals,id',
            'optionA' => 'required|exists:meals,id',
            'optionB' => 'required|exists:meals,id',
            'other' => 'nullable|exists:meals,id'
        ]);

        // Ha van ID, akkor frissítünk
        if ($id) {
            $menu = MenuItem::find($id);
            
            if (!$menu) {
                return response()->json([
                    'success' => false,
                    'message' => 'Menü nem található'
                ], 404);
            }
            
            $menu->update([
                'soup' => $request->soup,
                'optionA' => $request->optionA,
                'optionB' => $request->optionB,
                'other' => $request->other
            ]);
            
            $message = 'Menü sikeresen frissítve.';
        } else {
            // Nincs ID, új menü létrehozása
            $menu = MenuItem::updateOrCreate(
                ['day' => $request->day],
                [
                    'soup' => $request->soup,
                    'optionA' => $request->optionA,
                    'optionB' => $request->optionB,
                    'other' => $request->other
                ]
            );
            $message = 'Menü sikeresen mentve.';
        }

        return response()->json([
            'success' => true, 
            'message' => $message,
            'data' => $menu->getMenuWithMeals()
        ]);
    }
    // Mai menü lekérése
    public function getTodayMenu()
    {
        $today = Carbon::today()->toDateString();

        $menu = MenuItem::where('day', $today)
            ->with([
                'soupMeal.ingredients.allergens',
                'optionAMeal.ingredients.allergens',
                'optionBMeal.ingredients.allergens',
                'otherMeal.ingredients.allergens'
            ])
            ->first();

        if (!$menu) {
            return response()->json([
                'success' => true,
                'data' => null,
                'message' => 'Nincs menü a mai napra.'
            ], 200);
        }

        // Formázd az adatokat, hogy tartalmazzák az allergéneket
        $formattedMenu = [
            'id' => $menu->id,
            'day' => $menu->day,
            'soup' => $menu->soupMeal ? $this->formatMealWithAllergens($menu->soupMeal) : null,
            'optionA' => $menu->optionAMeal ? $this->formatMealWithAllergens($menu->optionAMeal) : null,
            'optionB' => $menu->optionBMeal ? $this->formatMealWithAllergens($menu->optionBMeal) : null,
            'other' => $menu->otherMeal ? $this->formatMealWithAllergens($menu->otherMeal) : null,
        ];

        return response()->json([
            'success' => true,
            'data' => $formattedMenu
        ]);
    }

    // Segédmetódus az étel allergénekkel való formázására
    private function formatMealWithAllergens($meal)
    {
        if (!$meal) return null;
        
        // Összegyűjtjük az összes allergént az összetevőkből
        $allAllergens = collect();
        foreach ($meal->ingredients as $ingredient) {
            if ($ingredient->allergens && $ingredient->allergens->isNotEmpty()) {
                $allAllergens = $allAllergens->merge($ingredient->allergens);
            }
        }
        
        $uniqueAllergens = $allAllergens->unique('id')->map(function($allergen) {
            return [
                'id' => $allergen->id,
                'allergenName' => $allergen->allergenName,
                'icon' => $allergen->icon,
                'icon_url' => $this->getAllergenIconUrl($allergen->icon)
            ];
        })->values()->toArray();
        
        return [
            'id' => $meal->id,
            'mealName' => $meal->mealName,
            'mealType' => $meal->mealType,
            'description' => $meal->description,
            'picture' => $meal->picture,
            'allergens' => $uniqueAllergens
        ];
    }

    private function getAllergenIconUrl($iconPath)
    {
        if (!$iconPath) return null;
        
        if (filter_var($iconPath, FILTER_VALIDATE_URL)) {
            return $iconPath;
        }
        
        return asset('storage/' . ltrim($iconPath, '/'));
    }

    // Heti menü lekérése
    public function getWeeklyMenu(Request $request)
    {
        $from = $request->query('from');

        $start = $from
            ? Carbon::parse($from)->startOfDay()
            : Carbon::now()->startOfWeek(Carbon::MONDAY)->startOfDay();

        $end = (clone $start)->addDays(6)->endOfDay();

        $menusByDay = MenuItem::query()
            ->whereBetween('day', [$start, $end])
            ->with(['soupMeal', 'optionAMeal', 'optionBMeal', 'otherMeal'])
            ->get()
            ->keyBy(fn($m) => Carbon::parse($m->day)->toDateString());

        $days = [];

        for ($i = 0; $i < 7; $i++) {
            $d = (clone $start)->addDays($i)->toDateString();
            $menuItem = $menusByDay->get($d);

            $days[] = [
                'day' => $d,
                'day_name' => Carbon::parse($d)->locale('hu')->dayName,
                'menu' => $menuItem ? $menuItem->getMenuWithMeals() : null
            ];
        }

        return response()->json([
            'success' => true,
            'data' => $days,
            'week_start' => $start->toDateString(),
            'week_end' => $end->toDateString()
        ]);
    }

    
    public function listMenus(Request $request)
    {
        $year = $request->query('year');
        $month = $request->query('month');
        $perPage = $request->query('per_page', 10);
        
        $query = MenuItem::query()
            ->with(['soupMeal', 'optionAMeal', 'optionBMeal', 'otherMeal'])
            ->orderBy('day', 'desc');
        
        // Szűrés év és hónap alapján
        if ($year && $month) {
            $query->whereYear('day', $year)
                ->whereMonth('day', $month);
        }
        
        $menus = $query->paginate($perPage);
        
        $formattedMenus = $menus->getCollection()->map(function($menu) {
            return $menu->getMenuWithMeals();
        });
        
        $menus->setCollection($formattedMenus);
        
        return response()->json([
            'success' => true,
            'data' => $menus
        ]);
    }

    public function destroy($id)
    {
        $menu = MenuItem::find($id);
        
        if (!$menu) {
            return response()->json([
                'success' => false,
                'message' => 'Menü nem található'
            ], 404);
        }
        
        $menu->delete();
        
        return response()->json([
            'success' => true,
            'message' => 'Menü sikeresen törölve'
        ]);
    }
    
}