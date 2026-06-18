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
        Schema::table('topic_phases', function (Blueprint $table) {
            $table->boolean('is_chatbot_enabled')->default(true)->after('is_ai_enabled');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('topic_phases', function (Blueprint $table) {
            $table->dropColumn('is_chatbot_enabled');
        });
    }
};
