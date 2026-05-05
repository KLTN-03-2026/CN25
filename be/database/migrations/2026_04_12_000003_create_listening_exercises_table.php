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
        Schema::create('listening_exercises', function (Blueprint $table) {
            $table->id();
            $table->foreignId('listening_id')->constrained()->cascadeOnDelete();
            
            $table->string('question');                    // Câu hỏi
            $table->enum('type', ['multiple_choice', 'fill_blank', 'true_false']);
            $table->json('options')->nullable();          // Các lựa chọn (cho multiple_choice)
            $table->string('correct_answer');            // Đáp án đúng
            $table->text('explanation')->nullable();     // Giải thích
            $table->integer('score')->default(10);       // Điểm cho câu này
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('listening_exercises');
    }
};
