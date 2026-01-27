<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class OrderSeeder extends Seeder
{
    public function run(): void
    {
        // Ellenőrizzük, hogy létezik-e a user id=2
        $userExists = DB::table('users')->where('id', 2)->exists();
        
        if (!$userExists) {
            $this->command->error('User id=2 nem létezik');
            return;
        }
        

        // Ellenőrizzük, hogy van-e Price
        $pricesCount = DB::table('prices')->count();
        

        
        $this->command->info('Rendelések létrehozása user id=2 számára...');
        
        // Aktuális dátum
        $now = Carbon::now();
        
        // Különböző dátumok a rendelésekhez
        $dates = [
            $now->copy()->subDays(3)->format('Y-m-d'), // 3 napja
            $now->copy()->subDays(1)->format('Y-m-d'), // tegnap
            $now->copy()->format('Y-m-d'),            // ma
            $now->copy()->addDays(1)->format('Y-m-d'), // holnap
            $now->copy()->addDays(2)->format('Y-m-d'), // holnapután
            $now->copy()->addDays(3)->format('Y-m-d'), // 3 nap múlva
        ];
        
        // MenuItem ID-k lekérdezése
        $menuItems = DB::table('menuitems')->take(3)->get();
        
        if ($menuItems->isEmpty()) {
            $this->command->error('Nincsenek MenuItem-ek!');
            return;
        }
        
        // Price ID lekérdezése (első ár)
        $price = DB::table('prices')->first();
        
        if (!$price) {
            $this->command->error('Nincsenek árak!');
            return;
        }
        
        // Rendelések adatai
        $orders = [];
        
        foreach ($dates as $index => $date) {
            $menuItem = $menuItems[$index % count($menuItems)];
            
            $orders[] = [
                'user_id' => 2, // FIX: user id=2
                'menuItems_id' => $menuItem->id,
                'orderDate' => $date,
                'selectedOption' => $index % 2 == 0 ? 'A' : 'B', // Váltakozva A és B opció
                'orderStatus' => $this->getStatusForDate($date, $now),
                'invoice_id' => null,
                'price_id' => $price->id,
                'cancelledAt' => $date < $now->format('Y-m-d') ? $now->copy()->subHours(2) : null,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }
        
        // Rendelések beszúrása
        DB::table('orders')->insert($orders);
        
        $this->command->info(count($orders) . ' rendelés létrehozva user id=2 számára');
        $this->command->info('Dátumok: ' . implode(', ', $dates));
    }
    
    /**
     * Status meghatározása dátum alapján
     */
    private function getStatusForDate($date, $now)
    {
        $dateObj = Carbon::parse($date);
        $nowDate = $now->format('Y-m-d');
        
        if ($date < $nowDate) {
            // Múltbeli dátumok: 50% eséllyel Lemondva
            return rand(0, 1) ? 'Lemondva' : 'Rendelve';
        } elseif ($date == $nowDate) {
            // Mai nap: mindig Rendelve
            return 'Rendelve';
        } else {
            // Jövőbeli dátumok: 70% eséllyel Rendelve
            return rand(0, 9) < 7 ? 'Rendelve' : 'Lemondva';
        }
    }
    
    
    
    

}