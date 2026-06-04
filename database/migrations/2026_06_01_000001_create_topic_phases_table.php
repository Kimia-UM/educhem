<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('topic_phases', function (Blueprint $table) {
            $table->id();
            $table->foreignId('topic_id')->constrained('topics')->cascadeOnDelete();
            
            $table->string('name'); // Contoh: "Fase Predict", "Fase Evaluasi"
            $table->text('description')->nullable();
            $table->integer('order')->default(0); // Untuk mengurutkan fase
            
            // Kotak "setting AI assistant" dari bagan klien
            $table->boolean('is_ai_enabled')->default(false);
            $table->text('ai_prompt_setting')->nullable(); // Instruksi khusus untuk AI di fase ini
            
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('topic_phases');
    }
};