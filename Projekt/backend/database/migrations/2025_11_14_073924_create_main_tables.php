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

    Schema::create('meals', function (Blueprint $table) {
        $table->id();
        $table->string('mealName');
        $table->enum('mealType', ['Leves', 'Főétel', 'Egyéb']);
        $table->text('description')->nullable();
        $table->string('picture', 1024)->nullable();
        $table->timestamps();
    });

    Schema::create('menuItems', function (Blueprint $table) {
        $table->id();
        $table->foreignId('soup')->constrained('meals');
        $table->foreignId('optionA')->constrained('meals');
        $table->foreignId('optionB')->constrained('meals');
        $table->date('day');
        $table->timestamps();
    });


    Schema::create('ingredients', function (Blueprint $table) {
        $table->id();
        $table->string('name')->unique();
        //$table->enum('type', array_column(IngredientType::cases(), 'value'))->default('Egyéb');
        $table->enum('ingredientType', ['Egyéb', 'Hús', 'Tejtermék', 'Zöldség', 'Gyümölcs', 'Fűszer'])->default('Egyéb');
        $table->integer('energy')->nullable();
        $table->integer('protein')->nullable();
        $table->integer('carbohydrate')->nullable();
        $table->integer('fat')->nullable();
        $table->integer('sodium')->nullable();
        $table->integer('sugar')->nullable();
        $table->integer('fiber')->nullable();
        $table->boolean('isAvailable')->default(true);
    });


    Schema::create('users', function (Blueprint $table) {
        $table->id();
        $table->string('firstName');
        $table->string('lastName');
        $table->string('thirdName')->nullable();
        $table->foreignId('city_id')->nullable()->constrained('cities');
        $table->string('address')->nullable();
        $table->string('email')->unique();
        $table->string('password');
        //$table->enum('userType', array_column(UserType::cases(), 'value'))->default('Tanuló');
        $table->enum('userType', ['Tanuló', 'Külsős', 'Tanár', 'Dolgozó', 'Admin', 'Konyha'])->default('Tanuló');
        $table->foreignId('rfidCard_id')->nullable()->constrained('rfidCards'); 
        $table->foreignId('class_id')->nullable()->constrained('classes');
        $table->foreignId('group_id')->nullable()->constrained('groups'); 
        //$table->enum('status', array_column(UserStatus::cases(), 'value'))->default('inactive');
        $table->enum('userStatus', ['inactive', 'active', 'suspended'])->default('inactive');
        $table->boolean('hasDiscount')->default(false);
        $table->timestamps();

    });

    Schema::create('schedules', function (Blueprint $table) {
        $table->id();
        $table->foreignId('class_id')->constrained('classes');
        $table->foreignId('group_id')->nullable()->constrained('groups');
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
