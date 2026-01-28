<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{

    public function up(): void
    {

        Schema::create('counties', function (Blueprint $table) {
            $table->id();
            $table->string('countyName');
        });


        Schema::create('cities', function (Blueprint $table) {
            $table->id();
            $table->string('cityName');
            $table->string('zipCode');
            $table->foreignId('county_id')->constrained('counties');
        });


        Schema::create('classes', function (Blueprint $table) {
            $table->id();
            $table->string('className')->unique();
            $table->timestamps();
        });

 
        Schema::create('groups', function (Blueprint $table) {
            $table->id();
            $table->string('groupName')->unique();
            $table->timestamps();
        });


        Schema::create('rfidCards', function (Blueprint $table) {
            $table->id();
            $table->string('cardNumber')->unique();
            $table->timestamp('lastUsedAt')->nullable();
            $table->boolean('isActive')->default(true);
            $table->timestamps();
        });


        Schema::create('allergens', function (Blueprint $table) {
            $table->id();
            $table->string('allergenName')->unique();
            $table->string('icon')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('base_tables');
        Schema::table('rfidCards', function (Blueprint $table) {
            $table->dropColumn('cardNumber');
        });
    }
};

