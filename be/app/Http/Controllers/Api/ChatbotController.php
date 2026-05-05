<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ChatbotMessage;
use App\Models\ContentChunk;
use App\Models\Grammar;
use App\Models\Vocabulary;
use App\Models\Lesson;
use App\Models\Course;
use App\Models\Chapter;
use App\Models\Listening;
use App\Models\LessonProgress;
use App\Models\UserCourse;
use App\Models\ExerciseScore;
use App\Services\GeminiService;
use App\Services\OllamaService;
use App\Jobs\ProcessChatbotResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class ChatbotController extends Controller
{
    private GeminiService $geminiService;
    private OllamaService $ollamaService;

    private const CACHE_TTL_HOURS = 24;
    private const QUERY_EMBEDDING_TTL_HOURS = 6;
    private const MAX_CONTEXT_TOKENS = 4000;
    private const MAX_CHUNKS_LESSON = 3;
    private const MAX_CHUNKS_GLOBAL = 3;

    public function __construct(GeminiService $geminiService, OllamaService $ollamaService)
    {
        $this->geminiService = $geminiService;
        $this->ollamaService = $ollamaService;
    }

    public function chat(Request $request)
    {
        $validated = $request->validate([
            'message' => 'required|string|min:1|max:1000',
            'lesson_id' => 'nullable|integer|exists:lessons,id',
        ]);

        $userId = Auth::id();
        $userMessage = trim($validated['message']);
        $lessonId = $validated['lesson_id'] ?? null;

        $cacheKey = $this->getCacheKey($userId, $userMessage, $lessonId);
        $cached = Cache::get($cacheKey);
        if ($cached) {
            Log::info("Chatbot cache hit for user {$userId}");
            return response()->json([
                'message' => 'OK',
                'data' => $cached,
                'cached' => true,
            ]);
        }

        $context = [];
        $sourceType = null;
        $sourceId = null;
        $relevanceScore = null;

        if ($lessonId) {
            $contextData = $this->getContextFromLesson($lessonId, $userMessage);
            if (!empty($contextData['chunks'])) {
                $context = $contextData['chunks'];
                $sourceType = 'lesson';
                $sourceId = $lessonId;
                $relevanceScore = $contextData['max_similarity'];
            }
        }

        if (empty($context)) {
            $globalContext = $this->getGlobalContext($userMessage);
            $context = $globalContext['chunks'];
            $relevanceScore = $globalContext['max_similarity'];
        }

        if (empty($context)) {
            $directContext = $this->getDirectDbContext($userMessage);
            if (!empty($directContext)) {
                $context = $directContext;
                $sourceType = 'database';
                $relevanceScore = 0.8;
            }
        }

        if ($this->isLearningProgressQuery($userMessage)) {
            $learningContext = $this->getUserLearningContext();
            if (!empty($learningContext)) {
                $context = array_merge([$learningContext], $context);
            }
        }

        $context = $this->trimContextToTokenLimit($context);

        $history = $this->getConversationHistory($userId);
        $historyText = !empty($history) ? "Lịch sử hội thoại gần đây:\n" . implode("\n", $history) . "\n\n" : "";

        $prompt = $historyText . "Câu hỏi hiện tại: " . $userMessage;

        $result = $this->geminiService->generateResponse($prompt, $context);

        if (!$result['success']) {
            Log::warning("Gemini failed, trying Ollama fallback");
            $result = $this->ollamaFallback($prompt, $context);
        }

        if (!$result['success']) {
            return response()->json([
                'message' => 'Lỗi khi xử lý câu hỏi',
                'error' => $result['error'] ?? 'Unknown error',
            ], 500);
        }

        $responseData = [
            'response' => $result['response'],
            'source_type' => $sourceType,
            'source_id' => $sourceId,
            'relevance_score' => $relevanceScore,
        ];

        Cache::put($cacheKey, $responseData, now()->addHours(self::CACHE_TTL_HOURS));

        ChatbotMessage::create([
            'user_id' => $userId,
            'user_message' => $userMessage,
            'bot_response' => $result['response'],
            'source_type' => $sourceType,
            'source_id' => $sourceId,
            'relevance_score' => $relevanceScore,
        ]);

        return response()->json([
            'message' => 'OK',
            'data' => $responseData,
        ]);
    }

    private function isLearningProgressQuery(string $query): bool
    {
        $lower = mb_strtolower($query);

        $progressPatterns = [
            'tiến độ', 'tiendo', 'bài đã', 'đã học', 'học xong',
            'trạng thái học', 'khóa học của tôi', 'khóa đã đăng ký',
            'dang ki', 'dang ky', 'dangkí', 'danky',
            'dang ki', 'dang kí', 'dang ký',
            'đã đăng kí', 'đã đăng ký', 'da dang ki', 'da dang ky',
            'enrolled', 'bài tập đã làm', 'score', 'điểm số',
            'của tôi', 'của bạn', 'my course', 'my progress',
            'học đến đâu', 'học bao nhiêu', 'đã học được',
            'đang học', 'đang theo', 'khóa đang học',
            'percent', '%', 'hoàn thành bao nhiêu',
            'điểm trung bình', 'điểm tb', 'average',
            'yếu', 'chưa giỏi', 'kém', 'yếu phần',
            'nên học gì', 'gợi ý', 'bài tiếp theo', 'next lesson',
            'streak', 'chuỗi', 'ngày liên tiếp',
            'total', 'tổng', 'tổng cộng',
            'thứ hạng', 'ranking', 'bảng xếp hạng',
            'bài chưa học', 'bài còn lại', 'chưa hoàn thành',
            'xem khóa', 'danh sách khóa', 'registered course', 'my enrollment',
            'học được bao nhiêu', 'bao nhiêu khóa', 'bao nhiêu bài',
            'khóa nào', 'bài nào', 'theo khóa nào', 'đăng ký khóa nào',
            'những khóa', 'những bài', 'course nào', 'lessons nào',
            'tổng quan khóa', 'tổng quan học', 'học tập của tôi',
        ];

        foreach ($progressPatterns as $pattern) {
            if (str_contains($lower, $pattern)) {
                return true;
            }
        }

        if (preg_match('/(\d+)\s*[\/\.\\|]\s*(\d+)/', $query)) {
            return true;
        }

        if (preg_match('/\d+\s*%/', $query)) {
            return true;
        }

        return false;
    }

    private function ollamaFallback(string $prompt, array $context): array
    {
        if (!config('services.ollama.enabled')) {
            return [
                'success' => false,
                'error' => 'Gemini tạm thời không khả dụng và Ollama chưa được bật.',
            ];
        }

        if (!$this->ollamaService->isAvailable()) {
            return [
                'success' => false,
                'error' => 'Gemini và Ollama đều không khả dụng. Vui lòng thử lại sau.',
            ];
        }

        return $this->ollamaService->generateResponse($prompt, $context);
    }

    private function getCacheKey(int $userId, string $message, ?int $lessonId): string
    {
        $hash = hash('sha256', mb_strtolower($message) . '|' . ($lessonId ?? 'global'));
        return "chatbot_response:{$userId}:{$hash}";
    }

    public function chatAsync(Request $request)
    {
        $validated = $request->validate([
            'message' => 'required|string|min:1|max:1000',
            'lesson_id' => 'nullable|integer|exists:lessons,id',
        ]);

        $userId = Auth::id();
        $userMessage = trim($validated['message']);
        $lessonId = $validated['lesson_id'] ?? null;

        $cacheKey = $this->getCacheKey($userId, $userMessage, $lessonId);
        $cached = Cache::get($cacheKey);
        if ($cached) {
            return response()->json([
                'message' => 'OK',
                'data' => $cached,
                'cached' => true,
            ]);
        }

        $context = [];
        $sourceType = null;
        $sourceId = null;
        $relevanceScore = null;

        if ($lessonId) {
            $contextData = $this->getContextFromLesson($lessonId, $userMessage);
            if (!empty($contextData['chunks'])) {
                $context = $contextData['chunks'];
                $sourceType = 'lesson';
                $sourceId = $lessonId;
                $relevanceScore = $contextData['max_similarity'];
            }
        }

        if (empty($context)) {
            $globalContext = $this->getGlobalContext($userMessage);
            $context = $globalContext['chunks'];
            $relevanceScore = $globalContext['max_similarity'];
        }

        if (empty($context)) {
            $directContext = $this->getDirectDbContext($userMessage);
            if (!empty($directContext)) {
                $context = $directContext;
                $sourceType = 'database';
                $relevanceScore = 0.8;
            }
        }

        if ($this->isLearningProgressQuery($userMessage)) {
            $learningContext = $this->getUserLearningContext();
            if (!empty($learningContext)) {
                $context = array_merge([$learningContext], $context);
            }
        }

        $context = $this->trimContextToTokenLimit($context);

        $placeholderMessage = ChatbotMessage::create([
            'user_id' => $userId,
            'user_message' => $userMessage,
            'bot_response' => 'Đang xử lý...',
            'source_type' => $sourceType,
            'source_id' => $sourceId,
            'relevance_score' => $relevanceScore,
        ]);

        ProcessChatbotResponse::dispatch(
            $userId,
            $userMessage,
            $placeholderMessage->id,
            $context,
            $lessonId,
            $cacheKey,
        );

        return response()->json([
            'message' => 'OK',
            'data' => [
                'response' => 'Đang xử lý câu trả lời...',
                'source_type' => $sourceType,
                'source_id' => $sourceId,
                'relevance_score' => $relevanceScore,
                'pending' => true,
                'message_id' => $placeholderMessage->id,
            ],
        ]);
    }

    private function getConversationHistory(int $userId, int $limit = 10): array
    {
        $messages = ChatbotMessage::where('user_id', $userId)
            ->orderBy('created_at', 'desc')
            ->limit($limit * 2)
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

    public function history(Request $request)
    {
        $messages = ChatbotMessage::where('user_id', Auth::id())
            ->orderBy('created_at', 'desc')
            ->limit(50)
            ->get()
            ->reverse()
            ->values();

        return response()->json([
            'message' => 'OK',
            'data' => $messages,
        ]);
    }

    public function clearHistory(Request $request)
    {
        ChatbotMessage::where('user_id', Auth::id())->delete();

        return response()->json([
            'message' => 'Đã xóa lịch sử chat',
        ]);
    }

    public function checkStatus(Request $request)
    {
        $hasApiKey = !empty(config('gemini.api_key'));
        $chunkCount = ContentChunk::embedded()->count();

        return response()->json([
            'message' => 'OK',
            'data' => [
                'gemini_configured' => $hasApiKey,
                'total_embedded_chunks' => $chunkCount,
            ],
        ]);
    }

    private function getContextFromLesson(int $lessonId, string $query): array
    {
        $queryEmbedding = $this->getCachedQueryEmbedding($query);
        if (!$queryEmbedding) {
            return $this->getFallbackContext($lessonId);
        }

        $lesson = Lesson::with([
            'grammars',
            'vocabularies',
            'listenings',
            'speakingExercises',
            'chapter.course',
        ])->find($lessonId);

        if (!$lesson) {
            return [];
        }

        $chunks = ContentChunk::where('content_type', 'lesson')
            ->where('content_id', $lessonId)
            ->where('is_embedded', true)
            ->get();

        if ($chunks->isEmpty()) {
            $this->embedLessonContent($lesson);
            $chunks = ContentChunk::where('content_type', 'lesson')
                ->where('content_id', $lessonId)
                ->where('is_embedded', true)
                ->get();
        }

        $scored = [];
        foreach ($chunks as $chunk) {
            if (!$chunk->embedding) {
                continue;
            }
            $similarity = $this->geminiService->cosineSimilarity($queryEmbedding, $chunk->embedding);
            if ($similarity >= $this->geminiService->getSimilarityThreshold()) {
                $scored[] = [
                    'chunk' => $chunk,
                    'similarity' => $similarity,
                ];
            }
        }

        usort($scored, fn($a, $b) => $b['similarity'] <=> $a['similarity']);

        $topChunks = array_slice($scored, 0, self::MAX_CHUNKS_LESSON);

        if (empty($topChunks)) {
            return $this->getFallbackContext($lessonId);
        }

        $maxSimilarity = $topChunks[0]['similarity'] ?? 0;

        return [
            'chunks' => array_map(fn($item) => $item['chunk']->chunk_text, $topChunks),
            'max_similarity' => $maxSimilarity,
        ];
    }

    private function getFallbackContext(int $lessonId): array
    {
        $lesson = Lesson::with(['grammars', 'vocabularies', 'chapter.course'])->find($lessonId);
        if (!$lesson) {
            return [];
        }

        $contextParts = [];

        if ($lesson->grammars->isNotEmpty()) {
            foreach ($lesson->grammars as $grammar) {
                $text = "NGỮ PHÁP: " . ($grammar->title ?? 'Bài ngữ pháp') . "\n" . ($grammar->structure ?? '') . "\n" . ($grammar->explanation ?? '');
                if (mb_strlen(trim($text)) > 30) {
                    $contextParts[] = $text;
                }
            }
        }

        if ($lesson->vocabularies->isNotEmpty()) {
            $vocabList = $lesson->vocabularies->take(10)->map(fn($v) => "- {$v->word}: {$v->meaning}")->toArray();
            if (!empty($vocabList)) {
                $contextParts[] = "TỪ VỰNG:\n" . implode("\n", $vocabList);
            }
        }

        return [
            'chunks' => $contextParts,
            'max_similarity' => 0.5,
        ];
    }

    private function getGlobalContext(string $query): array
    {
        $queryEmbedding = $this->getCachedQueryEmbedding($query);
        if (!$queryEmbedding) {
            return ['chunks' => [], 'max_similarity' => 0];
        }

        $embeddedChunks = ContentChunk::where('is_embedded', true)->get();

        if ($embeddedChunks->isEmpty()) {
            return ['chunks' => [], 'max_similarity' => 0];
        }

        $scored = [];
        foreach ($embeddedChunks as $chunk) {
            if (!$chunk->embedding) {
                continue;
            }
            $similarity = $this->geminiService->cosineSimilarity($queryEmbedding, $chunk->embedding);
            if ($similarity >= $this->geminiService->getSimilarityThreshold()) {
                $scored[] = [
                    'chunk' => $chunk,
                    'similarity' => $similarity,
                ];
            }
        }

        usort($scored, fn($a, $b) => $b['similarity'] <=> $a['similarity']);

        $topChunks = array_slice($scored, 0, self::MAX_CHUNKS_GLOBAL);
        $maxSimilarity = !empty($topChunks) ? $topChunks[0]['similarity'] : 0;

        return [
            'chunks' => array_map(fn($item) => $item['chunk']->chunk_text, $topChunks),
            'max_similarity' => $maxSimilarity,
        ];
    }

    private function getDirectDbContext(string $query): array
    {
        $queryLower = mb_strtolower($query);

        $context = [];

        if ($this->matchesKeyword($queryLower, ['khóa học', 'course', 'danh sách', 'có những', 'học gì', 'những bài', 'bài nào'])) {
            $courses = Course::where('status', 'published')
                ->with(['chapters' => function ($q) {
                    $q->where('status', 'published')->with(['lessons' => function ($l) {
                        $l->where('status', 'published')->select('id', 'chapter_id', 'title', 'type');
                    }]);
                }])
                ->select('id', 'title', 'slug', 'description', 'price', 'thumbnail')
                ->get();

            if ($courses->isNotEmpty()) {
                $courseList = $courses->map(function ($course) {
                    $lessonCount = $course->chapters->sum(fn($ch) => $ch->lessons->count());
                    $chapterCount = $course->chapters->count();
                    return "- {$course->title} ({$chapterCount} chương, {$lessonCount} bài học)";
                })->toArray();

                $context[] = "DANH SÁCH KHÓA HỌC TRÊN HỆ THỐNG:\n" . implode("\n", $courseList);
            }
        }

        return $context;
    }

    private function getCachedQueryEmbedding(string $query): ?array
    {
        $cacheKey = 'query_emb:' . hash('sha256', mb_strtolower($query));
        $cached = Cache::get($cacheKey);
        if ($cached) {
            return $cached;
        }

        $embedding = $this->geminiService->getEmbedding($query);
        if ($embedding) {
            Cache::put($cacheKey, $embedding, now()->addHours(self::QUERY_EMBEDDING_TTL_HOURS));
        }

        return $embedding;
    }

    private function trimContextToTokenLimit(array $context, float $maxTokens = self::MAX_CONTEXT_TOKENS): array
    {
        $totalChars = array_reduce($context, fn($sum, $text) => $sum + mb_strlen($text), 0);
        $estimatedTokens = $totalChars / 4;

        if ($estimatedTokens <= $maxTokens) {
            return $context;
        }

        $scale = $maxTokens / $estimatedTokens;
        $targetChars = (int)($totalChars * $scale);
        $result = [];
        $currentChars = 0;

        foreach ($context as $text) {
            if ($currentChars >= $targetChars) {
                break;
            }
            $result[] = $text;
            $currentChars += mb_strlen($text);
        }

        return $result;
    }

    private function matchesKeyword(string $query, array $keywords): bool
    {
        foreach ($keywords as $keyword) {
            if (str_contains($query, $keyword)) {
                return true;
            }
        }
        return false;
    }

    private function embedLessonContent(Lesson $lesson): void
    {
        if (ContentChunk::where('content_type', 'lesson')->where('content_id', $lesson->id)->where('is_embedded', true)->exists()) {
            return;
        }

        $chunks = [];

        foreach ($lesson->grammars ?? [] as $grammar) {
            $text = ($grammar->title ?? '') . ". " . ($grammar->structure ?? '') . " " . ($grammar->explanation ?? '');
            $text = trim($text);
            if (mb_strlen($text) < 20) {
                continue;
            }
            $textChunks = $this->geminiService->chunkText($text, $grammar->title ?? 'Ngữ pháp');
            foreach ($textChunks as $idx => $chunkText) {
                $chunks[] = [
                    'content_type' => 'grammar',
                    'content_id' => $grammar->id,
                    'title' => $grammar->title,
                    'chunk_text' => $chunkText,
                    'chunk_index' => $idx,
                ];
            }
        }

        foreach ($lesson->vocabularies ?? [] as $vocab) {
            $text = "TỪ: {$vocab->word}. PHIÊN ÂM: {$vocab->pronunciation}. NGHĨA: {$vocab->meaning}. VÍ DỤ: {$vocab->example}";
            $text = trim($text);
            if (mb_strlen($text) < 10) {
                continue;
            }
            $chunks[] = [
                'content_type' => 'vocabulary',
                'content_id' => $vocab->id,
                'title' => $vocab->word,
                'chunk_text' => $text,
                'chunk_index' => 0,
            ];
        }

        foreach ($chunks as $chunkData) {
            $embedding = $this->geminiService->getEmbedding($chunkData['chunk_text']);
            ContentChunk::create([
                'content_type' => $chunkData['content_type'],
                'content_id' => $chunkData['content_id'],
                'title' => $chunkData['title'],
                'chunk_text' => $chunkData['chunk_text'],
                'embedding' => $embedding,
                'chunk_index' => $chunkData['chunk_index'],
                'is_embedded' => !is_null($embedding),
            ]);
        }

        $lessonText = "BÀI HỌC: " . ($lesson->title ?? '') . ". " . ($lesson->description ?? '') . " " . ($lesson->content ?? '');
        $lessonText = trim($lessonText);
        if (mb_strlen($lessonText) > 20) {
            $lessonChunks = $this->geminiService->chunkText($lessonText, $lesson->title ?? 'Bài học');
            foreach ($lessonChunks as $idx => $chunkText) {
                $embedding = $this->geminiService->getEmbedding($chunkText);
                ContentChunk::create([
                    'content_type' => 'lesson',
                    'content_id' => $lesson->id,
                    'title' => $lesson->title,
                    'chunk_text' => $chunkText,
                    'embedding' => $embedding,
                    'chunk_index' => $idx,
                    'is_embedded' => !is_null($embedding),
                ]);
            }
        }
    }

    private function getUserLearningContext(): string
    {
        $userId = Auth::id();
        if (!$userId) {
            return '';
        }

        $lines = [];

        // === KHÓA HỌC ĐÃ ĐĂNG KÝ ===
        $enrolledCourses = UserCourse::where('user_id', $userId)
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
                    $completed = LessonProgress::where('user_id', $userId)
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

        // === TỔNG QUAN HỌC TẬP ===
        $totalCompleted = LessonProgress::where('user_id', $userId)->where('completed', true)->count();
        $totalStarted = LessonProgress::where('user_id', $userId)->count();
        $totalLessons = Lesson::count();

        $overallPercent = $totalLessons > 0 ? round(($totalCompleted / $totalLessons) * 100) : 0;
        $lines[] = "\n=== TỔNG QUAN ===";
        $lines[] = "- Tổng bài đã hoàn thành: {$totalCompleted}/{$totalLessons} ({$overallPercent}%)";
        $lines[] = "- Tổng bài đã bắt đầu: {$totalStarted}";

        // === ĐIỂM SỐ & THỐNG KÊ ===
        $scores = ExerciseScore::where('user_id', $userId)->get();
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

            // Phân tích điểm yếu dựa trên loại bài tập
            $typeScores = $scores->groupBy('type')->map(function ($group) {
                return [
                    'count' => $group->count(),
                    'avg' => round($group->avg('percentage'), 1),
                ];
            });

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

        // === BÀI GẦN HOÀN THÀNH NHẤT ===
        $inProgress = LessonProgress::where('user_id', $userId)
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
}
