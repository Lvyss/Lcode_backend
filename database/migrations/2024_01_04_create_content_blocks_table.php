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
// database/migrations/2024_01_04_create_content_blocks_table.php
Schema::create('content_blocks', function (Blueprint $table) {
    $table->id();
    $table->foreignId('part_id')->constrained()->onDelete('cascade');
    $table->string('type'); // heading, paragraph, code, code_block, exercise, image
    $table->integer('order_index')->default(0);
    $table->json('content')->nullable(); // ✅ GUNAKAN JSON UNTUK FLEXIBILITY
    $table->text('text_content')->nullable(); // ✅ BACKUP UNTUK PLAIN TEXT
    $table->string('language')->nullable(); // untuk code blocks
    $table->json('metadata')->nullable(); // ✅ EXTRA DATA (level heading, dll)
    $table->timestamps();
});
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('content_blocks');
    }
};
