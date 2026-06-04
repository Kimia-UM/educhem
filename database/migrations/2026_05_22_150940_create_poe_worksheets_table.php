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
        Schema::create('poe_worksheets', function (Blueprint $table) {
            $table->id();

                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();    
                $table->foreignId('topic_id')->constrained('topics')->cascadeOnDelete();    
            
                $table->text('predict_text')->nullable();
                $table->text('observe_text')->nullable();
                $table->text('explain_text')->nullable();

                // Enum : Draft, Submitted
                $table->enum('status', ['Draft', 'Submitted'])->default('Draft');
                $table->timestamps();

                $table->unique(['user_id', 'topic_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('poe_worksheets');
    }
};
