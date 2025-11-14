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
            $table->foreignId('county_id')->constrained("county")->onDelete('cascade');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('city');
        Schema::dropIfExists('county');
    }
};