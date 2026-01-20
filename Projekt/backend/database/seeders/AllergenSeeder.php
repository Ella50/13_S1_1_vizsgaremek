<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;


class AllergenSeeder extends Seeder
{
    public function run()
    {

         DB::table('allergens')->insert([
            [
                'allergenName' => 'Glutén',
                'icon' => 'allergens/gluten.png', //útvonal a storage/app/public/allergens/ mappa
            ],
            [
                'allergenName' => 'Rákfélék',
                'icon' => 'allergens/rakfelek.png',
            ],
            [
                'allergenName' => 'Tojás',
                'icon' => 'allergens/tojas.png',
            ],
            [
                'allergenName' => 'Hal',
                'icon' => 'allergens/hal.png',
            ],
            [
                'allergenName' => 'Földimogyoró',
                'icon' => 'allergens/foldiMogyoro.png',
            ],
            [
                'allergenName' => 'Szójabab',
                'icon' => 'allergens/szoja.png',
            ],
            [
                'allergenName' => 'Tej',
                'icon' => 'allergens/tej.png',
            ],
            [
                'allergenName' => 'Diófélék',
                'icon' => 'allergens/diofelek.png',
            ],
            [
                'allergenName' => 'Zeller',
                'icon' => 'allergens/celery.png',
            ],
            [
                'allergenName' => 'Mustár',
                'icon' => 'allergens/mustar.png',
            ],

            [
                'allergenName' => 'Kukorica',
                'icon' => 'allergens/kukorica.png',
            ],
        ]);

        /*foreach ($allergens as $allergen) {
            Allergen::create($allergen);
        }*/

        $this->command->info('Allergének sikeresen létrehozva');
    }
}