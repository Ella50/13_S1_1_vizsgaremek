<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

use App\Enums\UserType;
use App\Enums\UserStatus;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('user', function (Blueprint $table) {
            $table->id();
            $table->string('firstName');
            $table->string('lastName');
            $table->string('thirdName')->nullable();
            $table->foreignId('city_id')->constrained("city")->onDelete('cascade');
            $table->string('address');
            $table->string('email')->unique();
            $table->string('password');
            $table->enum('userType', array_column(UserType::cases(), 'value'))->default('Tanuló');

            $table->foreignId('rfidCard_id')->constrained('rfid_cards');
            $table->foreignId('class_id')->nullable()->constrained('class');
            $table->foreignId('group_id')->nullable()->constrained('group');
            $table->enum('status', array_column(UserStatus::cases(), 'value'))->default('inactive');
            $table->boolean('hasDiscount')->default(false);
            $table->timestamps();
            $table->rememberToken();
            $table->timestamps();
        });

        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignId('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('sessions');
    }
};

/*
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

use App\Enums\UserType;
use App\Enums\UserStatus;

return new class extends Migration
{
   
    public function up(): void
    {
        Schema::create('user', function (Blueprint $table) {
            $table->id();
            $table->string('firstName');
            $table->string('lastName');
            $table->string('thirdName')->nullable();
            $table->foreignId('city_id')->constrained()->onDelete('cascade');
            $table->string('address');
            $table->string('email')->unique();
            $table->string('password');
            $table->enum('userType', array_column(UserType::cases(), 'value'))->default('Tanuló');

            $table->foreignId('rfidCard_id')->constrained('rfid_cards');
            $table->foreignId('class_id')->nullable()->constrained('classes');
            $table->foreignId('group_id')->nullable()->constrained('groups');
            $table->enum('status', array_column(UserStatus::cases(), 'value'))->default('inactive');
            $table->boolean('hasDiscount')->default(false);
            $table->timestamps();
            $table->rememberToken();
            $table->timestamps();
        });

        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignId('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });
    }

   
    public function down(): void
    {
        Schema::dropIfExists('user');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('sessions');
    }
};

*/ 