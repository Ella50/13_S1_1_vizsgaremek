<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Invoice;
use App\Models\User;
use App\Enums\InvoiceStatus;
use App\Enums\PaymentMethod;



class InvoiceTableSeeder extends Seeder
{
    public function run(): void
    {
        // Ellenőrizzük, hogy vannak-e userek
        $users = User::all();
        
        if ($users->isEmpty()) {
            // Ha nincsenek userek, hozzunk létre
            $this->call(UserSeeder::class);
            $users = User::all();
        }

        // 1. Speciális számlák konkrét userekhez
        Invoice::factory()->create([
            'user_id' => $users->first()->id,
            'invoiceNumber' => 'INV-2024-001',
            'billingMonth' => '2024-11-01',
            'issueDate' => '2024-11-01',
            'dueDate' => '2024-12-01',
            'totalAmount' => 12500.00,
            'paymentMethod' => PaymentMethod::UTALAS->value,
            'status' => InvoiceStatus::PAID->value,
            'transactionId' => 'txn_7c6b5a4d3e2f1',
            'paidAt' => '2024-11-05 14:30:00',
        ]);

        Invoice::factory()->create([
            'user_id' => $users->first()->id,
            'invoiceNumber' => 'INV-2024-002',
            'billingMonth' => '2024-12-01',
            'issueDate' => '2024-12-01',
            'dueDate' => '2025-01-01',
            'totalAmount' => 13400.00,
            'paymentMethod' => PaymentMethod::KARTYA->value,
            'status' => InvoiceStatus::PENDING->value,
            'transactionId' => null,
            'paidAt' => null,
        ]);

        // 2. Véletlenszerű számlák minden userhez
        foreach ($users as $user) {
            // Minden user kap 1-4 számlát
            $invoiceCount = rand(1, 4);
            
            Invoice::factory()->count($invoiceCount)->create([
                'user_id' => $user->id,
            ]);
        }

        // 3. Speciális állapotú számlák
        Invoice::factory()->count(3)->create();
        Invoice::factory()->count(5)->create();
        Invoice::factory()->count(2)->create();
        Invoice::factory()->count(2)->create();

        // Összesen kb. 20-30 számla
        $remainingCount = 30 - Invoice::count();
        if ($remainingCount > 0) {
            Invoice::factory()->count($remainingCount)->create();
        }
    }
}