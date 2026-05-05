<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Tạo bảng courses với cấu trúc chuẩn:
     * - id: khóa chính tự tăng
     * - title: tên khóa học
     * - slug: URL thân thiện (duy nhất)
     * - description: mô tả khóa học
     * - thumbnail: ảnh đại diện
     * - level: trình độ (beginner, intermediate, advanced)
     * - price: giá khóa học
     * - status: trạng thái (draft, published)
     * - timestamps: created_at, updated_at tự động
     */
    public function up(): void
    {
        Schema::create('courses', function (Blueprint $table) {
            $table->id();                                      // Khóa chính tự tăng
            $table->string('title');                          // Tên khóa học
            $table->string('slug')->nullable()->unique();  // URL thân thiện (duy nhất, nullable để Model tự tạo)
            $table->text('description')->nullable();           // Mô tả khóa học
            $table->string('thumbnail')->nullable();            // Ảnh đại diện
            $table->string('level')->nullable();               // Trình độ: beginner, intermediate, advanced
            $table->decimal('price', 10, 2)->default(0);       // Giá khóa học (0 = miễn phí)
            $table->string('status')->default('draft');         // Trạng thái: draft, published
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('courses');
    }
};
