<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RfidCardTableSeeder extends Seeder
{
    public function run()
    {
        for ($i = 1; $i <= 20; $i++) {
            DB::table('rfidCard')->insert([
                'id' => $i,
                'cardNumber' => '000000' . str_pad($i, 6, '0', STR_PAD_LEFT),
                'lastUsedAt' => now(),
                'isActive' => true,
                'created_at' => now(),
                'updated_at' => now()
            ]);
        }
    }
}