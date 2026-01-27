<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class PriceSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('Árak létrehozása...');
        
        
        $now = Carbon::now();
        $nextYear = $now->copy()->addYear();
        
        $prices = [
            // ========== TANULÓK ==========
            [
                'userType' => 'Tanuló',
                'priceCategory' => 'Normál',
                'amount' => 450.00,
                'validFrom' => '2024-09-01',
                'validTo' => '2024-12-31',
                'created_at' => $now,
                'updated_at' => $now
            ],
            [
                'userType' => 'Tanuló',
                'priceCategory' => 'Normál',
                'amount' => 480.00,
                'validFrom' => '2025-01-01',
                'validTo' => '2025-06-30',
                'created_at' => $now,
                'updated_at' => $now
            ],
            [
                'userType' => 'Tanuló',
                'priceCategory' => 'Kedvezményes',
                'amount' => 300.00,
                'validFrom' => '2024-09-01',
                'validTo' => '2025-06-30',
                'created_at' => $now,
                'updated_at' => $now
            ],
            
            // ========== TANÁROK ==========
            [
                'userType' => 'Tanár',
                'priceCategory' => 'Normál',
                'amount' => 650.00,
                'validFrom' => '2024-09-01',
                'validTo' => '2025-06-30',
                'created_at' => $now,
                'updated_at' => $now
            ],
            [
                'userType' => 'Tanár',
                'priceCategory' => 'Kedvezményes',
                'amount' => 400.00,
                'validFrom' => '2024-09-01',
                'validTo' => '2025-06-30',
                'created_at' => $now,
                'updated_at' => $now
            ],
            
            // ========== DOLGOZÓK ==========
            [
                'userType' => 'Dolgozó',
                'priceCategory' => 'Normál',
                'amount' => 600.00,
                'validFrom' => '2024-09-01',
                'validTo' => '2025-06-30',
                'created_at' => $now,
                'updated_at' => $now
            ],
            
            // ========== KÜLSŐSÖK ==========
            [
                'userType' => 'Külsős',
                'priceCategory' => 'Normál',
                'amount' => 750.00,
                'validFrom' => '2024-09-01',
                'validTo' => '2025-06-30',
                'created_at' => $now,
                'updated_at' => $now
            ],
            
            // ========== ADMIN/KONYHA ==========
            [
                'userType' => 'Admin',
                'priceCategory' => 'Normál',
                'amount' => 0.00, // ingyen
                'validFrom' => '2024-09-01',
                'validTo' => '2025-06-30',
                'created_at' => $now,
                'updated_at' => $now
            ],
            [
                'userType' => 'Konyha',
                'priceCategory' => 'Normál',
                'amount' => 0.00, // ingyen
                'validFrom' => '2024-09-01',
                'validTo' => '2025-06-30',
                'created_at' => $now,
                'updated_at' => $now
            ]
        ];
        
        DB::table('prices')->insert($prices);
        
        $this->command->info(count($prices) . ' ár sikeresen létrehozva!');
        $this->command->info('User típusok: Tanuló, Tanár, Dolgozó, Külsős, Admin, Konyha');
        $this->command->info('Árkategóriák: Normál, Kedvezményes');
    }
}