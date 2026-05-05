<?php

namespace Database\Seeders;

use App\Models\Review;
use App\Models\User;
use App\Models\Course;
use Illuminate\Database\Seeder;

class ReviewSeeder extends Seeder
{
    public function run(): void
    {
        $users = User::where('role', 'student')->get();
        $courses = Course::published()->get();

        if ($users->isEmpty() || $courses->isEmpty()) {
            $this->command->warn('Canh bao: Khong co user hoac khoa hoc. Vui long chay UserSeeder va CourseSeeder truoc.');
            return;
        }

        $reviewsData = [];

        // Lay tat ca user_id va course_id lam mang
        $userIds = $users->pluck('id')->toArray();
        $courseIds = $courses->pluck('id')->toArray();

        // ===== Khóa học 1: Anh Ngữ Giao Tiếp Cơ Bản (ID 1) =====
        $course1Reviews = [
            [
                'user_id' => $userIds[0] ?? $userIds[array_rand($userIds)],
                'rating' => 5,
                'content' => 'Khóa học cực kỳ hay! Giáo trình được biên soạn rất kỹ lưỡng, dễ hiểu ngay cả với người mới bắt đầu. Tôi đã tự tin hơn rất nhiều trong giao tiếp hàng ngày sau khi hoàn thành khóa học này.',
                'status' => 'duyet',
                'admin_reply' => 'Cảm ơn bạn đã tin tưởng và đánh giá tích cực! Chúc bạn học tốt!',
            ],
            [
                'user_id' => $userIds[1] ?? $userIds[array_rand($userIds)],
                'rating' => 5,
                'content' => 'Mình rất thích cách trình bày bài học, mỗi chủ đề đều có ví dụ thực tế rất dễ nhớ. Phần ngữ pháp giải thích rõ ràng, có bài tập luyện tập ngay sau mỗi bài.',
                'status' => 'duyet',
                'admin_reply' => null,
            ],
            [
                'user_id' => $userIds[2] ?? $userIds[array_rand($userIds)],
                'rating' => 4,
                'content' => 'Khóa học tốt, nội dung phong phú. Tuy nhiên mình muốn có thêm nhiều bài luyện nghe hơn nữa. Nói chung rất hài lòng và sẽ giới thiệu cho bạn bè.',
                'status' => 'duyet',
                'admin_reply' => 'Cảm ơn phản hồi của bạn! Đội ngũ đang cập nhật thêm nhiều bài nghe mới. Hãy theo dõi nhé!',
            ],
            [
                'user_id' => $userIds[3] ?? $userIds[array_rand($userIds)],
                'rating' => 5,
                'content' => 'Đây là khóa học tiếng Anh đầu tiên mà mình cảm thấy không bị nhàm chán. Cách chia bài học rất hợp lý, mỗi ngày học một chút là vừa đủ. AI hỗ trợ rất tốt!',
                'status' => 'duyet',
                'admin_reply' => null,
            ],
            [
                'user_id' => $userIds[4] ?? $userIds[array_rand($userIds)],
                'rating' => 4,
                'content' => 'Nội dung chất lượng, giảng viên dễ hiểu. Mình đặc biệt thích phần từ vựng theo chủ đề, nhớ rất lâu. Khuyến khích mọi người nên thử!',
                'status' => 'duyet',
                'admin_reply' => 'Rất vui khi khóa học hữu ích với bạn. Cảm ơn đánh giá!',
            ],
            [
                'user_id' => $userIds[0] ?? $userIds[array_rand($userIds)],
                'rating' => 3,
                'content' => 'Khóa học ổn nhưng phần nói chưa có nhiều bài luyện tập thực tế. Mong sẽ được cải thiện thêm.',
                'status' => 'cho',
                'admin_reply' => null,
            ],
        ];

        foreach ($course1Reviews as $r) {
            $reviewsData[] = array_merge($r, [
                'course_id' => $courseIds[0] ?? 1,
                'created_at' => now()->subDays(rand(1, 30)),
                'updated_at' => now()->subDays(rand(0, 5)),
            ]);
        }

        // ===== Khóa học 2: IELTS 7.0 Target (ID 2) =====
        $course2Reviews = [
            [
                'user_id' => $userIds[1] ?? $userIds[array_rand($userIds)],
                'rating' => 5,
                'content' => 'Sau khi hoàn thành khóa học, mình đã đạt được 7.0 IELTS! Các chiến thuật làm bài rất thực tế, áp dụng được ngay vào bài thi. Đội ngũ giảng viên hỗ trợ nhiệt tình.',
                'status' => 'duyet',
                'admin_reply' => 'Chúc mừng bạn đạt 7.0 IELTS! Thành công là nhờ sự nỗ lực của bạn. Chúc bạn luôn thành công!',
            ],
            [
                'user_id' => $userIds[2] ?? $userIds[array_rand($userIds)],
                'rating' => 5,
                'content' => 'Mình đã thử nhiều khóa học IELTS khác nhưng đây là khóa học tốt nhất. Phương pháp học rõ ràng, có lộ trình cụ thể từng ngày. Đặc biệt phần luyện nói với AI rất hữu ích.',
                'status' => 'duyet',
                'admin_reply' => null,
            ],
            [
                'user_id' => $userIds[3] ?? $userIds[array_rand($userIds)],
                'rating' => 4,
                'content' => 'Khóa học IELTS chất lượng cao, có nhiều đề thi thực tế. Phần Reading và Listening mình cải thiện được rất nhiều. Writing có hướng dẫn chi tiết từng band điểm.',
                'status' => 'duyet',
                'admin_reply' => 'Cảm ơn đánh giá của bạn! Hãy tiếp tục luyện tập nhé.',
            ],
            [
                'user_id' => $userIds[4] ?? $userIds[array_rand($userIds)],
                'rating' => 5,
                'content' => 'Tuyệt vời! Khóa học giúp mình hệ thống lại kiến thức rất bài bản. Mình đã đạt 6.5 sau 2 tháng học, sẽ tiếp tục luyện để đạt 7.5.',
                'status' => 'duyet',
                'admin_reply' => 'Kết quả 6.5 rất tốt! Với sự cố gắng, bạn sẽ đạt được mục tiêu 7.5 sớm thôi.',
            ],
            [
                'user_id' => $userIds[0] ?? $userIds[array_rand($userIds)],
                'rating' => 4,
                'content' => 'Nội dung cập nhật liên tục theo format đề thi mới nhất. Mình rất thích cách mà các bài giảng được trình bày một cách logic và dễ hiểu.',
                'status' => 'duyet',
                'admin_reply' => null,
            ],
            [
                'user_id' => $userIds[1] ?? $userIds[array_rand($userIds)],
                'rating' => 5,
                'content' => 'Học phí hợp lý mà chất lượng vượt kỳ vọng. Giảng viên có kinh nghiệm thi thật, chia sẻ nhiều tips hay. Đây là khoản đầu tư xứng đáng.',
                'status' => 'duyet',
                'admin_reply' => 'Cảm ơn bạn đã tin tưởng! Chúc bạn sớm đạt mục tiêu IELTS.',
            ],
            [
                'user_id' => $userIds[2] ?? $userIds[array_rand($userIds)],
                'rating' => 3,
                'content' => 'Khóa học ổn nhưng tốc độ giảng dạy hơi nhanh, một số phần mình cần xem lại nhiều lần mới hiểu. Nên bổ sung thêm bài tập thực hành.',
                'status' => 'cho',
                'admin_reply' => null,
            ],
        ];

        foreach ($course2Reviews as $r) {
            $reviewsData[] = array_merge($r, [
                'course_id' => $courseIds[1] ?? 2,
                'created_at' => now()->subDays(rand(1, 25)),
                'updated_at' => now()->subDays(rand(0, 5)),
            ]);
        }

        // ===== Khóa học 3: Business English (ID 3) =====
        $course3Reviews = [
            [
                'user_id' => $userIds[3] ?? $userIds[array_rand($userIds)],
                'rating' => 5,
                'content' => 'Khóa học Business English này đã giúp mình tự tin hơn trong các cuộc họp và thuyết trình bằng tiếng Anh. Phần email và giao dịch thương mại rất thực tế.',
                'status' => 'duyet',
                'admin_reply' => 'Rất vui khi khóa học hữu ích cho công việc của bạn!',
            ],
            [
                'user_id' => $userIds[4] ?? $userIds[array_rand($userIds)],
                'rating' => 4,
                'content' => 'Mình là nhân viên văn phòng, khóa học này giúp mình viết email chuyên nghiệp hơn rất nhiều. Các mẫu câu giao tiếp trong cuộc họp cực kỳ hữu ích.',
                'status' => 'duyet',
                'admin_reply' => null,
            ],
            [
                'user_id' => $userIds[0] ?? $userIds[array_rand($userIds)],
                'rating' => 5,
                'content' => 'Sau khóa học này, mình đã được thăng chức nhờ khả năng giao tiếp tiếng Anh tốt hơn. Cảm ơn khóa học rất nhiều!',
                'status' => 'duyet',
                'admin_reply' => 'Chúc mừng bạn! Thành công là nhờ sự nỗ lực của bạn. Hãy tiếp tục phát triển nhé!',
            ],
            [
                'user_id' => $userIds[1] ?? $userIds[array_rand($userIds)],
                'rating' => 4,
                'content' => 'Nội dung phong phú, tập trung đúng vào những gì dân văn phòng cần. Đặc biệt thích phần đàm phán và xử lý tình huống.',
                'status' => 'cho',
                'admin_reply' => null,
            ],
        ];

        foreach ($course3Reviews as $r) {
            $reviewsData[] = array_merge($r, [
                'course_id' => $courseIds[2] ?? 3,
                'created_at' => now()->subDays(rand(1, 20)),
                'updated_at' => now()->subDays(rand(0, 3)),
            ]);
        }

        // ===== Khóa học 4: Phát Âm (ID 4) - Free =====
        $course4Reviews = [
            [
                'user_id' => $userIds[2] ?? $userIds[array_rand($userIds)],
                'rating' => 5,
                'content' => 'Khóa học miễn phí nhưng chất lượng không thua kém khóa học trả phí. Mình đã cải thiện phát âm rất nhiều sau 2 tuần. Ngữ điệu tự nhiên hơn hẳn!',
                'status' => 'duyet',
                'admin_reply' => 'Rất vui khi thấy bạn tiến bộ! Hãy tiếp tục luyện tập mỗi ngày nhé.',
            ],
            [
                'user_id' => $userIds[3] ?? $userIds[array_rand($userIds)],
                'rating' => 5,
                'content' => 'AI chấm phát âm rất chính xác, giúp mình biết chính xác mình đang phát âm sai ở đâu. Video hướng dẫn IPA rõ ràng, dễ theo dõi.',
                'status' => 'duyet',
                'admin_reply' => null,
            ],
            [
                'user_id' => $userIds[4] ?? $userIds[array_rand($userIds)],
                'rating' => 4,
                'content' => 'Khóa học tốt, nên có thêm nhiều bài luyện phát âm theo cụm từ và câu dài hơn. Nói chung rất hài lòng với khóa học miễn phí này.',
                'status' => 'duyet',
                'admin_reply' => 'Cảm ơn góp ý! Đội ngũ đang cập nhật thêm nhiều bài luyện phát âm nâng cao.',
            ],
            [
                'user_id' => $userIds[0] ?? $userIds[array_rand($userIds)],
                'rating' => 5,
                'content' => 'Phát âm chuẩn Anh Mỹ sau khóa học này! Mình đã tự tin hơn khi nói chuyện với người nước ngoài. Highly recommended!',
                'status' => 'duyet',
                'admin_reply' => 'Cảm ơn đánh giá tích cực! Chúc bạn luôn tự tin với tiếng Anh của mình.',
            ],
            [
                'user_id' => $userIds[1] ?? $userIds[array_rand($userIds)],
                'rating' => 4,
                'content' => 'Bổ ích, nhất là phần so sánh cách phát âm giữa Anh-Anh và Anh-Mỹ. Mình chọn chuẩn Anh Mỹ và đã thấy hiệu quả.',
                'status' => 'cho',
                'admin_reply' => null,
            ],
        ];

        foreach ($course4Reviews as $r) {
            $reviewsData[] = array_merge($r, [
                'course_id' => $courseIds[3] ?? 4,
                'created_at' => now()->subDays(rand(1, 15)),
                'updated_at' => now()->subDays(rand(0, 2)),
            ]);
        }

        // ===== Khóa học 5: TOEFL (ID 5) =====
        $course5Reviews = [
            [
                'user_id' => $userIds[2] ?? $userIds[array_rand($userIds)],
                'rating' => 5,
                'content' => 'Mình đạt 105 TOEFL sau khi hoàn thành khóa học! Các chiến thuật làm bài cực kỳ hữu ích, đặc biệt là phần Speaking và Writing. Rất đáng để đầu tư.',
                'status' => 'duyet',
                'admin_reply' => 'Chúc mừng bạn đạt 105 TOEFL! Thành công là nhờ sự kiên trì luyện tập.',
            ],
            [
                'user_id' => $userIds[3] ?? $userIds[array_rand($userIds)],
                'rating' => 4,
                'content' => 'Khóa học toàn diện, bao quát cả 4 kỹ năng. Phần Reading có nhiều bài tập phong phú, Speaking có AI chấm điểm rất chính xác.',
                'status' => 'duyet',
                'admin_reply' => null,
            ],
            [
                'user_id' => $userIds[4] ?? $userIds[array_rand($userIds)],
                'rating' => 5,
                'content' => 'Tài liệu ôn tập rất chất lượng, có cả đề thi thực tế và đáp án chi tiết. Mình đặc biệt thích phần Listening vì tốc độ nghe sát với đề thi thật.',
                'status' => 'duyet',
                'admin_reply' => 'Cảm ơn đánh giá của bạn! Hãy tiếp tục luyện tập để đạt điểm cao hơn nhé.',
            ],
        ];

        foreach ($course5Reviews as $r) {
            $reviewsData[] = array_merge($r, [
                'course_id' => $courseIds[4] ?? 5,
                'created_at' => now()->subDays(rand(1, 10)),
                'updated_at' => now()->subDays(rand(0, 2)),
            ]);
        }

        // ===== Khóa học 6: Tiếng Anh Trung Cấp (ID 6) =====
        $course6Reviews = [
            [
                'user_id' => $userIds[0] ?? $userIds[array_rand($userIds)],
                'rating' => 5,
                'content' => 'Khóa học giúp mình nâng cao từ vựng và ngữ pháp trung cấp rất hiệu quả. Các chủ đề giao tiếp thực tế, phù hợp với người đã có nền tảng cơ bản.',
                'status' => 'duyet',
                'admin_reply' => 'Rất vui khi khóa học giúp bạn tiến bộ! Hãy tiếp tục học nhé.',
            ],
            [
                'user_id' => $userIds[1] ?? $userIds[array_rand($userIds)],
                'rating' => 4,
                'content' => 'Nội dung phong phú, từ vựng theo chủ đề rất dễ nhớ. Ngữ pháp nâng cao được giải thích rõ ràng với nhiều ví dụ. Mình đã cải thiện đáng kể.',
                'status' => 'duyet',
                'admin_reply' => null,
            ],
            [
                'user_id' => $userIds[2] ?? $userIds[array_rand($userIds)],
                'rating' => 5,
                'content' => 'Sau khóa học này mình tự tin hơn rất nhiều khi giao tiếp. Các bài hội thoại mẫu rất thực tế, có thể áp dụng ngay vào công việc và cuộc sống.',
                'status' => 'duyet',
                'admin_reply' => 'Chúc mừng bạn! Thành công đến từ sự nỗ lực không ngừng.',
            ],
            [
                'user_id' => $userIds[3] ?? $userIds[array_rand($userIds)],
                'rating' => 4,
                'content' => 'Mình rất hài lòng với khóa học này. Từ vựng được học trong ngữ cảnh rất dễ nhớ, không còn phải học vẹt nữa. Giá cả hợp lý.',
                'status' => 'duyet',
                'admin_reply' => 'Cảm ơn bạn đã tin tưởng và đánh giá tích cực!',
            ],
            [
                'user_id' => $userIds[4] ?? $userIds[array_rand($userIds)],
                'rating' => 5,
                'content' => 'Đây là khóa học trung cấp tốt nhất mình từng học. Có đầy đủ bài nghe, bài nói, từ vựng và ngữ pháp. AI hỗ trợ 24/7 rất tiện lợi.',
                'status' => 'cho',
                'admin_reply' => null,
            ],
        ];

        foreach ($course6Reviews as $r) {
            $reviewsData[] = array_merge($r, [
                'course_id' => $courseIds[5] ?? 6,
                'created_at' => now()->subDays(rand(1, 8)),
                'updated_at' => now()->subDays(rand(0, 1)),
            ]);
        }

        // ===== Tạo đăng ký (enrollment) cho user-course =====
        // Để reviews hoạt động, user phải được đăng ký khóa học trước
        $enrolledCombos = [];
        foreach ($reviewsData as $review) {
            $key = $review['user_id'] . '-' . $review['course_id'];
            if (!in_array($key, $enrolledCombos)) {
                $enrolledCombos[] = $key;
                \App\Models\UserCourse::updateOrCreate(
                    [
                        'user_id' => $review['user_id'],
                        'course_id' => $review['course_id'],
                    ],
                    [
                        'enrolled_at' => now()->subDays(rand(15, 40)),
                    ]
                );
            }
        }

        // ===== Insert reviews =====
        foreach ($reviewsData as $review) {
            // Tránh trùng lặp review của cùng user cho cùng khóa học
            $exists = Review::where('user_id', $review['user_id'])
                ->where('course_id', $review['course_id'])
                ->exists();

            if (!$exists) {
                Review::create($review);
            }
        }

        $this->command->info('Da tao ' . count($reviewsData) . ' reviews cho ' . count($courses) . ' khoa hoc.');
    }
}
