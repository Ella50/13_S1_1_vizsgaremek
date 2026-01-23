<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\MenuItem;
use App\Models\Meal;
use Carbon\Carbon;

class MenuItemSeeder extends Seeder
{
    private array $menuCombinations = [
        // Leves + A opció + B opció + Egyéb (opcionális)
        [
            'soup' => 'Gombaleves',
            'optionA' => 'Marhapörkölt',
            'optionB' => 'Sajtos tészta',
            'other' => null,
        ],
        [
            'soup' => 'Húsleves',
            'optionA' => 'Rántott csirke',
            'optionB' => 'Grillcsirke',
            'other' => 'Gyümölcssaláta',
        ],
        [
            'soup' => 'Paradicsomleves',
            'optionA' => 'Lasagne',
            'optionB' => 'Marhapörkölt',
            'other' => null,
        ],
        [
            'soup' => 'Gombaleves',
            'optionA' => 'Grillcsirke',
            'optionB' => 'Sajtos tészta',
            'other' => 'Gyümölcssaláta',
        ],
        [
            'soup' => 'Húsleves',
            'optionA' => 'Lasagne',
            'optionB' => 'Rántott csirke',
            'other' => null,
        ],
    ];

    private array $holidays = [
        '01-01', '03-15', '05-01', '08-20', '10-23', '11-01', '12-25', '12-26',
    ];

    public function run(): void
    {

        // Ellenőrizzük, hogy vannak-e ételek
        $mealsCount = Meal::count();

        
        if ($mealsCount === 0) {
            $this->command->error('No meals found! Please run MealSeeder first.');
            return;
        }

        // Ellenőrizzük az ételeket

        $requiredMeals = ['Gombaleves', 'Marhapörkölt', 'Sajtos tészta', 'Húsleves', 'Rántott csirke', 
                         'Grillcsirke', 'Paradicsomleves', 'Lasagne', 'Gyümölcssaláta'];
        
        foreach ($requiredMeals as $mealName) {
            $meal = Meal::where('mealName', $mealName)->first();
            if (!$meal) {
                $this->command->error("Missing meal: {$mealName}");
            }
        }

        // Táblázat ürítése vagy ellenőrzése
        $existingMenuItems = MenuItem::count();
        if ($existingMenuItems > 0) {
            
            MenuItem::truncate();

        }

        // Menü elemek generálása

        $menuItems = $this->generateNextMonthMenuItems();
        
        if (empty($menuItems)) {
            return;
        }

        $createdCount = 0;
        $errors = [];

        foreach ($menuItems as $menuItem) {
            try {
                // Ellenőrizzük az ID-kat
                $soupId = $this->findMealIdByName($menuItem['soup_name']);
                $optionAId = $this->findMealIdByName($menuItem['optionA_name']);
                $optionBId = $this->findMealIdByName($menuItem['optionB_name']);
                $otherId = $menuItem['other_name'] ? $this->findMealIdByName($menuItem['other_name']) : null;

                if (!$soupId || !$optionAId || !$optionBId) {
                    $errors[] = "Missing IDs for: soup={$menuItem['soup_name']}, A={$menuItem['optionA_name']}, B={$menuItem['optionB_name']}";
                    continue;
                }

                MenuItem::create([
                    'day' => $menuItem['day'],
                    'soup' => $soupId,
                    'optionA' => $optionAId,
                    'optionB' => $optionBId,
                    'other' => $otherId,
                ]);
                
                $createdCount++;
                

            } catch (\Exception $e) {
                $errors[] = "Failed for {$menuItem['day']}: " . $e->getMessage();
            }
        }

        if (!empty($errors)) {
            $this->command->error('Errors occurred:');
            foreach ($errors as $error) {
                $this->command->error("  - {$error}");
            }
        }

        $this->command->info("\n Menük sikeresen létrehozva: {$createdCount}");
        
        $generatedDates = MenuItem::orderBy('day')->pluck('day')->toArray();

    }

    private function generateNextMonthMenuItems(): array
    {
        $menuItems = [];
        $combinationIndex = 0;
        $combinationsCount = count($this->menuCombinations);

        // Kezdődátum: ma
        $startDate = Carbon::today();

        // Havi menü generálás
        $daysGenerated = 0;
        $maxDays = 31;
        
        for ($dayOffset = 0; $dayOffset < $maxDays && $daysGenerated < 22; $dayOffset++) {
            $currentDate = $startDate->copy()->addDays($dayOffset);
            
            // Ellenőrizzük, hogy még a következő hónapban van-e
            $nextMonth = $startDate->copy()->addMonth()->month;
            if ($currentDate->month > $nextMonth) {
                break;
            }

  
            if ($this->shouldSkipDate($currentDate)) {
                continue;
            }


            $combination = $this->menuCombinations[$combinationIndex % $combinationsCount];
            
            $menuItems[] = [
                'day' => $currentDate->toDateString(),
                'soup_name' => $combination['soup'],
                'optionA_name' => $combination['optionA'],
                'optionB_name' => $combination['optionB'],
                'other_name' => $combination['other'],
            ];

            $combinationIndex++;
            $daysGenerated++;
        }

        return $menuItems;
    }

    private function shouldSkipDate(Carbon $date): bool
    {
        // Hétvége
        if ($date->isWeekend()) {
            return true;
        }

        // Ünnepnap
        $monthDay = $date->format('m-d');
        if (in_array($monthDay, $this->holidays)) {
            // Ellenőrizzük, hogy az aktuális évben van-e
            $currentYear = Carbon::now()->year;
            $holidayDate = Carbon::createFromFormat('Y-m-d', "{$currentYear}-{$monthDay}");
            
            // Ha az ünnepnap múlt héten volt
            if ($holidayDate->isPast()) {
                $nextYear = $currentYear + 1;
                $holidayDate = Carbon::createFromFormat('Y-m-d', "{$nextYear}-{$monthDay}");
            }
            
            // Ha az ünnepnap a következő hónapban
            if ($holidayDate->between(
                Carbon::now()->startOfMonth()->addMonth(),
                Carbon::now()->endOfMonth()->addMonth()
            )) {
                return true;
            }
        }

        return false;
    }

    private function findMealIdByName(string $mealName): ?int
    {
        $meal = Meal::where('mealName', $mealName)->first();
        return $meal ? $meal->id : null;
    }
}