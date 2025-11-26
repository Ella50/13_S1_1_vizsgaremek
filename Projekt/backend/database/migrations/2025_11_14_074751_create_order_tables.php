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

    Schema::create('price', function (Blueprint $table) {
        $table->id();
        $table->enum('status', array_column(UserType::cases(), 'value'))->default('Tanuló');
        $table->enum('priceCategory', ['Normál', 'Kedvezményes']);
        $table->decimal('amount', 10, 2);
        $table->date('validFrom');
        $table->date('validTo')->nullable();
        $table->timestamps();
    });



    Schema::create('invoice', function (Blueprint $table) {
        $table->id();
        $table->foreignId('user_id')->constrained('user');
        $table->string('invoiceNumber')->unique();
        $table->date('billingMonth');
        $table->date('issueDate');
        $table->date('dueDate');
        $table->decimal('totalAmount', 10, 2);
        $table->enum('paymentMethod', array_column(PaymentMethod::cases(), 'value'))->default('Banki utalás');;
        $table->enum('status', array_column(InvoiceStatus::cases(), 'value'))->default('Generálva');
        $table->string('transactionId')->nullable();
        $table->timestamp('paidAt')->nullable();
        $table->timestamps();
    });


    Schema::create('order', function (Blueprint $table) {
        $table->id();
        $table->foreignId('user_id')->constrained('user');
        $table->foreignId('menuItems_id')->constrained('menuItem');
        $table->date('orderDate');
        $table->enum('selectedOption', ['A', 'B']);
        $table->enum('status', array_column(OrderStatus::cases(), 'value'))->default('Rendelve');
        $table->foreignId('invoice_id')->nullable()->constrained('invoice');
        $table->foreignId('price_id')->constrained('price');
        $table->timestamp('cancelledAt')->nullable();
        $table->timestamps();
    });


    Schema::create('rating', function (Blueprint $table) {
        $table->id();
        $table->foreignId('user_id')->constrained('user');
        $table->foreignId('order_id')->constrained('order');
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
