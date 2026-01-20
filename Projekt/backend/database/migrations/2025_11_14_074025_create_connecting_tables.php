<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{

    public function up(): void
    {

        Schema::create('ingredient_allergens', function (Blueprint $table) {
            $table->foreignId('allergen_id')->constrained('allergens')->onDelete('cascade');
            $table->foreignId('ingredient_id')->constrained('ingredients')->onDelete('cascade');
            $table->primary(['ingredient_id', 'allergen_id']);
            
            $table->unique(['ingredient_id', 'allergen_id']);
        });


        Schema::create('meal_ingredients', function (Blueprint $table) {
            $table->foreignId('meal_id')->constrained('meals');
            $table->foreignId('ingredient_id')->constrained('ingredients');
            $table->decimal('amount', 8, 2);
            $table->string('unit', 30);
            $table->primary(['meal_id', 'ingredient_id']);
        });

        Schema::create('userHealthRestrictions', function (Blueprint $table) {
            $table->foreignId('user_id')->constrained('users');
            $table->foreignId('allergen_id')->nullable()->constrained('allergens');
            $table->boolean('hasDiabetes')->default(false);
            $table->primary(['user_id']);
    });
    }


    public function down(): void
    {
        Schema::dropIfExists('connecting_tables');
    }
};
