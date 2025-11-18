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
// database/migrations/2024_01_01_create_languages_table.php
Schema::create('languages', function (Blueprint $table) {
    $table->id();
    $table->string('name'); // Python, C++, JavaScript
    $table->text('icon')->nullable();
    $table->text('description')->nullable();
    $table->integer('order_index')->default(0);
    $table->boolean('is_active')->default(true);
    $table->timestamps();
});
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('languages');
    }
};
