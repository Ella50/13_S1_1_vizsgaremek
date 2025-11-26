<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\Invoice;
use App\Models\User;

class InvoiceFactory extends Factory
{
    protected $model = Invoice::class;

    public function definition(): array
    {
        $issueDate = $this->faker->dateTimeBetween('-6 months', 'now');
        $dueDate = clone $issueDate;
        $dueDate->modify('+30 days');
        $billingMonth = $this->faker->dateTimeBetween('-1 year', 'now');

        return [
            'user_id' => User::factory(),
            'invoiceNumber' => 'INV-' . $this->faker->unique()->numberBetween(1000, 9999),
            'billingMonth' => $billingMonth->format('Y-m-01'), // Hónap első napja
            'issueDate' => $issueDate,
            'dueDate' => $dueDate,
            'totalAmount' => $this->faker->randomFloat(2, 5000, 50000),
            'paymentMethod' => $this->faker->randomElement(['Banki utalás', 'Bankkártya', 'Készpénz']),
            'status' => $this->faker->randomElement(['Függőben lévő', 'Generálva', 'Fizetve', 'Lejárt']),
            'transactionId' => $this->faker->optional(0.7)->uuid(),
            'paidAt' => $this->faker->optional(0.6)->dateTimeBetween('-3 months', 'now'),
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }

    /*
     
    public function paid(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'paid',
            'paidAt' => $this->faker->dateTimeBetween('-2 months', 'now'),
            'transactionId' => $this->faker->uuid(),
        ]);
    }


    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'pending',
            'paidAt' => null,
            'transactionId' => null,
        ]);
    }


    public function overdue(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'overdue',
            'paidAt' => null,
            'dueDate' => $this->faker->dateTimeBetween('-60 days', '-1 day'),
        ]);
    }


    public function bankTransfer(): static
    {
        return $this->state(fn (array $attributes) => [
            'paymentMethod' => 'bank_transfer',
        ]);
    }


    public function creditCard(): static
    {
        return $this->state(fn (array $attributes) => [
            'paymentMethod' => 'credit_card',
        ]);
    }*/
}