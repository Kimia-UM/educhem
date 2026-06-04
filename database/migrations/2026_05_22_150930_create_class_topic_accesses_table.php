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
        Schema::create('class_topic_accesses', function (Blueprint $table) {
            $table->id();
            
            $table->foreignId('class_id')->constrained('classes')->cascadeOnDelete();
            $table->foreignId('topic_id')->constrained('topics')->cascadeOnDelete();
            
            // Status akses topik, default false (ditutup) saat kelas baru dibuat
            $table->boolean('is_open')->default(false);
            
            $table->timestamps();

            // Memastikan satu kelas hanya memiliki satu pengaturan akses per topik
            $table->unique(['class_id', 'topic_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('class_topic_accesses');
    }
};
