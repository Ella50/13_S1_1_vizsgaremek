<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Enums\MealType;
use App\Enums\IngredientType;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('meal', function (Blueprint $table) {
            $table->id();
            $table->string('mealName');
            $table->enum('mealType', array_column(MealType::cases(), 'value'));
            $table->text('description')->nullable();
            $table->string('picture', 1024)->nullable();
            $table->timestamps();
        });

        Schema::create('ingredient', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->enum('type', array_column(IngredientType::cases(), 'value'))->nullable();
            $table->integer('energy')->nullable();
            $table->integer('protein')->nullable();
            $table->integer('carbohydrate')->nullable();
            $table->integer('fat')->nullable();
            $table->integer('sodium')->nullable();
            $table->integer('sugar')->nullable();
            $table->integer('fiber')->nullable();
            $table->boolean('isAvailable')->default(true);
            $table->timestamps();
        });

        Schema::create('allergen', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('icon')->nullable();
            $table->timestamps();
        });

        // Köztes táblák
        Schema::create('ingredient_allergen', function (Blueprint $table) {
            $table->foreignId('allergen_id')->constrained('allergen');
            $table->foreignId('ingredient_id')->constrained('ingredient'); 
            $table->primary(['ingredient_id', 'allergen_id']);
        });

        Schema::create('meal_ingredient', function (Blueprint $table) {
            $table->foreignId('meal_id')->constrained("meal")->onDelete('cascade');
            $table->foreignId('ingredient_id')->constrained("ingredient")->onDelete('cascade');
            $table->decimal('amount', 8, 2);
            $table->string('unit', 30);
            $table->primary(['meal_id', 'ingredient_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('meal_ingredient');
        Schema::dropIfExists('ingredient_allergen');
        Schema::dropIfExists('allergen');
        Schema::dropIfExists('ingredient');
        Schema::dropIfExists('meal');
    }
};