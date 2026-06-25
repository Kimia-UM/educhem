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
        if (!Schema::hasColumn('student_answers', 'is_locked')) {
            Schema::table('student_answers', function (Blueprint $table) {
                $table->boolean('is_locked')->default(false)->after('ai_feedback');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('student_answers', 'is_locked')) {
            Schema::table('student_answers', function (Blueprint $table) {
                $table->dropColumn('is_locked');
            });
        }
    }
};
