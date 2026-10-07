<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ai_chat_logs', function (Blueprint $table) {
            $table->string('processing_mode', 32)->nullable()->index();
            $table->unsignedBigInteger('enqueued_at_ms')->nullable();
            $table->timestamp('processing_started_at')->nullable();
            $table->unsignedInteger('queue_wait_ms')->nullable();
            $table->unsignedInteger('direct_attempt_ms')->nullable();
            $table->unsignedInteger('provider_duration_ms')->nullable();
            $table->unsignedInteger('total_duration_ms')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('ai_chat_logs', function (Blueprint $table) {
            $table->dropIndex(['processing_mode']);
            $table->dropColumn([
                'processing_mode',
                'enqueued_at_ms',
                'processing_started_at',
                'queue_wait_ms',
                'direct_attempt_ms',
                'provider_duration_ms',
                'total_duration_ms',
            ]);
        });
    }
};
