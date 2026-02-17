<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\UserHealthRestriction;
use Illuminate\Support\Facades\DB;


class UserHealthSeeder extends Seeder
{
    public function run()
    {

         DB::table('userhealthrestrictions')->insert([
            [
                'user_id' => '2',
                'allergen_id' => '1',
            ],
            [
                'user_id' => '2',
                'allergen_id' => '4',
            ],

        ]);


        $this->command->info('UserAllergének sikeresen létrehozva');
    }
}