<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

/**
 * DatabaseSeeder - Chạy tất cả các seeder
 * 
 * Chạy: php artisan db:seed
 * Hoặc: php artisan migrate:fresh --seed
 */
class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            UserSeeder::class,
            CourseSeeder::class,
            DocumentSeeder::class,
            ArticleSeeder::class,
            ReviewSeeder::class,
        ]);
    }
}
