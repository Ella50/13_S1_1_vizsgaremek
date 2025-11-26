<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{

    public function up(): void
    {

        Schema::create('ingredient_allergen', function (Blueprint $table) {
            $table->foreignId('allergen_id')->constrained('allergen');
            $table->foreignId('ingredient_id')->constrained('ingredient');
            $table->primary(['ingredient_id', 'allergen_id']);
        });


        Schema::create('meal_ingredient', function (Blueprint $table) {
            $table->foreignId('meal_id')->constrained('meal');
            $table->foreignId('ingredient_id')->constrained('ingredient');
            $table->decimal('amount', 8, 2);
            $table->string('unit', 30);
            $table->primary(['meal_id', 'ingredient_id']);
        });

        Schema::create('userHealthRestriction', function (Blueprint $table) {
            $table->foreignId('user_id')->constrained('user');
            $table->foreignId('allergen_id')->nullable()->constrained('allergen');
            $table->boolean('hasDiabetes')->default(false);
            $table->primary(['user_id']);
    });
    }


    public function down(): void
    {
        Schema::dropIfExists('connecting_tables');
    }
};
