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
Schema::create('badges', function (Blueprint $table) {
    $table->id();
    $table->string('name');
    $table->string('icon_path'); // EMOJI ONLY - simple!
    $table->string('color'); // yellow, blue, green, purple, red, indigo
    $table->text('description');
    $table->foreignId('section_id')->nullable()->constrained();
    $table->integer('required_parts')->default(1);
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
        Schema::dropIfExists('badges');
    }
};


