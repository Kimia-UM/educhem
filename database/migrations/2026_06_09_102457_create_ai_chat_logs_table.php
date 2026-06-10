<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_chat_logs', function (Blueprint $table) {
            $table->id();
            
            // Menyimpan siapa yang bertanya (Siswa)
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            
            // UBAH DI SINI: Gunakan tipe data angka biasa tanpa ->constrained()
            // agar PostgreSQL tidak mencari tabel "classrooms" yang tidak ada.
            $table->unsignedBigInteger('classroom_id')->nullable();
            
            // Pesan dari Siswa
            $table->text('prompt');
            
            // Balasan dari AI (Bisa Null saat AI masih berpikir)
            $table->text('response')->nullable();
            
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_chat_logs');
    }
};