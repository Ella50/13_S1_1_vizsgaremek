<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\County;
use App\Models\City;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

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
            throw new \Exception("CSV fájl nem található: " . $csvPath);
        }

        $csvData = array_map('str_getcsv', file($csvPath));
        $header = array_shift($csvData); // Fejléc eltávolítása

       

        $counties = [];
        $cities = [];

        foreach ($csvData as $row) {
            if (count($row) < 3) continue;
            
            [$zipCode, $cityName, $countyName] = $row;
            
            // Megyék mentése
            if (!isset($counties[$countyName])) {
                $counties[$countyName] = County::create(['name' => trim($countyName)]);
            }
            
            // Városok mentése
            $cities[] = [
                'name' => trim($cityName),
                'zip_code' => trim($zipCode),
                'county_id' => $counties[$countyName]->id,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        // Tömeges beszúrás
        City::insert($cities);

        $this->command->info(count($counties) . ' megye és ' . count($cities) . ' város sikeresen betöltve.');
    }
}