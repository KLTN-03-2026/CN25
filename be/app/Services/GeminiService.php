<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GeminiService
{
    private string $apiKey;
    private string $model;
    private string $embeddingModel;
    private string $apiVersion;
    private int $maxTokens;
    private float $temperature;
    private int $embeddingDimension;
    private int $chunkSize;
    private int $chunkOverlap;
    private int $maxChunksRetrieve;
    private float $similarityThreshold;

    public function __construct()
    {
        $this->apiKey = config('gemini.api_key');
        $this->model = config('gemini.model');
        $this->embeddingModel = config('gemini.embedding_model');
        $this->apiVersion = config('gemini.api_version', 'v1beta');
        $this->maxTokens = config('gemini.max_tokens');
        $this->temperature = config('gemini.temperature');
        $this->embeddingDimension = config('gemini.embedding_dimension');
        $this->chunkSize = config('gemini.chunk_size');
        $this->chunkOverlap = config('gemini.chunk_overlap');
        $this->maxChunksRetrieve = config('gemini.max_chunks_retrieve');
        $this->similarityThreshold = config('gemini.similarity_threshold');
    }

    public function generateResponse(string $prompt, array $context = []): array
    {
        $systemPrompt = $this->buildSystemPrompt($context);
        $fullPrompt = $systemPrompt . "\n\n" . $prompt;

        $maxRetries = 3;
        $lastError = null;

        for ($attempt = 1; $attempt <= $maxRetries; $attempt++) {
            try {
                $url = "https://generativelanguage.googleapis.com/{$this->apiVersion}/models/{$this->model}:generateContent?key={$this->apiKey}";

                $response = Http::timeout(30)->withHeaders([
                    'Content-Type' => 'application/json',
                ])->post($url, [
                    'contents' => [
                        'parts' => [
                            ['text' => $fullPrompt]
                        ]
                    ],
                    'generationConfig' => [
                        'maxOutputTokens' => $this->maxTokens,
                        'temperature' => $this->temperature,
                        'topP' => config('gemini.top_p'),
                        'topK' => config('gemini.top_k'),
                    ],
                    'safetySettings' => [
                        ['category' => 'HARM_CATEGORY_HARASSMENT', 'threshold' => 'BLOCK_NONE'],
                        ['category' => 'HARM_CATEGORY_HATE_SPEECH', 'threshold' => 'BLOCK_NONE'],
                        ['category' => 'HARM_CATEGORY_SEXUALLY_EXPLICIT', 'threshold' => 'BLOCK_NONE'],
                        ['category' => 'HARM_CATEGORY_DANGEROUS_CONTENT', 'threshold' => 'BLOCK_NONE'],
                    ],
                ]);

                if ($response->status() === 503) {
                    $lastError = 'Gemini đang quá tải. Vui lòng đợi vài phút rồi thử lại.';
                    Log::warning("Gemini 503 attempt {$attempt}/{$maxRetries}");
                    usleep(500000 * $attempt);
                    continue;
                }

                if ($response->status() === 429) {
                    $lastError = 'Đã vượt giới hạn request Gemini. Vui lòng đợi một lát.';
                    Log::warning("Gemini 429 attempt {$attempt}/{$maxRetries}");
                    usleep(1000000 * $attempt);
                    continue;
                }

                if (!$response->successful()) {
                    $errorBody = $response->body();
                    Log::error('Gemini API error: ' . $errorBody);
                    $parsed = json_decode($errorBody, true);
                    $msg = $parsed['error']['message'] ?? 'Lỗi không xác định';
                    return [
                        'success' => false,
                        'error' => "Lỗi Gemini: {$msg}",
                        'status' => $response->status(),
                    ];
                }

                $data = $response->json();
                $text = $data['candidates'][0]['content']['parts'][0]['text'] ?? null;

                if (!$text) {
                    return [
                        'success' => false,
                        'error' => 'Không nhận được phản hồi từ Gemini.',
                    ];
                }

                return [
                    'success' => true,
                    'response' => $text,
                ];
            } catch (\Exception $e) {
                $lastError = 'Lỗi kết nối: ' . $e->getMessage();
                Log::error('Gemini exception: ' . $e->getMessage());
            }
        }

        return [
            'success' => false,
            'error' => $lastError ?? 'Gemini tạm thời không khả dụng. Vui lòng thử lại sau.',
        ];
    }

    public function getEmbedding(string $text): ?array
    {
        $maxRetries = 3;
        $lastError = null;

        for ($attempt = 1; $attempt <= $maxRetries; $attempt++) {
            try {
                $url = "https://generativelanguage.googleapis.com/v1beta/models/{$this->embeddingModel}:embedContent?key={$this->apiKey}";

                $response = Http::timeout(30)->withHeaders([
                    'Content-Type' => 'application/json',
                ])->post($url, [
                    'model' => "models/{$this->embeddingModel}",
                    'content' => [
                        'parts' => [
                            ['text' => $text]
                        ]
                    ],
                    'taskType' => 'RETRIEVAL_DOCUMENT',
                ]);

                if ($response->status() === 503) {
                    Log::warning("Embedding 503 attempt {$attempt}/{$maxRetries}");
                    usleep(500000 * $attempt);
                    continue;
                }

                if (!$response->successful()) {
                    Log::error('Gemini Embedding error: ' . $response->body());
                    return null;
                }

                $data = $response->json();
                return $data['embedding']['values'] ?? null;
            } catch (\Exception $e) {
                Log::error('Gemini Embedding exception: ' . $e->getMessage());
                $lastError = $e->getMessage();
            }
        }

        Log::error('Gemini Embedding failed after retries: ' . $lastError);
        return null;
    }

    public function cosineSimilarity(array $vecA, array $vecB): float
    {
        if (count($vecA) !== count($vecB)) {
            return 0.0;
        }

        $dotProduct = 0.0;
        $normA = 0.0;
        $normB = 0.0;

        for ($i = 0; $i < count($vecA); $i++) {
            $dotProduct += $vecA[$i] * $vecB[$i];
            $normA += $vecA[$i] * $vecA[$i];
            $normB += $vecB[$i] * $vecB[$i];
        }

        $normA = sqrt($normA);
        $normB = sqrt($normB);

        if ($normA == 0 || $normB == 0) {
            return 0.0;
        }

        return $dotProduct / ($normA * $normB);
    }

    public function chunkText(string $text, string $title = ''): array
    {
        $chunks = [];
        $prefix = $title ? $title . ". " : "";

        if (mb_strlen($text) <= $this->chunkSize) {
            return [$prefix . $text];
        }

        $sentences = $this->splitIntoSentences($text);
        $currentChunk = "";

        foreach ($sentences as $sentence) {
            if (mb_strlen($currentChunk) + mb_strlen($sentence) <= $this->chunkSize) {
                $currentChunk .= $sentence . " ";
            } else {
                if (!empty(trim($currentChunk))) {
                    $chunks[] = $prefix . trim($currentChunk);
                }
                $currentChunk = mb_substr($currentChunk, -$this->chunkOverlap);
                if (mb_strlen($currentChunk) > $this->chunkOverlap) {
                    $currentChunk = "";
                }
                $currentChunk .= $sentence . " ";
            }
        }

        if (!empty(trim($currentChunk))) {
            $chunks[] = $prefix . trim($currentChunk);
        }

        return $chunks;
    }

    private function splitIntoSentences(string $text): array
    {
        $text = preg_replace('/([.!?])\s+/u', '$1|', $text);
        $sentences = explode('|', $text);
        return array_filter($sentences, fn($s) => mb_strlen(trim($s)) > 10);
    }

    private function buildSystemPrompt(array $context): string
    {
        $system = <<<'PROMPT'
Bạn là trợ lý dạy tiếng Anh thân thiện cho học viên Việt Nam trên DTU LingoAI.

=== QUY TẮC BẮT BUỘC ===
1. Trả lời bằng tiếng Việt, NGẮN GỌN, ĐÚNG TRỌNG TÂM, dùng gạch đầu dòng. Không dùng emoji.
2. Trả lời NHƯ CON NGƯỜI - tự nhiên, thân thiện, không phải bài giảng.
3. Nếu câu hỏi không liên quan đến tiếng Anh, học tập, hoặc khóa học → từ chối lịch sự.
4. Về TIẾN ĐỘ HỌC TẬP: dựa vào thông tin trong NỘI DUNG LIÊN QUAN, nếu thấy 'TIẾN ĐỘ HỌC TẬP', hãy phân tích và trả lời cụ thể.
5. Về KHÓA HỌC/BÀI HỌC: trả lời chi tiết dựa trên nội dung có sẵn, những khóa học chưa có bài vẫn tính.
6. Về KIẾN THỨC TIẾNG ANH: nếu không có nội dung bài học, vẫn trả lời dựa trên kiến thức chung của bạn.
7. Chỉ TỪ CHỐI nếu câu hỏi hoàn toàn không liên quan (ví dụ: thời tiết, tin tức chính trị, nấu ăn...).
=== CÁCH PHÂN BIỆT CÂU HỎI ===
A) CHÀO HỎI / TÌNH CẢM / CHUNG CHUNG → trả lời tự nhiên như trợ lý thân thiện, KHÔNG biến thành bài ngữ pháp:
   - "xin chào" → "Chào bạn! Mình là trợ lý học tiếng Anh của DTU LingoAI. Cần mình giúp gì không?"
   - "bạn là ai" → "Mình là trợ lý AI ở đây để hỗ trợ bạn học tiếng Anh!"
   - "how are you" → "Mình khỏe, cảm ơn bạn! Bạn cần hỗ trợ gì hôm nay?"
   - "hôm nay thế nào" → trả lời bình thường, hỏi thăm lại

B) KIẾN THỨC TIẾNG ANH CỤ THỂ → trả lời có cấu trúc:
   - TỪ VỰNG: giải thích nghĩa, phiên âm, ví dụ ngắn
   - NGỮ PHÁP / THÌ: đưa công thức, cách dùng, dấu hiệu nhận biết
   - KỸ NĂNG: reading, writing, speaking, listening - mẹo
   - CẤU TRÚC CÂU: giải thích cách ghép từ, giới từ, chia động từ

C) TIẾN ĐỘ HỌC TẬP CÁ NHÂN → dùng TIẾN ĐỘ HỌC TẬP để trả lời cá nhân hóa:
   - KHÔNG HỎI lại thông tin đăng nhập, KHÔNG xin email
   - Dữ liệu đã được cung cấp sẵn trong phần TIẾN ĐỘ HỌC TẬP bên dưới
   - Trả lời trực tiếp dựa trên dữ liệu có sẵn
   - Nếu phần TIẾN ĐỘ HỌC TẬP trống → thông báo "Bạn chưa đăng ký khóa học nào"
   - Khi hỏi về KHÓA HỌC ĐÃ ĐĂNG KÝ: liệt kê rõ tên khóa, số bài đã học/tổng số bài, % hoàn thành, trạng thái
   - Khi hỏi về TỔNG QUAN: tổng bài hoàn thành, tổng bài đã bắt đầu, so với tổng số bài trên hệ thống
   - Khi hỏi về ĐIỂM SỐ: trình bày điểm trung bình, điểm cao nhất, số bài đạt/chưa đạt, tổng thời gian học
   - Khi hỏi về BÀI ĐANG HỌC: cho biết tên bài, khóa học chứa bài đó

D) CÂU HỎI KHÔNG RÕ → hỏi lại: "Bạn muốn hỏi về chủ đề gì nhỉ?"

=== VÍ DỤ ĐÚNG ===
Q: "xin chào" → "Chào bạn! Rất vui được hỗ trợ bạn học tiếng Anh. Mình có thể giúp gì cho bạn hôm nay?"
Q: "bạn là ai" → "Mình là trợ lý học tiếng Anh của DTU LingoAI. Mình có thể giúp bạn về từ vựng, ngữ pháp, hay trả lời các câu hỏi tiếng Anh khác!"
Q: "cấu trúc thì hiện tại hoàn thành" → "- Công thức: S + have/has + V3/V-ed\n- Dấu hiệu: already, yet, just, ever, never..."
Q: "tôi đã đăng ký khóa học nào" → Dựa vào phần TIẾN ĐỘ HỌC TẬP, liệt kê từng khóa: tên, số bài đã học, % hoàn thành, trạng thái.

PROMPT;

        if (!empty($context)) {
            $system .= "\n=== NỘI DUNG LIÊN QUAN ===\n" . implode("\n\n", $context) . "\n";
        }

        return $system;
    }

    public function getChunkSize(): int
    {
        return $this->chunkSize;
    }

    public function getMaxChunksRetrieve(): int
    {
        return $this->maxChunksRetrieve;
    }

    public function getSimilarityThreshold(): float
    {
        return $this->similarityThreshold;
    }
}
