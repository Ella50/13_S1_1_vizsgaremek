<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class GroupTableSeeder extends Seeder
{
    public function run()
    {
        DB::table('group')->insert([
            ['id' => 1, 'groupName' => 'Matematika csoport', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 2, 'groupName' => 'Nyelvi csoport', 'created_at' => now(), 'updated_at' => now()],
        ]);
    }
}