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
// database/migrations/2024_01_03_create_parts_table.php
Schema::create('parts', function (Blueprint $table) {
    $table->id();
    $table->foreignId('section_id')->constrained()->onDelete('cascade');
    $table->string('title'); // "Part 1: If-Else"
    $table->text('description')->nullable(); // ✅ TAMBAH DESCRIPTION
    $table->integer('order_index')->default(0);
    $table->integer('exp_reward')->default(10);
    $table->boolean('is_active')->default(true); // ✅ GUNAKAN is_active UNTUK CONSISTENCY
    $table->timestamps();
});
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('parts');
    }
};
