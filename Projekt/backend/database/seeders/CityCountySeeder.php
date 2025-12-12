<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\County;
use App\Models\City;
use Illuminate\Support\Facades\DB;

class CityCountySeeder extends Seeder
{
    public function run(): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0');
        City::truncate();
        County::truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1');

        $csvPath = storage_path('iranyitoszamok.csv');
        
        if (!file_exists($csvPath)) {
            $this->command->error("CSV fájl nem található: " . $csvPath);
            return;
        }

        $lines = file($csvPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        
        array_shift($lines);

        $counties = [];
        $cities = [];
        $counter = 0;

        foreach ($lines as $line) {
            $counter++;
            
            // Debug (minden 1000 sor)
            if ($counter % 1000 === 0) {
                $this->command->info("Feldolgozva: $counter sor");
            }
            
            $row = explode(';', $line);
            
            if (count($row) < 3) continue;
            
            $zipCode = trim($row[0]);
            $cityName = trim($row[1]);
            $countyName = trim($row[2]);
            
            if (empty($zipCode) || empty($cityName) || empty($countyName)) {
                continue;
            }
            
            if (!isset($counties[$countyName])) {
                $countyId = DB::table('counties')->insertGetId([
                    'countyName' => $countyName
                ]);
                $counties[$countyName] = $countyId;
            }

            $cities[] = [
                'cityName' => $cityName,
                'zipCode' => $zipCode,
                'county_id' => $counties[$countyName],
            ];
            
            // Batch insert (minden 500)
            if (count($cities) >= 500) {
                try {
                    DB::table('cities')->insert($cities);
                    $cities = [];
                } catch (\Exception $e) {
                    $this->command->error("Hiba batch insert közben: " . $e->getMessage());
                    $cities = [];
                }
            }
        }

        if (!empty($cities)) {
            try {
                DB::table('cities')->insert($cities);
            } catch (\Exception $e) {
                $this->command->error("Maradék hiba: " . $e->getMessage());
            }
        }

        $this->command->info(count($counties) . ' megye és ' . DB::table('cities')->count() . ' város sikeresen betöltve.');
    }
}