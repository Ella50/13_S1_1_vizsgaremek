<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class PriceSeeder extends Seeder
{
    public function run(): void
    {
        $now = Carbon::now();
        $nextYear = $now->copy()->addYear();
        
        $prices = [
       
            [
                'userType' => 'Tanuló',
                'priceCategory' => 'Normál',
                'amount' => 450.00,
                'validFrom' => '2024-09-01',
                'validTo' => null,

            ],
            [
                'userType' => 'Tanuló',
                'priceCategory' => 'Kedvezményes',
                'amount' => 300.00,
                'validFrom' => '2024-09-01',
                'validTo' => null,

            ],
            
          
            [
                'userType' => 'Tanár',
                'priceCategory' => 'Normál',
                'amount' => 650.00,
                'validFrom' => '2024-09-01',
                'validTo' => null,

            ],
            [
                'userType' => 'Tanár',
                'priceCategory' => 'Kedvezményes',
                'amount' => 400.00,
                'validFrom' => '2024-09-01',
                'validTo' => null,

            ],
            
        
            [
                'userType' => 'Dolgozó',
                'priceCategory' => 'Normál',
                'amount' => 600.00,
                'validFrom' => '2024-09-01',
                'validTo' => null,

            ],
            
           
            [
                'userType' => 'Külsős',
                'priceCategory' => 'Normál',
                'amount' => 750.00,
                'validFrom' => '2024-09-01',
                'validTo' => null,

            ],
            

        ];
        
        DB::table('prices')->insert($prices);
        
        $this->command->info('Árak sikeresen létrehova:' . count($prices));
    }
}