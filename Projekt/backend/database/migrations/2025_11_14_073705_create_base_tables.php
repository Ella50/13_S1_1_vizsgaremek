<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{

    public function up(): void
    {

        Schema::create('county', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->timestamps();
        });


        Schema::create('city', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->integer('zipCode');
            $table->foreignId('county_id')->constrained('county');
            $table->timestamps();
        });


        Schema::create('class', function (Blueprint $table) {
            $table->id();
            $table->string('className')->unique();
            $table->timestamps();
        });

 
        Schema::create('group', function (Blueprint $table) {
            $table->id();
            $table->string('groupName')->unique();
            $table->timestamps();
        });


        Schema::create('rfidCard', function (Blueprint $table) {
            $table->id();
            $table->integer('cardNumber')->unique();
            $table->timestamp('lastUsedAt')->nullable();
            $table->boolean('isActive')->default(true);
            $table->timestamps();
        });


        Schema::create('allergen', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('icon')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('base_tables');
    }
};

