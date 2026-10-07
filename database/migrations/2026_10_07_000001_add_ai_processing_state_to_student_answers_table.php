<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('student_answers', function (Blueprint $table) {
            $table->string('ai_status', 32)->nullable()->index();
            $table->unsignedBigInteger('answer_version')->default(1);
            $table->timestamp('ai_requested_at')->nullable();
            $table->timestamp('ai_completed_at')->nullable();
            $table->string('ai_error_code', 64)->nullable();
            $table->index(['user_id', 'phase_id'], 'student_answers_user_phase_index');
        });
    }

    public function down(): void
    {
        Schema::table('student_answers', function (Blueprint $table) {
            $table->dropIndex('student_answers_user_phase_index');
            $table->dropIndex(['ai_status']);
            $table->dropColumn([
                'ai_status',
                'answer_version',
                'ai_requested_at',
                'ai_completed_at',
                'ai_error_code',
            ]);
        });
    }
};
