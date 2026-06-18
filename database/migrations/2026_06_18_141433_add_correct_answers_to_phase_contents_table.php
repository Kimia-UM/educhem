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
        Schema::table('phase_contents', function (Blueprint $table) {
            $table->json('correct_answers')->nullable()->after('content_data');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('phase_contents', function (Blueprint $table) {
            $table->dropColumn('correct_answers');
        });
    }
};
