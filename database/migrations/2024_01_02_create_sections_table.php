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
// database/migrations/2024_01_02_create_sections_table.php
Schema::create('sections', function (Blueprint $table) {
    $table->id();
    $table->foreignId('language_id')->constrained()->onDelete('cascade');
    $table->string('name'); // "Hello World", "Operator Kondisi"
    $table->text('description')->nullable();
    $table->integer('order_index')->default(0);
    $table->integer('exp_reward')->default(0); // EXP bonus
    $table->boolean('is_active')->default(true);
    $table->timestamps();
});
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sections');
    }
};
