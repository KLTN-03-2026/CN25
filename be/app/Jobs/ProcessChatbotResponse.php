<?php

namespace App\Jobs;

use App\Models\ChatbotMessage;
use App\Models\ExerciseScore;
use App\Models\Lesson;
use App\Models\LessonProgress;
use App\Models\UserCourse;
use App\Services\GeminiService;
use App\Services\OllamaService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class ProcessChatbotResponse implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 2;
    public int $timeout = 60;
    public int $backoff = 15;

    private function isLearningProgressQuery(string $query): bool
    {
        $lower = mb_strtolower($query);
        $progressPatterns = [
            'tiến độ', 'tiendo', 'bài đã', 'đã học', 'học xong',
            'trạng thái học', 'khóa học của tôi', 'khóa đã đăng ký',
            'bài tập đã làm', 'điểm số',
            'của tôi', 'my progress',
            'học đến đâu', 'học bao nhiêu', 'đã học được',
            'đang học', 'đang theo', 'khóa đang học',
            'percent', '%', 'hoàn thành bao nhiêu',
            'điểm trung bình', 'average',
            'yếu', 'chưa giỏi', 'kém', 'yếu phần',
            'nên học gì', 'gợi ý', 'bài tiếp theo',
            'streak', 'chuỗi', 'ngày liên tiếp',
            'tổng', 'tổng cộng',
            'bài chưa học', 'bài còn lại', 'chưa hoàn thành',
            'xem khóa', 'danh sách khóa', 'registered course', 'my enrollment',
        ];
        foreach ($progressPatterns as $pattern) {
            if (str_contains($lower, $pattern)) {
                return true;
            }
        }
        if (preg_match('/(\d+)\s*[\/\.\\|]\s*(\d+)/', $query) || preg_match('/\d+\s*%/', $query)) {
            return true;
        }
        return false;
    }

    private function getConversationHistory(): array
    {
        $messages = ChatbotMessage::where('user_id', $this->userId)
            ->where('id', '!=', $this->chatbotMessageId)
            ->orderBy('created_at', 'desc')
            ->limit(20)
            ->get()
            ->reverse()
            ->values();

        $history = [];
        foreach ($messages as $msg) {
            $history[] = "Người dùng: " . $msg->user_message;
            $history[] = "Trợ lý: " . $msg->bot_response;
        }

        return $history;
    }

    private function getUserLearningContext(): string
    {
        $lines = [];

        $enrolledCourses = UserCourse::where('user_id', $this->userId)
            ->with(['course:id,title,thumbnail', 'course.chapters.lessons'])
            ->get();

        if ($enrolledCourses->isNotEmpty()) {
            $lines[] = "=== CÁC KHÓA HỌC ĐÃ ĐĂNG KÝ ===";
            foreach ($enrolledCourses as $uc) {
                $course = $uc->course;
                if (!$course) continue;
                $totalLessons = $course->chapters->sum(fn($ch) => $ch->lessons->count());
                $chapterIds = $course->chapters->pluck('id')->toArray();
                $completed = 0;
                if (!empty($chapterIds)) {
                    $completed = LessonProgress::where('user_id', $this->userId)
                        ->where('completed', true)
                        ->whereIn('lesson_id', function ($q) use ($chapterIds) {
                            $q->select('id')->from('lessons')->whereIn('chapter_id', $chapterIds);
                        })
                        ->count();
                }
                $percent = $totalLessons > 0 ? round(($completed / $totalLessons) * 100) : 0;
                $status = $uc->status ?? 'active';
                $lines[] = "- {$course->title}: {$completed}/{$totalLessons} bài ({$percent}%), Trạng thái: {$status}";
            }
        }

        $totalCompleted = LessonProgress::where('user_id', $this->userId)->where('completed', true)->count();
        $totalStarted = LessonProgress::where('user_id', $this->userId)->count();
        $totalLessons = Lesson::count();
        $overallPercent = $totalLessons > 0 ? round(($totalCompleted / $totalLessons) * 100) : 0;

        $lines[] = "\n=== TỔNG QUAN ===";
        $lines[] = "- Tổng bài đã hoàn thành: {$totalCompleted}/{$totalLessons} ({$overallPercent}%)";
        $lines[] = "- Tổng bài đã bắt đầu: {$totalStarted}";

        $scores = ExerciseScore::where('user_id', $this->userId)->get();
        if ($scores->isNotEmpty()) {
            $avgScore = round($scores->avg('percentage'), 1);
            $passCount = $scores->where('passed', true)->count();
            $failCount = $scores->where('passed', false)->count();
            $bestScore = round($scores->max('percentage'), 1);
            $totalAttempts = $scores->count();
            $totalTimeSpent = round($scores->sum('time_spent') / 60);

            $lines[] = "\n=== ĐIỂM SỐ & THỐNG KÊ ===";
            $lines[] = "- Số bài đã làm: {$totalAttempts}";
            $lines[] = "- Điểm trung bình: {$avgScore}/100";
            $lines[] = "- Điểm cao nhất: {$bestScore}/100";
            $lines[] = "- Đạt: {$passCount}, Chưa đạt: {$failCount}";
            $lines[] = "- Tổng thời gian học: {$totalTimeSpent} phút";

            $typeScores = $scores->groupBy('type')->map(fn($group) => [
                'count' => $group->count(),
                'avg' => round($group->avg('percentage'), 1),
            ]);

            if ($typeScores->isNotEmpty()) {
                $weakTypes = $typeScores->filter(fn($data) => $data['avg'] < 60)->sortBy('avg');
                $strongTypes = $typeScores->filter(fn($data) => $data['avg'] >= 80)->sortByDesc('avg');
                if ($weakTypes->isNotEmpty()) {
                    $lines[] = "- ĐIỂM YẾU: " . $weakTypes->keys()->map(fn($t) => "{$t} ({$weakTypes[$t]['avg']}%)")->implode(', ');
                }
                if ($strongTypes->isNotEmpty()) {
                    $lines[] = "- ĐIỂM MẠNH: " . $strongTypes->keys()->map(fn($t) => "{$t} ({$strongTypes[$t]['avg']}%)")->implode(', ');
                }
            }
        } else {
            $lines[] = "\n=== ĐIỂM SỐ ===";
            $lines[] = "- Chưa có bài kiểm tra nào.";
        }

        $inProgress = LessonProgress::where('user_id', $this->userId)
            ->where('completed', false)
            ->where('score', '>', 0)
            ->with('lesson:id,title')
            ->orderByDesc('score')
            ->limit(3)
            ->get();

        if ($inProgress->isNotEmpty()) {
            $lines[] = "\n=== BÀI ĐANG HỌC (chưa hoàn thành) ===";
            foreach ($inProgress as $lp) {
                $lesson = $lp->lesson;
                if (!$lesson) continue;
                $lines[] = "- {$lesson->title} (điểm: {$lp->score})";
            }
        }

        $text = implode("\n", $lines);
        return empty($text) ? '' : "TIẾN ĐỘ HỌC TẬP:\n" . $text;
    }

    public function __construct(
        public int $userId,
        public string $userMessage,
        public int $chatbotMessageId,
        public array $context,
        public ?int $lessonId = null,
        public ?string $cacheKey = null,
    ) {}

    public function handle(GeminiService $geminiService, OllamaService $ollamaService): void
    {
        $history = $this->getConversationHistory();
        $historyText = !empty($history) ? "Lịch sử hội thoại gần đây:\n" . implode("\n", $history) . "\n\n" : "";

        if ($this->isLearningProgressQuery($this->userMessage)) {
            $learningContext = $this->getUserLearningContext();
            if (!empty($learningContext)) {
                $this->context = array_merge([$learningContext], $this->context);
            }
        }

        $prompt = $historyText . "Câu hỏi hiện tại: " . $this->userMessage;

        $result = $geminiService->generateResponse($prompt, $this->context);

        if (!$result['success']) {
            Log::warning("Queue: Gemini failed, trying Ollama fallback");
            $result = $ollamaService->generateResponse($prompt, $this->context);
        }

        if (!$result['success']) {
            $this->failed($result['error'] ?? 'Unknown error');
            return;
        }

        $message = ChatbotMessage::find($this->chatbotMessageId);
        if ($message) {
            $message->bot_response = $result['response'];
            $message->save();
        }

        if ($this->cacheKey) {
            Cache::put($this->cacheKey, [
                'response' => $result['response'],
                'source_type' => $message->source_type ?? null,
                'source_id' => $message->source_id ?? null,
                'relevance_score' => $message->relevance_score ?? null,
            ], now()->addHours(24));
        }

        Log::info("ChatbotJob completed for user {$this->userId}");
    }

    public function failed($error): void
    {
        $message = ChatbotMessage::find($this->chatbotMessageId);
        if ($message) {
            $message->bot_response = 'Xin lỗi, mình đang gặp sự cố. Vui lòng thử lại sau nhé.';
            $message->save();
        }

        Log::error("ChatbotJob failed: {$error}");
    }
}
