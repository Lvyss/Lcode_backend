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
// database/migrations/2024_01_06_create_user_progress_table.php
Schema::create('user_progress', function (Blueprint $table) {
    $table->id();
    $table->foreignId('user_id')->constrained()->onDelete('cascade');
    $table->foreignId('part_id')->nullable()->constrained()->onDelete('cascade'); // ✅ BISA NULL
    $table->foreignId('exercise_id')->nullable()->constrained()->onDelete('cascade'); // ✅ BISA NULL
    $table->boolean('completed')->default(false);
    $table->integer('exp_earned')->default(0); // ✅ TAMBAH INI
     $table->text('user_answer')->nullable(); // ✅ UNTUK SIMPAN CODE/JAWABAN
    $table->boolean('is_correct')->default(false); // ✅ UNTUK TRACK BENAR/SALAH
    $table->integer('attempts')->default(0);
    $table->timestamp('completed_at')->nullable();
    $table->timestamps();
    
    $table->unique(['user_id', 'part_id', 'exercise_id']); // ✅ KOMBINASI UNIK
});
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_progress');
    }
};
