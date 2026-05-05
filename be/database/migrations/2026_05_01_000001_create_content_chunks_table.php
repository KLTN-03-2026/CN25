<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('content_chunks', function (Blueprint $table) {
            $table->id();
            $table->string('content_type');
            $table->unsignedInteger('content_id');
            $table->string('title')->nullable();
            $table->text('chunk_text');
            $table->json('embedding')->nullable();
            $table->unsignedInteger('chunk_index')->default(0);
            $table->boolean('is_embedded')->default(false);
            $table->timestamps();

            $table->index(['content_type', 'content_id']);
            $table->index('is_embedded');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('content_chunks');
    }
};
