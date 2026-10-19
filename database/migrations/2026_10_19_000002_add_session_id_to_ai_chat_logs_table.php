<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ai_chat_logs', function (Blueprint $table) {
            $table->foreignUuid('session_id')
                ->nullable()
                ->after('classroom_id')
                ->constrained('ai_chat_sessions')
                ->nullOnDelete();

            $table->index(['session_id', 'id']);
        });
    }

    public function down(): void
    {
        Schema::table('ai_chat_logs', function (Blueprint $table) {
            $table->dropIndex(['session_id', 'id']);
            $table->dropConstrainedForeignId('session_id');
        });
    }
};
