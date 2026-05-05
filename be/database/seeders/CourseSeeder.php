<?php

namespace Database\Seeders;

use App\Models\Course;
use Illuminate\Database\Seeder;

class CourseSeeder extends Seeder
{
    public function run(): void
    {
        $courses = [
            [
                'title' => 'Anh Ngữ Giao Tiếp Cơ Bản',
                'slug' => 'anh-ngu-giao-tiep-co-ban',
                'description' => 'Khóa học giúp bạn tự tin giao tiếp tiếng Anh trong các tình huống hàng ngày từ những bước đầu tiên.',
                'thumbnail' => 'https://images.unsplash.com/photo-1546410531-bb4caa6b424d?w=600&h=400&fit=crop',
                'level' => 'beginner',
                'price' => 299000,
                'status' => 'published',
            ],
            [
                'title' => 'IELTS 7.0 Target',
                'slug' => 'ielts-7-0-target',
                'description' => 'Lộ trình học IELTS toàn diện với chiến thuật làm bài hiệu quả, luyện tập thực tế cùng AI.',
                'thumbnail' => 'https://images.unsplash.com/photo-1434030216411-0b793f4b4173?w=600&h=400&fit=crop',
                'level' => 'intermediate',
                'price' => 799000,
                'status' => 'published',
            ],
            [
                'title' => 'Business English Cho Dân Văn Phòng',
                'slug' => 'business-english-dan-van-phong',
                'description' => 'Thành thạo tiếng Anh thương mại, email, thuyết trình và giao dịch trong môi trường doanh nghiệp.',
                'thumbnail' => 'https://images.unsplash.com/photo-1552664730-d307ca884978?w=600&h=400&fit=crop',
                'level' => 'upper_intermediate',
                'price' => 599000,
                'status' => 'published',
            ],
            [
                'title' => 'Phát Âm & Ngữ Điệu Chuẩn Anh Mỹ',
                'slug' => 'phat-am-ngu-dieu-chuan-anh-my',
                'description' => 'Cải thiện phát âm, ngữ điệu tự nhiên như người bản xứ với sự hỗ trợ của AI.',
                'thumbnail' => 'https://images.unsplash.com/photo-1478737270239-2f02b77fc618?w=600&h=400&fit=crop',
                'level' => 'beginner',
                'price' => 0,
                'status' => 'published',
            ],
            [
                'title' => 'TOEFL iBT 100+ Preparation',
                'slug' => 'toefl-ibt-100-preparation',
                'description' => 'Ôn luyện TOEFL iBT đạt điểm 100+ với phương pháp học hiệu quả và đề thi thực tế.',
                'thumbnail' => 'https://images.unsplash.com/photo-1513258496099-48168024aec0?w=600&h=400&fit=crop',
                'level' => 'advanced',
                'price' => 899000,
                'status' => 'published',
            ],
            [
                'title' => 'Tiếng Anh Giao Tiếp Trung Cấp',
                'slug' => 'tieng-anh-giao-tiep-trung-cap',
                'description' => 'Nâng cao khả năng giao tiếp, mở rộng từ vựng và thành thạo ngữ pháp nâng cao.',
                'thumbnail' => 'https://images.unsplash.com/photo-1522202176988-66273c2fd55f?w=600&h=400&fit=crop',
                'level' => 'intermediate',
                'price' => 399000,
                'status' => 'published',
            ],
        ];

        foreach ($courses as $course) {
            Course::create($course);
        }
    }
}
