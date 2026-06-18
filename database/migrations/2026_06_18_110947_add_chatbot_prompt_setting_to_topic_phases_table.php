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
            $table->text('chatbot_prompt_setting')->nullable()->after('ai_prompt_setting');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('topic_phases', function (Blueprint $table) {
            $table->dropColumn('chatbot_prompt_setting');
        });
    }
};
