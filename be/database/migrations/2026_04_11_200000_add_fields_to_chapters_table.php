<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Thêm các trường mới vào bảng chapters
     * - type: loại chapter (vocabulary, grammar, listening, speaking)
     * - slug: URL thân thiện
     * - description: mô tả chapter
     * - status: trạng thái (draft, published)
     * - is_free: miễn phí hay không
     */
    public function up(): void
    {
        Schema::table('chapters', function (Blueprint $table) {
            if (!Schema::hasColumn('chapters', 'slug')) {
                $table->string('slug')->nullable()->after('title');
            }
            if (!Schema::hasColumn('chapters', 'description')) {
                $table->text('description')->nullable()->after('slug');
            }
            if (!Schema::hasColumn('chapters', 'type')) {
                $table->string('type')->nullable()->after('description');
            }
            if (!Schema::hasColumn('chapters', 'status')) {
                $table->string('status')->default('draft')->after('type');
            }
            if (!Schema::hasColumn('chapters', 'is_free')) {
                $table->boolean('is_free')->default(false)->after('status');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('chapters', function (Blueprint $table) {
            $columns = ['slug', 'description', 'type', 'status', 'is_free'];
            foreach ($columns as $column) {
                if (Schema::hasColumn('chapters', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
