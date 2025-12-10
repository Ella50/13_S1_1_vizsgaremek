<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        // ADMIN felhasználó
        User::create([
            'firstName' => 'Admin',
            'lastName' => 'Felhasználó',
            'email' => 'admin@iskola.hu',
            'password' => Hash::make('admin123'),
            'userType' => 'Admin',
            'userStatus' => 'active',
            'city_id' => null,
            'address' => 'Admin utca 1.',
            'thirdName' => null,
            'rfidCard_id' => null,
            'class_id' => null,
            'group_id' => null,
            'hasDiscount' => false,
        ]);

        // TANÁR felhasználók
        User::create([
            'firstName' => 'Kovács',
            'lastName' => 'János',
            'email' => 'kovacs.janos@iskola.hu',
            'password' => Hash::make('tanar123'),
            'userType' => 'Tanár',
            'userStatus' => 'active',
            'city_id' => null,
            'address' => 'Tanár utca 2.',
            'thirdName' => null,
            'rfidCard_id' => null,
            'class_id' => null,
            'group_id' => null,
            'hasDiscount' => false,
        ]);

        User::create([
            'firstName' => 'Nagy',
            'lastName' => 'Anna',
            'email' => 'nagy.anna@iskola.hu',
            'password' => Hash::make('tanar456'),
            'userType' => 'Tanár',
            'userStatus' => 'active',
            'city_id' => null,
            'address' => 'Tanár utca 3.',
            'thirdName' => null,
            'rfidCard_id' => null,
            'class_id' => null,
            'group_id' => null,
            'hasDiscount' => false,
        ]);

        // TANULÓ felhasználók
        User::create([
            'firstName' => 'Tóth',
            'lastName' => 'Bence',
            'email' => 'toth.bence@iskola.hu',
            'password' => Hash::make('tanulo123'),
            'userType' => 'Tanuló',
            'userStatus' => 'active',
            'city_id' => null,
            'address' => 'Tanuló utca 4.',
            'thirdName' => null,
            'rfidCard_id' => null,
            'class_id' => 1,
            'group_id' => 1,
            'hasDiscount' => false,
        ]);

        User::create([
            'firstName' => 'Kiss',
            'lastName' => 'Erika',
            'email' => 'kiss.erika@eiskola.hu',
            'password' => Hash::make('tanulo456'),
            'userType' => 'Tanuló',
            'userStatus' => 'inactive', // Inaktív - adminnak kell aktiválnia
            'city_id' => null,
            'address' => 'Tanuló utca 5.',
            'thirdName' => null,
            'rfidCard_id' => null,
            'class_id' => 2,
            'group_id' => 1,
            'hasDiscount' => true,
        ]);

        // DOLGOZÓ felhasználó
        User::create([
            'firstName' => 'Szabó',
            'lastName' => 'Gábor',
            'email' => 'szabo.gabor@iskola.hu',
            'password' => Hash::make('dolgozo123'),
            'userType' => 'Dolgozó',
            'userStatus' => 'active',
            'city_id' => null,
            'address' => 'Dolgozó utca 6.',
            'thirdName' => null,
            'rfidCard_id' => null,
            'class_id' => null,
            'group_id' => null,
            'hasDiscount' => false,
        ]);


        // KONYHA dolgozó
        User::create([
            'firstName' => 'Farkas',
            'lastName' => 'Ilona',
            'email' => 'farkas.ilona@iskola.hu',
            'password' => Hash::make('konyha123'),
            'userType' => 'Konyha',
            'userStatus' => 'active',
            'city_id' => null,
            'address' => 'Konyha utca 8.',
            'thirdName' => null,
            'rfidCard_id' => null,
            'class_id' => null,
            'group_id' => null,
            'hasDiscount' => false,
        ]);

        echo "Teszt felhasználók sikeresen létrehozva!\n";
        echo "==========================\n";
        echo "Admin belépés:\n";
        echo "Email: admin@iskola.hu\n";
        echo "Jelszó: admin123\n";
        echo "==========================\n";
        echo "Tanár belépés:\n";
        echo "Email: kovacs.janos@iskola.hu\n";
        echo "Jelszó: tanar123\n";
        echo "==========================\n";
        echo "Tanuló belépés:\n";
        echo "Email: toth.bence@iskola.hu\n";
        echo "Jelszó: tanulo123\n";
        echo "==========================\n";
    }
}