<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class OllamaService
{
    private string $baseUrl;
    private string $model;
    private int $timeout;

    public function __construct()
    {
        $this->baseUrl = config('services.ollama.host', 'http://localhost:11434');
        $this->model = config('services.ollama.model', 'llama3.2');
        $this->timeout = config('services.ollama.timeout', 60);
    }

    public function isAvailable(): bool
    {
        try {
            $response = Http::timeout(3)->get("{$this->baseUrl}/api/tags");
            return $response->successful();
        } catch (\Exception $e) {
            return false;
        }
    }

    public function generateResponse(string $prompt, array $context = []): array
    {
        $fullPrompt = $this->buildPrompt($prompt, $context);

        $maxRetries = 2;
        $lastError = null;

        for ($attempt = 1; $attempt <= $maxRetries; $attempt++) {
            try {
                $response = Http::timeout($this->timeout)->post("{$this->baseUrl}/api/generate", [
                    'model' => $this->model,
                    'prompt' => $fullPrompt,
                    'stream' => false,
                    'options' => [
                        'temperature' => 0.7,
                        'num_predict' => 512,
                    ],
                ]);

                if ($response->status() === 503 || $response->status() === 0) {
                    $lastError = 'Ollama đang bận hoặc không phản hồi.';
                    Log::warning("Ollama 503 attempt {$attempt}/{$maxRetries}");
                    usleep(500000 * $attempt);
                    continue;
                }

                if (!$response->successful()) {
                    $lastError = 'Ollama lỗi: ' . $response->status();
                    Log::error('Ollama error: ' . $response->body());
                    continue;
                }

                $data = $response->json();
                $text = $data['response'] ?? null;

                if (empty(trim($text))) {
                    return [
                        'success' => false,
                        'error' => 'Ollama không trả lời.',
                    ];
                }

                return [
                    'success' => true,
                    'response' => trim($text),
                    'provider' => 'ollama',
                ];
            } catch (\Exception $e) {
                $lastError = 'Lỗi kết nối Ollama: ' . $e->getMessage();
                Log::error('Ollama exception: ' . $e->getMessage());
            }
        }

        return [
            'success' => false,
            'error' => $lastError ?? 'Ollama tạm thời không khả dụng.',
        ];
    }

    private function buildPrompt(string $prompt, array $context): string
    {
        $system = "Bạn là trợ lý dạy tiếng Anh cho học viên Việt Nam. Trả lời ngắn gọn, rõ ràng, dùng gạch đầu dòng. Nếu câu hỏi không liên quan đến tiếng Anh, học tập, hoặc khóa học thì từ chối.\n";

        $full = $system;
        if (!empty($context)) {
            $full .= "\nNỘI DUNG LIÊN QUAN:\n" . implode("\n\n", $context) . "\n";
        }
        $full .= "\nCâu hỏi: " . $prompt;

        return $full;
    }
}
