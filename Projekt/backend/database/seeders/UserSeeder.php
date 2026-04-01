<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        // ADMIN
        User::create([
            'firstName' => 'Felhasználó',
            'lastName' => 'Admin',
            'email' => 'admin@iskola.hu',
            'password' => Hash::make('admin123'),
            'userType' => 'Admin',
            'userStatus' => 'Aktív',
            'city_id' => 7,
            'address' => 'Admin utca 1.',
            'thirdName' => null,
            'rfidCard_id' => null,
            'hasDiscount' => false,
            'hasDiabetes' => false
        ]);

        // TANÁR
        User::create([
            'firstName' => 'János',
            'lastName' => 'Kovács',
            'email' => 'kovacs.janos@iskola.hu',
            'password' => Hash::make('tanar123'),
            'userType' => 'Tanár',
            'userStatus' => 'Aktív',
            'city_id' => 4,
            'address' => 'Tanár utca 2.',
            'thirdName' => null,
            'rfidCard_id' => null,
            'hasDiscount' => false,
            'hasDiabetes' => false
        ]);

        User::create([
            'firstName' => 'Anna',
            'lastName' => 'Nagy',
            'email' => 'nagy.anna@iskola.hu',
            'password' => Hash::make('tanar123'),
            'userType' => 'Tanár',
            'userStatus' => 'Aktív',
            'city_id' => 3,
            'address' => 'Tanár utca 3.',
            'thirdName' => null,
            'rfidCard_id' => null,
            'hasDiscount' => false,
            'hasDiabetes' => false
        ]);

        // TANULÓ
        User::create([
            'firstName' => 'Bence',
            'lastName' => 'Tóth',
            'email' => 'toth.bence@iskola.hu',
            'password' => Hash::make('tanulo123'),
            'userType' => 'Tanuló',
            'userStatus' => 'Aktív',
            'city_id' => 2,
            'address' => 'Tanuló utca 4.',
            'thirdName' => null,
            'rfidCard_id' => null,
            'hasDiscount' => false,
            'hasDiabetes' => false
        ]);

        User::create([
            'firstName' => 'Erika',
            'lastName' => 'Kiss',
            'email' => 'kiss.erika@iskola.hu',
            'password' => Hash::make('tanulo123'),
            'userType' => 'Tanuló',
            'userStatus' => 'inAktív',
            'city_id' => 2,
            'address' => 'Tanuló utca 5.',
            'thirdName' => null,
            'rfidCard_id' => null,
            'hasDiscount' => true,
            'hasDiabetes' => false
        ]);

        // DOLGOZÓ
        User::create([
            'firstName' => 'Gábor',
            'lastName' => 'Szabó',
            'email' => 'szabo.gabor@iskola.hu',
            'password' => Hash::make('dolgozo123'),
            'userType' => 'Dolgozó',
            'userStatus' => 'Aktív',
            'city_id' => 8,
            'address' => 'Dolgozó utca 6.',
            'thirdName' => null,
            'rfidCard_id' => null,
            'hasDiscount' => false,
            'hasDiabetes' => false
        ]);



        // KONYHA
        User::create([
            'firstName' => 'Ilona',
            'lastName' => 'Farkas',
            'email' => 'farkas.ilona@iskola.hu',
            'password' => Hash::make('konyha123'),
            'userType' => 'Konyha',
            'userStatus' => 'Aktív',
            'city_id' => 5,
            'address' => 'Konyha utca 8.',
            'thirdName' => null,
            'rfidCard_id' => null,
            'hasDiscount' => false,
            'hasDiabetes' => false
        ]);


        echo "Teszt felhasználók sikeresen létrehozva\n";
        echo "==========================\n";
        echo "Admin:\n";
        echo "Email: admin@iskola.hu\n";
        echo "Jelszó: admin123\n";
        echo "==========================\n";
        echo "Tanár:\n";
        echo "Email: kovacs.janos@iskola.hu\n";
        echo "Jelszó: tanar123\n";
        echo "==========================\n";
        echo "Konyha:\n";
        echo "Email: farkas.ilona@iskola.hu\n";
        echo "Jelszó: konyha123\n";
        echo "==========================\n";
    }
}