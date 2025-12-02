<?php

use App\Enums\InvoiceStatus;
use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\UserType;
use App\Enums\SelectedOption;


use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{

    public function up(): void
    {  

    Schema::create('prices', function (Blueprint $table) {
        $table->id();
        //$table->enum('status', array_column(UserType::cases(), 'value'))->default('Tanuló');
        $table->enum('userType', ['Tanuló', 'Külsős', 'Tanár', 'Dolgozó', 'Admin', 'Konyha'])->default('Tanuló');
        $table->enum('priceCategory', ['Normál', 'Kedvezményes'])->default('Normál');
        $table->decimal('amount', 10, 2);
        $table->date('validFrom');
        $table->date('validTo')->nullable();
    });



    Schema::create('invoices', function (Blueprint $table) {
        $table->id();
        $table->foreignId('user_id')->constrained('users');
        $table->string('invoiceNumber')->unique();
        $table->date('billingMonth');
        $table->date('issueDate');
        $table->date('dueDate');
        $table->decimal('totalAmount', 10, 2);
        //$table->enum('paymentMethod', array_column(PaymentMethod::cases(), 'value'))->default('Banki utalás');;
        $table->enum('paymentMethod', ['Banki utalás', 'Bankkártya', 'Készpénz'])->default('Bankkártya');
        //$table->enum('status', array_column(InvoiceStatus::cases(), 'value'))->default('Generálva');
        $table->enum('invoiceStatus', ['Függőben lévő', 'Generálva', 'Fizetve', 'Lejárt'])->default('Generálva');

        $table->string('transactionId')->nullable();
        $table->timestamp('paidAt')->nullable();
        $table->timestamps();
    });


    Schema::create('orders', function (Blueprint $table) {
        $table->id();
        $table->foreignId('user_id')->constrained('users');
        $table->foreignId('menuItems_id')->constrained('menuItems');
        $table->date('orderDate');
        $table->enum('selectedOption', ['A', 'B']);
        //$table->enum('status', array_column(OrderStatus::cases(), 'value'))->default('Rendelve');
        $table->enum('orderStatus', ['Rendelve', 'Lemondva', 'Fizetve'])->default('Rendelve');
        $table->foreignId('invoice_id')->nullable()->constrained('invoices');
        $table->foreignId('price_id')->constrained('prices');
        $table->timestamp('cancelledAt')->nullable();
        $table->timestamps();
    });


    Schema::create('ratings', function (Blueprint $table) {
        $table->id();
        $table->foreignId('user_id')->constrained('users');
        $table->foreignId('order_id')->constrained('orders');
        $table->smallInteger('rating');
        $table->text('comment')->nullable();
        $table->unique(['user_id', 'order_id']);
        $table->timestamps();
    });



    }

    public function down(): void
    {
        Schema::dropIfExists('order_tables');
    }
};
