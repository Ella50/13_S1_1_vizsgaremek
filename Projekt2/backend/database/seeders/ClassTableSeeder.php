<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ClassTableSeeder extends Seeder
{
    public function run()
    {
        DB::table('class')->insert([
            ['className' => '9.A', 'created_at' => now(), 'updated_at' => now()],
            ['className' => '9.B', 'created_at' => now(), 'updated_at' => now()],
            ['className' => '10.D', 'created_at' => now(), 'updated_at' => now()],
            
        ]);
    }
}