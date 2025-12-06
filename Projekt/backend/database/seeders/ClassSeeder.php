<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ClassSeeder extends Seeder
{
    public function run()
    {
        DB::table('classes')->insert([
            ['className' => '9.A', 'created_at' => now(), 'updated_at' => now()],
            ['className' => '9.B', 'created_at' => now(), 'updated_at' => now()],
            ['className' => '10.D', 'created_at' => now(), 'updated_at' => now()],
            
        ]);
    }
}



/*

use Illuminate\Database\Seeder;
use App\Models\ClassModel;

class ClassSeeder extends Seeder
{
    public function run(): void
    {
        ClassModel::create(['name' => '9.A']);
        ClassModel::create(['name' => '9.B']);
        ClassModel::create(['name' => '10.A']);
        ClassModel::create(['name' => '10.B']);
        ClassModel::create(['name' => '11.A']);
        ClassModel::create(['name' => '11.B']);
        ClassModel::create(['name' => '12.A']);
        ClassModel::create(['name' => '12.B']);
    }
}
*/