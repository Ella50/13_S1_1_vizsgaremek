<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
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

        Schema::create('schedule', function (Blueprint $table) {
            $table->id();
            $table->foreignId('class_id')->constrained("class")->onDelete('cascade');
            $table->foreignId('group_id')->nullable()->constrained("group")->onDelete('cascade');
            $table->date('from');
            $table->date('to');
            $table->timestamps();
        });

        Schema::create('rfidCard', function (Blueprint $table) {
            $table->id();
            $table->integer('cardNumber')->unique();
            $table->timestamp('lastUsedAt')->nullable();
            $table->boolean('isActive')->default(true);
            $table->timestamps();
        });

        Schema::create('userHealthRestriction', function (Blueprint $table) {
            $table->foreignId('user_id')->constrained("user")->onDelete('cascade');
            $table->foreignId('allergen_id')->nullable()->constrained("allergen")->onDelete('cascade');
            $table->boolean('hasDiabetes')->default(false);
            $table->primary(['user_id', 'allergen_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('userHealthRestriction');
        Schema::dropIfExists('rfidCard');
        Schema::dropIfExists('schedule');
        Schema::dropIfExists('group');
        Schema::dropIfExists('class');
    }
};