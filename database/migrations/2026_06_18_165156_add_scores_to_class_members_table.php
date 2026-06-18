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
        Schema::table('class_members', function (Blueprint $table) {
            $table->integer('pre_test_score')->nullable()->after('is_evaluation_sent');
            $table->integer('post_test_score')->nullable()->after('pre_test_score');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('class_members', function (Blueprint $table) {
            $table->dropColumn(['pre_test_score', 'post_test_score']);
        });
    }
};
