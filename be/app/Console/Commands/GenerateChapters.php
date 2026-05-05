<?php

namespace App\Console\Commands;

use App\Models\Course;
use App\Models\Chapter;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class GenerateChapters extends Command
{
    protected $signature = 'course:generate-chapters {--course= : ID của khóa học (để trống để tạo cho tất cả)}';
    protected $description = 'Tạo 4 chapters mặc định cho các khóa học chưa có';

    public function handle(): int
    {
        $courseId = $this->option('course');

        $query = Course::query();
        if ($courseId) {
            $query->where('id', $courseId);
        }

        $courses = $query->get();

        if ($courses->isEmpty()) {
            $this->error('Không tìm thấy khóa học nào.');
            return Command::FAILURE;
        }

        $chaptersData = [
            [
                'title' => 'Từ vựng',
                'type' => 'vocabulary',
                'description' => 'Học từ vựng theo chủ đề',
            ],
            [
                'title' => 'Ngữ pháp',
                'type' => 'grammar',
                'description' => 'Học cấu trúc và ngữ pháp',
            ],
            [
                'title' => 'Nghe',
                'type' => 'listening',
                'description' => 'Luyện nghe qua audio',
            ],
            [
                'title' => 'Nói',
                'type' => 'speaking',
                'description' => 'Luyện nói giao tiếp',
            ],
        ];

        $bar = $this->output->createProgressBar($courses->count());
        $bar->start();

        foreach ($courses as $course) {
            // Kiểm tra xem khóa học đã có chapters chưa
            if ($course->chapters()->exists()) {
                $this->line(" - Course #{$course->id}: {$course->title} (đã có chapters, bỏ qua)");
                $bar->advance();
                continue;
            }

            // Tạo 4 chapters
            foreach ($chaptersData as $index => $chapterData) {
                Chapter::create([
                    'course_id' => $course->id,
                    'title' => $chapterData['title'],
                    'slug' => Str::slug($chapterData['type']) . '-' . $course->id,
                    'type' => $chapterData['type'],
                    'description' => $chapterData['description'],
                    'order' => $index + 1,
                    'status' => 'published',
                ]);
            }

            $this->line(" - Course #{$course->id}: {$course->title} (đã tạo chapters)");
            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
        $this->info('Hoàn thành! Đã tạo chapters cho các khóa học.');

        return Command::SUCCESS;
    }
}
