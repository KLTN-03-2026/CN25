<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('course_quiz_questions', function (Blueprint $table) {
            $table->string('audio_url')->nullable()->after('correct_answer');
            $table->text('sample_answer')->nullable()->after('audio_url');
        });
    }

    public function down(): void
    {
        Schema::table('course_quiz_questions', function (Blueprint $table) {
            $table->dropColumn(['audio_url', 'sample_answer']);
        });
    }
};
