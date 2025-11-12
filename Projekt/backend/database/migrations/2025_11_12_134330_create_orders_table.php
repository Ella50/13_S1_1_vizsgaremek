<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Enums\OrderStatus;
use App\Enums\Option;
use App\Enums\InvoiceStatus;
use App\Enums\PaymentStatus;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('invoice', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained("user")->onDelete('cascade');
            $table->string('invoiceNumber')->unique();
            $table->date('billingMonth');
            $table->date('issueDate');
            $table->date('dueDate');
            $table->decimal('totalAmount', 10, 2);
            $table->enum('status', array_column(InvoiceStatus::cases(), 'value'))->default('Generálva');
            $table->string('paymentMethod')->nullable();
            $table->enum('paymentStatus', array_column(PaymentStatus::cases(), 'value'))->default('Folyamatban');
            $table->string('transactionId')->nullable();
            $table->timestamp('paidAt')->nullable();
            $table->timestamps();
        });

        Schema::create('price', function (Blueprint $table) {
            $table->id();
            $table->enum('userType', array_column(\App\Enums\UserType::cases(), 'value'));
            $table->enum('priceCategory', array_column(\App\Enums\PriceCategory::cases(), 'value'));
            $table->decimal('amount', 10, 2);
            $table->date('validFrom');
            $table->date('validTo')->nullable();
            $table->timestamps();
        });

        Schema::create('menuItems', function (Blueprint $table) {
            $table->id();
            $table->foreignId('soup')->constrained('meal');
            $table->foreignId('optionA')->constrained('meal');
            $table->foreignId('optionB')->constrained('meal');
            $table->date('day');
            $table->timestamps();
        });

        Schema::create('order', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('menuItems_id')->constrained('menuItems');
            $table->date('orderDate');
            $table->enum('selectedOption', array_column(Option::cases(), 'value'));
            $table->enum('status', array_column(OrderStatus::cases(), 'value'))->default('Rendelve');
            $table->foreignId('invoice_id')->nullable()->constrained("invoice");
            $table->foreignId('price_id')->constrained("price");
            $table->timestamp('cancelledAt')->nullable();
            $table->timestamps();
        });

        Schema::create('rating', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained("user")->onDelete('cascade');
            $table->foreignId('order_id')->constrained("order")->onDelete('cascade');
            $table->smallInteger('rating');
            $table->text('comment')->nullable();
            $table->unique(['user_id', 'order_id']);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rating');
        Schema::dropIfExists('order');
        Schema::dropIfExists('menuItems');
        Schema::dropIfExists('price');
        Schema::dropIfExists('invoice');
    }
};