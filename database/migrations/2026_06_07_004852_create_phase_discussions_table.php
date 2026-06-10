<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('phase_discussions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('phase_id')->constrained('topic_phases')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            
            // Kolom ini mengizinkan sistem komentar bersarang (Nested Reply)
            $table->unsignedBigInteger('parent_id')->nullable(); 
            
            $table->text('message');
            $table->timestamps();

            $table->foreign('parent_id')->references('id')->on('phase_discussions')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('phase_discussions');
    }
};