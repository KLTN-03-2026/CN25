<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vocabularies', function (Blueprint $table) {
            if (!Schema::hasColumn('vocabularies', 'order')) {
                $table->integer('order')->default(0);
            }
        });
    }

    public function down(): void
    {
        Schema::table('vocabularies', function (Blueprint $table) {
            if (Schema::hasColumn('vocabularies', 'order')) {
                $table->dropColumn('order');
            }
        });
    }
};
