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
        // Töröljük a régi adatokat
        DB::statement('SET FOREIGN_KEY_CHECKS=0');
        City::truncate();
        County::truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1');

        // CSV fájl beolvasása
        $csvPath = storage_path('iranyitoszamok.csv');
        
        if (!file_exists($csvPath)) {
            $this->command->error("CSV fájl nem található: " . $csvPath);
            return;
        }

        // Olvassuk be a fájlt soronként
        $lines = file($csvPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        
        // Fejléc eltávolítása (első sor)
        array_shift($lines);

        $counties = [];
        $cities = [];
        $counter = 0;

        foreach ($lines as $line) {
            $counter++;
            
            // Debug minden 1000. sor
            if ($counter % 1000 === 0) {
                $this->command->info("Feldolgozva: $counter sor");
            }
            
            // Szétválasztjuk pontosvesszővel
            $row = explode(';', $line);
            
            if (count($row) < 3) continue;
            
            $zipCode = trim($row[0]);
            $cityName = trim($row[1]);
            $countyName = trim($row[2]);
            
            // Üres értékek ellenőrzése
            if (empty($zipCode) || empty($cityName) || empty($countyName)) {
                continue;
            }
            
            // Megyék mentése - KIJAVÍTVA: timestamps nélkül
            if (!isset($counties[$countyName])) {
                // Közvetlen SQL insert, hogy ne próbáljon timestamps-t beszúrni
                $countyId = DB::table('counties')->insertGetId([
                    'countyName' => $countyName
                ]);
                $counties[$countyName] = $countyId;
            }

            // Városok mentése
            $cities[] = [
                'cityName' => $cityName,
                'zipCode' => $zipCode,
                'county_id' => $counties[$countyName],
            ];
            
            // Batch insert minden 500 rekord után
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

        // Maradék városok beszúrása
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