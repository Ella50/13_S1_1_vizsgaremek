<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

use App\Enums\UserType;
use App\Enums\UserStatus;

use App\Enums\MealType;
use App\Enums\IngredientType;





return new class extends Migration
{

    public function up(): void
    {

    Schema::create('meal', function (Blueprint $table) {
        $table->id();
        $table->string('mealName');
        $table->enum('mealType', ['Leves', 'Főétel', 'Bónusz']);
        $table->text('description')->nullable();
        $table->string('picture', 1024)->nullable();
        $table->timestamps();
    });

    Schema::create('menuItem', function (Blueprint $table) {
        $table->id();
        $table->foreignId('soup')->constrained('meal');
        $table->foreignId('optionA')->constrained('meal');
        $table->foreignId('optionB')->constrained('meal');
        $table->date('day');
        $table->timestamps();
    });


    Schema::create('ingredient', function (Blueprint $table) {
        $table->id();
        $table->string('name')->unique();
        $table->enum('type', array_column(IngredientType::cases(), 'value'))->default('Egyéb');
        $table->integer('energy')->nullable();
        $table->integer('protein')->nullable();
        $table->integer('carbohydrate')->nullable();
        $table->integer('fat')->nullable();
        $table->integer('sodium')->nullable();
        $table->integer('sugar')->nullable();
        $table->integer('fiber')->nullable();
        $table->boolean('isAvailable')->default(true);
    });


    Schema::create('user', function (Blueprint $table) {
        $table->id();
        $table->string('firstName');
        $table->string('lastName');
        $table->string('thirdName')->nullable();
        $table->foreignId('city_id')->constrained('city');
        $table->string('address');
        $table->string('email')->unique();
        $table->string('password');
        $table->enum('userType', array_column(UserType::cases(), 'value'))->default('Tanuló');
        $table->foreignId('rfidCard_id')->nullable()->constrained('rfidCard'); 
        $table->foreignId('class_id')->nullable()->constrained('class');
        $table->foreignId('group_id')->nullable()->constrained('group'); 
        $table->enum('status', array_column(UserStatus::cases(), 'value'))->default('inactive');
        $table->boolean('hasDiscount')->default(false);
        $table->timestamps();

    });

    Schema::create('schedule', function (Blueprint $table) {
        $table->id();
        $table->foreignId('class_id')->constrained('class');
        $table->foreignId('group_id')->nullable()->constrained('group');
        $table->date('from');
        $table->date('to');
        $table->timestamps();
    });

    }


    public function down(): void
    {
        Schema::dropIfExists('main_tables');
    }
};
