<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('originalName');
            $table->string('fileName');
            $table->string('filePath');
            $table->string('mimeType'); // Pl. 'application/pdf'
            $table->integer('fileSize'); // Fájlméret byte-ban
            $table->enum('type', ['discount', 'diabetes'])->nullable();
            $table->timestamps();
    });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('documents', function (Blueprint $table) {
            
        });
    }
};
