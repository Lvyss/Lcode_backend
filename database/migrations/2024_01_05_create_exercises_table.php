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
// database/migrations/2024_01_05_create_exercises_table.php
Schema::create('exercises', function (Blueprint $table) {
    $table->id();
    $table->foreignId('part_id')->constrained()->onDelete('cascade');
    $table->string('type'); // fill_blank, multiple_choice, code_test
    $table->text('question');
    $table->json('solution'); // JSON untuk structured solution
    $table->text('code_template')->nullable();
    $table->text('hint')->nullable(); // ✅ TAMBAH HINT
    $table->string('difficulty')->default('easy'); // ✅ easy, medium, hard
    $table->integer('exp_reward')->default(10);
    $table->integer('order_index')->default(0);
    $table->boolean('is_active')->default(true); // ✅ TAMBAH is_active
    $table->timestamps();
});
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('exercises');
    }
};
