<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\Allergen;

class AllergenSeeder extends Seeder
{
    public function run()
    {

        $allergens = [
            [
                'name' => 'Glutén',
                'icon' => 'allergens/gluten.png', //útvonal a storage/app/public/allergens/ mappa
            ],
            [
                'name' => 'Rákfélék',
                'icon' => 'allergens/rakfelek.png',
            ],
            [
                'name' => 'Tojás',
                'icon' => 'allergens/tojas.png',
            ],
            [
                'name' => 'Hal',
                'icon' => 'allergens/hal.png',
            ],
            [
                'name' => 'Földimogyoró',
                'icon' => 'allergens/foldiMogyoro.png',
            ],
            [
                'name' => 'Szójabab',
                'icon' => 'allergens/szoja.png',
            ],
            [
                'name' => 'Tej',
                'icon' => 'allergens/tej.png',
            ],
            [
                'name' => 'Diófélék',
                'icon' => 'allergens/diofelek.png',
            ],
            [
                'name' => 'Zeller',
                'icon' => 'allergens/celery.png',
            ],
            [
                'name' => 'Mustár',
                'icon' => 'allergens/mustar.png',
            ],

            [
                'name' => 'Kukorica',
                'icon' => 'allergens/kukorica.png',
            ],
        ];

        foreach ($allergens as $allergen) {
            Allergen::create($allergen);
        }

        $this->command->info('Allergének sikeresen létrehozva!');
    }
}