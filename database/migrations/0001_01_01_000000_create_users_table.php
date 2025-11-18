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
// database/migrations/2014_10_12_000000_create_users_table.php
Schema::create('users', function (Blueprint $table) {
    // Default Laravel
    $table->id();
    $table->string('name');
    $table->string('email')->unique();
    $table->timestamp('email_verified_at')->nullable();
    $table->string('password')->nullable(); // Bisa null karena pakai Google login
    $table->rememberToken();
    $table->timestamps();

    // TAMBAHAN CUSTOM UNTUK LCODE
    $table->string('google_id')->unique()->nullable(); // Untuk Google login
    $table->string('avatar')->nullable(); // Avatar dari Google
    $table->integer('total_exp')->default(0); // TOTAL EXP USER
    $table->integer('current_streak')->default(0); // Streak hari berurutan
    $table->date('last_activity_date')->nullable(); // Untuk hitung streak
    $table->enum('role', ['user', 'admin'])->default('user'); // ROLE SYSTEM
    $table->string('username')->unique()->nullable(); // Username custom
    $table->text('bio')->nullable(); // Bio user
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
        Schema::dropIfExists('users');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('sessions');
    }
};
