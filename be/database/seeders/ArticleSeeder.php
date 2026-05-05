<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Article;

class ArticleSeeder extends Seeder
{
    public function run(): void
    {
        $articles = [
            [
                'title' => 'Cách học từ vựng tiếng Anh hiệu quả',
                'slug' => 'cach-hoc-tu-vung-tieng-anh-hieu-qua',
                'summary' => 'Bài viết chia sẻ những phương pháp học từ vựng tiếng Anh hiệu quả nhất, giúp bạn nhớ lâu và sử dụng thành thạo.',
                'content' => '<h2>1. Học từ vựng theo chủ đề</h2><p>Học từ vựng theo chủ đề giúp bạn liên kết các từ với nhau, dễ nhớ hơn và có thể sử dụng trong các tình huống cụ thể.</p><h2>2. Sử dụng Flashcard</h2><p>Flashcard là công cụ học từ vựng phổ biến nhất, giúp bạn ôn tập theo phương pháp lặp lại ngắn cách đều.</p><h2>3. Học qua ngữ cảnh</h2><p>Thay vì học từ rời rạc, hãy học từ trong câu, trong bài viết để hiểu rõ cách sử dụng.</p>',
                'thumbnail' => 'https://images.unsplash.com/photo-1434030216411-0b793f4b4173?w=800',
                'category' => 'Phương pháp học',
                'author' => 'Nguyễn Văn A',
                'author_avatar' => 'https://i.pravatar.cc/150?img=1',
                'is_featured' => true,
                'is_active' => true,
                'view_count' => 1250,
            ],
            [
                'title' => '10 cấu trúc câu tiếng Anh thông dụng nhất',
                'slug' => '10-cau-truc-cau-tieng-anh-thong-dung-nhat',
                'summary' => 'Tổng hợp 10 cấu trúc câu tiếng Anh thông dụng nhất trong giao tiếp hàng ngày.',
                'content' => '<h2>1. Câu chủ ngữ - động từ - bổ ngữ (S + V + O)</h2><p>Đây là cấu trúc cơ bản nhất trong tiếng Anh.</p><h2>2. Câu điều kiện (If + S + would + V)</h2><p>Dùng để diễn đạt các giả thuyết và kết quả.</p><h2>3. Câu so sánh (S + be + adj + -er + than)</h2><p>Dùng để so sánh hai đối tượng.</p>',
                'thumbnail' => 'https://images.unsplash.com/photo-1456513080510-7bf3a84b82f8?w=800',
                'category' => 'Ngữ pháp',
                'author' => 'Trần Thị B',
                'author_avatar' => 'https://i.pravatar.cc/150?img=2',
                'is_featured' => true,
                'is_active' => true,
                'view_count' => 890,
            ],
            [
                'title' => 'Hướng dẫn ôn thi IELTS Reading từ A đến Z',
                'slug' => 'huong-dan-on-thi-ielts-reading',
                'summary' => 'Bài viết hướng dẫn chi tiết cách ôn thi IELTS Reading hiệu quả, từ cơ bản đến nâng cao.',
                'content' => '<h2>Giới thiệu về IELTS Reading</h2><p>IELTS Reading là phần thi thứ hai trong bài thi IELTS, kéo dài 60 phút với 40 câu hỏi.</p><h2>Các dạng câu hỏi thường gặp</h2><p>Multiple choice, True/False/Not given, Yes/No/Not given, Sentence completion...</p><h2>Chiến lược làm bài</h2><p>Skimming, Scanning, đọc hiểu chi tiết...</p>',
                'thumbnail' => 'https://images.unsplash.com/photo-1513258496099-48168024aec0?w=800',
                'category' => 'IELTS',
                'author' => 'Lê Văn C',
                'author_avatar' => 'https://i.pravatar.cc/150?img=3',
                'is_featured' => false,
                'is_active' => true,
                'view_count' => 567,
            ],
            [
                'title' => 'Phân biệt các thì trong tiếng Anh',
                'slug' => 'phan-biet-cac-thi-trong-tieng-anh',
                'summary' => 'Hướng dẫn cách phân biệt và sử dụng đúng các thì trong tiếng Anh.',
                'content' => '<h2>Thì hiện tại đơn (Present Simple)</h2><p>Dùng cho các sự thật chung, thói quen, sự thật hiển nhiên.</p><h2>Thì hiện tại tiếp diễn (Present Continuous)</h2><p>Dùng cho hành động đang xảy ra tại thời điểm nói.</p><h2>Thì quá khứ đơn (Past Simple)</h2><p>Dùng cho hành động đã hoàn thành trong quá khứ.</p>',
                'thumbnail' => 'https://images.unsplash.com/photo-1503676260728-1c00da094a0b?w=800',
                'category' => 'Ngữ pháp',
                'author' => 'Phạm Thị D',
                'author_avatar' => 'https://i.pravatar.cc/150?img=4',
                'is_featured' => false,
                'is_active' => true,
                'view_count' => 432,
            ],
            [
                'title' => 'Cách cải thiện kỹ năng nghe tiếng Anh',
                'slug' => 'cach-cai-thien-ky-nang-nghe-tieng-anh',
                'summary' => 'Những tips và tricks giúp bạn cải thiện kỹ năng nghe tiếng Anh nhanh chóng.',
                'content' => '<h2>1. Nghe mỗi ngày</h2><p>Hãy tạo thói quen nghe tiếng Anh ít nhất 30 phút mỗi ngày.</p><h2>2. Nghe từ dễ đến khó</h2><p>Bắt đầu với các bài nghe ngắn, tốc độ chậm, sau đó tăng dần độ khó.</p><h2>3. Chú thích từ mới</h2><p>Ghi chú lại những từ và cụm từ mới bạn nghe được.</p>',
                'thumbnail' => 'https://images.unsplash.com/photo-1485546246426-74dc88dec4d9?w=800',
                'category' => 'Kỹ năng',
                'author' => 'Hoàng Văn E',
                'author_avatar' => 'https://i.pravatar.cc/150?img=5',
                'is_featured' => true,
                'is_active' => true,
                'view_count' => 789,
            ],
            [
                'title' => 'Giới từ trong tiếng Anh - Hướng dẫn đầy đủ',
                'slug' => 'gioi-tu-trong-tieng-anh',
                'summary' => 'Tổng hợp các giới từ thông dụng trong tiếng Anh và cách sử dụng chúng.',
                'content' => '<h2>Giới từ chỉ thời gian</h2><p>In, on, at, during, before, after...</p><h2>Giới từ chỉ nơi chốn</h2><p>In, on, at, under, over, between...</p><h2>Giới từ chỉ cách thức</h2><p>By, with, through, via...</p>',
                'thumbnail' => 'https://images.unsplash.com/photo-1499750310107-5fef28a66643?w=800',
                'category' => 'Ngữ pháp',
                'author' => 'Nguyễn Thị F',
                'author_avatar' => 'https://i.pravatar.cc/150?img=6',
                'is_featured' => false,
                'is_active' => true,
                'view_count' => 654,
            ],
            [
                'title' => 'Cách viết email tiếng Anh chuyên nghiệp',
                'slug' => 'cach-viet-email-tieng-anh-chuyen-nghiep',
                'summary' => 'Hướng dẫn cách viết email tiếng Anh chuyên nghiệp trong công việc.',
                'content' => '<h2>Cấu trúc một email chuyên nghiệp</h2><p>Greeting - Introduction - Body - Closing - Signature</p><h2>Các cụm từ thường dùng</h2><p>I am writing to... Please find attached... Looking forward to hearing from you...</p>',
                'thumbnail' => 'https://images.unsplash.com/photo-1557200134-90327ee9fafa?w=800',
                'category' => 'Kỹ năng',
                'author' => 'Đặng Văn G',
                'author_avatar' => 'https://i.pravatar.cc/150?img=7',
                'is_featured' => false,
                'is_active' => true,
                'view_count' => 321,
            ],
            [
                'title' => 'Những lỗi sai thường gặp khi học tiếng Anh',
                'slug' => 'nhung-loi-sai-thuong-gap-khi-hoc-tieng-anh',
                'summary' => 'Liệt kê những lỗi sai phổ biến nhất mà người học tiếng Anh thường mắc phải.',
                'content' => '<h2>1. Sử dụng sai giới từ</h2><p>Nhiều người hay nhầm lẫn "in", "on", "at" khi nói về thời gian.</p><h2>2. Nhầm lẫn các thì</h2><p>Không phân biệt được giữa Present Perfect và Past Simple.</p><h2>3. Dùng sai danh từ đếm được/không đếm được</h2><p>A/an, some/any, much/many...</p>',
                'thumbnail' => 'https://images.unsplash.com/photo-1516321318423-f06f85e504b3?w=800',
                'category' => 'Phương pháp học',
                'author' => 'Vũ Thị H',
                'author_avatar' => 'https://i.pravatar.cc/150?img=8',
                'is_featured' => false,
                'is_active' => true,
                'view_count' => 445,
            ],
        ];

        foreach ($articles as $article) {
            Article::create($article);
        }
    }
}
