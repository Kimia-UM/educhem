<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('phase_contents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('topic_phase_id')->constrained('topic_phases')->cascadeOnDelete();
            
            // Jenis konten: 'text', 'image', 'h5p', 'input_text', 'multiple_choice'
            $table->string('type'); 
            
            // Simpan isinya di dalam JSON agar fleksibel (misal URL gambar, isi teks, atau opsi ganda)
            $table->json('content_data')->nullable(); 
            
            $table->integer('order')->default(0); // Urutan konten dari atas ke bawah
            
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('phase_contents');
    }
};