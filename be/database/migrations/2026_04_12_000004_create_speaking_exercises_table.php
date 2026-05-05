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
        Schema::create('speaking_exercises', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lesson_id')->constrained('lessons')->onDelete('cascade');
            $table->enum('type', ['repeat', 'read', 'describe', 'qa'])->comment('Loại bài tập: repeat, read, describe, qa');
            $table->text('content')->comment('Nội dung câu hỏi hoặc văn bản');
            $table->string('audio_url')->nullable()->comment('URL file audio');
            $table->string('image_url')->nullable()->comment('URL hình ảnh cho describe');
            $table->text('keywords')->nullable()->comment('Từ khóa gợi ý, phân cách bằng dấu phẩy');
            $table->text('sample_answer')->nullable()->comment('Câu trả lời mẫu cho Q&A');
            $table->integer('order')->default(0)->comment('Thứ tự sắp xếp');
            $table->timestamps();

            $table->index(['lesson_id', 'type']);
            $table->index(['lesson_id', 'order']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('speaking_exercises');
    }
};
