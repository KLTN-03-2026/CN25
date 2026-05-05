<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Document;

class DocumentSeeder extends Seeder
{
    public function run(): void
    {
        $documents = [
            [
                'title' => 'Giáo trình tiếng Anh cơ bản',
                'description' => 'Tài liệu học tiếng Anh từ cơ bản đến nâng cao dành cho người mới bắt đầu.',
                'file_type' => 'pdf',
                'file_url' => 'https://example.com/docs/english-basics.pdf',
                'content' => 'Nội dung giáo trình tiếng Anh cơ bản...',
                'author' => 'Nguyễn Văn A',
                'download_count' => 150,
                'is_active' => true,
                'order' => 1,
            ],
            [
                'title' => 'Bài tập ngữ pháp tiếng Anh',
                'description' => 'Tổng hợp bài tập ngữ pháp tiếng Anh có đáp án chi tiết.',
                'file_type' => 'doc',
                'file_url' => 'https://example.com/docs/grammar-exercises.doc',
                'content' => 'Các bài tập ngữ pháp...',
                'author' => 'Trần Thị B',
                'download_count' => 89,
                'is_active' => true,
                'order' => 2,
            ],
            [
                'title' => '3000 từ vựng tiếng Anh thông dụng',
                'description' => 'Danh sách 3000 từ vựng tiếng Anh thông dụng nhất, sắp xếp theo chủ đề.',
                'file_type' => 'pdf',
                'file_url' => 'https://example.com/docs/vocabulary-3000.pdf',
                'content' => 'Danh sách từ vựng...',
                'author' => 'Lê Văn C',
                'download_count' => 234,
                'is_active' => true,
                'order' => 3,
            ],
            [
                'title' => 'Bí quyết học IELTS 7.0+',
                'description' => 'Hướng dẫn chi tiết cách đạt điểm IELTS 7.0 trở lên.',
                'file_type' => 'pdf',
                'file_url' => 'https://example.com/docs/ielts-tips.pdf',
                'content' => 'Các bí quyết ôn thi IELTS...',
                'author' => 'Phạm Thị D',
                'download_count' => 312,
                'is_active' => true,
                'order' => 4,
            ],
            [
                'title' => 'Ngữ pháp tiếng Anh nâng cao',
                'description' => 'Tài liệu ngữ pháp tiếng Anh nâng cao cho người học trung cấp.',
                'file_type' => 'xls',
                'file_url' => 'https://example.com/docs/advanced-grammar.xlsx',
                'content' => 'Ngữ pháp nâng cao...',
                'author' => 'Hoàng Văn E',
                'download_count' => 67,
                'is_active' => true,
                'order' => 5,
            ],
            [
                'title' => 'Bài nghe TOEIC mẫu',
                'description' => 'Tổng hợp bài nghe TOEIC mẫu theo từng phần.',
                'file_type' => 'zip',
                'file_url' => 'https://example.com/docs/toeic-listening.zip',
                'content' => 'Bài nghe TOEIC...',
                'author' => 'Nguyễn Thị F',
                'download_count' => 178,
                'is_active' => true,
                'order' => 6,
            ],
            [
                'title' => 'Hướng dẫn phát âm tiếng Anh',
                'description' => 'Video hướng dẫn phát âm chuẩn IPA cho người học.',
                'file_type' => 'video',
                'file_url' => 'https://example.com/docs/pronunciation.mp4',
                'content' => 'Hướng dẫn phát âm...',
                'author' => 'Đặng Văn G',
                'download_count' => 45,
                'is_active' => false,
                'order' => 7,
            ],
            [
                'title' => 'Bài viết mẫu IELTS Writing',
                'description' => 'Tuyển chọn bài viết IELTS Writing band 8.0+ có phân tích.',
                'file_type' => 'pdf',
                'file_url' => 'https://example.com/docs/ielts-writing-samples.pdf',
                'content' => 'Bài viết mẫu IELTS...',
                'author' => 'Vũ Thị H',
                'download_count' => 201,
                'is_active' => true,
                'order' => 8,
            ],
        ];

        foreach ($documents as $doc) {
            Document::create($doc);
        }
    }
}
