<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

class UserTableSeeder extends Seeder
{
    public function run()
    {
        $users = [
            [
                'firstName' => 'Admin',
                'lastName' => 'Admin',
                'thirdName' => null,
                'city_id' => 1,
                'address' => 'Admin utca 1.',
                'email' => 'admin@iskola.hu',
                'password' => Hash::make('admin123'),
                'userType' => 'Admin',
                'rfidCard_id' => null,
                'class_id' => null,
                'group_id' => null,
                'status' => 'active',
                'hasDiscount' => false,
                'created_at' => now(),
                'updated_at' => now()
            ]
        ];

        // Diákok
        $diakok = [
            ['Kovács', 'Bence', 'Gábor'],
            ['Nagy', 'Anna', 'Mária'],
            ['Tóth', 'Dávid', null],
            ['Szabó', 'Eszter', 'Katalin'],
            ['Varga', 'Márk', 'Péter'],
        ];

        foreach ($diakok as $index => $diak) {
            $users[] = [
                'firstName' => $diak[1],
                'lastName' => $diak[0],
                'thirdName' => $diak[2],
                'city_id' => rand(1, 2),
                'address' => $this->generateRandomAddress(),
                'email' => strtolower($diak[1] . '.' . $diak[0] . '@iskola.hu'),
                'password' => Hash::make('diak123'),
                'userType' => 'Tanuló',
                'rfidCard_id' => $index + 1, 
                'class_id' => rand(1, 2), 
                'group_id' => rand(1, 2), 
                'status' => 'active',
                'hasDiscount' => (bool)rand(0, 1),
                'created_at' => now(),
                'updated_at' => now()
            ];
        }

        // Tanárok
        $tanarok = [
            ['Kiss', 'István', null],
            ['Németh', 'Erika', null],
        ];

        foreach ($tanarok as $index => $tanar) {
            $users[] = [
                'firstName' => $tanar[1],
                'lastName' => $tanar[0],
                'thirdName' => $tanar[2],
                'city_id' => rand(1, 2),
                'address' => $this->generateRandomAddress(),
                'email' => strtolower($tanar[1] . '.' . $tanar[0] . '@iskola.hu'),
                'password' => Hash::make('tanar123'),
                'userType' => 'Tanár',
                'rfidCard_id' => null,
                'class_id' => null,
                'group_id' => rand(1, 2),
                'status' => 'active',
                'hasDiscount' => false,
                'created_at' => now(),
                'updated_at' => now()
            ];
        }

        // Beszúrás
        foreach ($users as $user) {
            User::create($user);
        }

        $this->command->info(count($users) . ' felhasználó sikeresen létrehozva!');
    }

    private function generateRandomAddress()
    {
        $utcak = ['Kossuth', 'Petőfi', 'Rákóczi', 'Ady Endre', 'Béke', 'Szabadság'];
        return $utcak[array_rand($utcak)] . ' ' . rand(1, 5) . '. utca';
    }
}