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
// database/migrations/2024_01_07_create_badges_table.php
Schema::create('user_badges', function (Blueprint $table) {
    $table->id();
    $table->foreignId('user_id')->constrained()->onDelete('cascade');
    $table->foreignId('badge_id')->constrained()->onDelete('cascade');
    $table->timestamp('earned_at');
    $table->timestamps();
    
    $table->unique(['user_id', 'badge_id']); // Prevent duplicate badges
});
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_badges');
    }
};
