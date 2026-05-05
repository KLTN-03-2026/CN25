<?php

namespace App\Services;

use OpenAI\Laravel\Facades\OpenAI;
use Illuminate\Http\UploadedFile;

class SpeakingAssessmentService
{
    public function assess(
        UploadedFile $audioFile,
        string $expectedAnswer,
        string $type,
        ?string $keywords = null
    ): array {
        $userText = $this->transcribeAudio($audioFile);

        if (!$userText) {
            return [
                'success' => false,
                'error' => 'Không thể nhận diện giọng nói. Vui lòng thử lại.',
            ];
        }

        $score = $this->calculateScore($userText, $expectedAnswer, $keywords);
        $feedback = $this->getFeedback($userText, $expectedAnswer, $score, $type, $keywords);

        return [
            'success' => true,
            'score' => $score,
            'user_text' => $userText,
            'expected_answer' => $expectedAnswer,
            'feedback' => $feedback,
            'word_match' => $this->calculateWordMatch($userText, $expectedAnswer),
        ];
    }

    private function transcribeAudio(UploadedFile $audioFile): ?string
    {
        try {
            $response = OpenAI::audio()->transcribe([
                'model' => 'whisper-1',
                'file' => $audioFile,
                'language' => 'vi',
            ]);

            $text = $response->text ?? null;

            \Log::info('Whisper transcription result', [
                'text' => $text,
                'audio_size' => $audioFile->getSize(),
                'audio_mime' => $audioFile->getMimeType(),
            ]);

            return $text;
        } catch (\Exception $e) {
            \Log::error('Whisper transcription error: ' . $e->getMessage(), [
                'exception' => get_class($e),
                'trace' => $e->getTraceAsString(),
            ]);
            return null;
        }
    }

    private function calculateScore(string $userText, string $expectedAnswer, ?string $keywords): int
    {
        $userTextLower = mb_strtolower(trim($userText));
        $expectedLower = mb_strtolower(trim($expectedAnswer));

        $userWords = $this->normalizeText($userTextLower);
        $expectedWords = $this->normalizeText($expectedLower);

        $intersection = array_intersect($userWords, $expectedWords);
        $wordMatchScore = count($intersection) / max(count($expectedWords), 1) * 100;

        $keywordScore = 0;
        if ($keywords) {
            $keywordList = array_map('trim', explode(',', mb_strtolower($keywords)));
            $keywordFound = 0;
            foreach ($keywordList as $keyword) {
                if (str_contains($userTextLower, $keyword)) {
                    $keywordFound++;
                }
            }
            $keywordScore = count($keywordList) > 0
                ? ($keywordFound / count($keywordList)) * 100
                : 0;
        }

        $baseScore = ($wordMatchScore * 0.6) + ($keywordScore * 0.4);

        if ($keywordScore > 0 && str_word_count($userText) >= str_word_count($expectedAnswer) * 0.8) {
            $lengthBonus = min(10, (str_word_count($userText) / max(str_word_count($expectedAnswer), 1)) * 5);
            $baseScore = min(100, $baseScore + $lengthBonus);
        }

        return (int) round($baseScore);
    }

    private function getFeedback(string $userText, string $expectedAnswer, int $score, string $type, ?string $keywords): array
    {
        $userTextLower = mb_strtolower(trim($userText));
        $expectedLower = mb_strtolower(trim($expectedAnswer));

        $feedback = [];

        if ($score >= 90) {
            $feedback['level'] = 'excellent';
            $feedback['message'] = 'Xuất sắc! Bạn phát âm rất chuẩn xác.';
        } elseif ($score >= 70) {
            $feedback['level'] = 'good';
            $feedback['message'] = 'Tốt lắm! Cần cải thiện một chút về ngữ điệu và tốc độ.';
        } elseif ($score >= 50) {
            $feedback['level'] = 'fair';
            $feedback['message'] = 'Khá ổn! Hãy chú ý đến từ khóa và cách phát âm.';
        } else {
            $feedback['level'] = 'poor';
            $feedback['message'] = 'Cần luyện tập thêm. Hãy nghe câu mẫu và thử lại nhé!';
        }

        $missingWords = [];
        $expectedWords = $this->normalizeText($expectedLower);
        foreach ($expectedWords as $word) {
            if (strlen($word) > 2 && !str_contains($userTextLower, $word)) {
                $missingWords[] = $word;
            }
        }

        if (count($missingWords) > 0) {
            $feedback['missing_keywords'] = array_slice(array_unique($missingWords), 0, 5);
        }

        if ($keywords) {
            $keywordList = array_map('trim', explode(',', mb_strtolower($keywords)));
            $missingKeywords = [];
            foreach ($keywordList as $keyword) {
                if (!str_contains($userTextLower, $keyword)) {
                    $missingKeywords[] = $keyword;
                }
            }
            if (count($missingKeywords) > 0) {
                $feedback['missing_keywords'] = array_unique(
                    array_merge(
                        $feedback['missing_keywords'] ?? [],
                        $missingKeywords
                    )
                );
            }
        }

        $feedback['suggestions'] = $this->getSuggestions($score, $type, $missingWords);

        return $feedback;
    }

    private function getSuggestions(int $score, string $type, array $missingWords): array
    {
        $suggestions = [];

        if ($score < 90) {
            $suggestions[] = 'Nghe câu mẫu và chú ý cách phát âm từng từ.';
        }

        if ($score < 70) {
            $suggestions[] = 'Tập nói chậm rãi, rõ ràng từng câu.';
        }

        if (count($missingWords) > 3) {
            $suggestions[] = 'Hãy tập trung vào các từ khóa: ' . implode(', ', array_slice($missingWords, 0, 3));
        }

        if ($type === 'repeat') {
            $suggestions[] = 'Nhớ lặp lại đúng ngữ điệu như câu mẫu.';
        } elseif ($type === 'read') {
            $suggestions[] = 'Đọc to rõ ràng, chú ý dấu câu.';
        } elseif ($type === 'qa') {
            $suggestions[] = 'Trả lời đủ ý và đúng trọng tâm câu hỏi.';
        } elseif ($type === 'describe') {
            $suggestions[] = 'Mô tả chi tiết hơn, sử dụng nhiều từ vựng đã học.';
        }

        return $suggestions;
    }

    private function normalizeText(string $text): array
    {
        $text = preg_replace('/[^\p{L}\p{N}\s]/u', '', $text);
        $words = preg_split('/\s+/', $text, -1, PREG_SPLIT_NO_EMPTY);
        return array_filter($words, fn($word) => mb_strlen($word) > 0);
    }

    private function calculateWordMatch(string $userText, string $expectedAnswer): array
    {
        $userWords = $this->normalizeText(mb_strtolower($userText));
        $expectedWords = $this->normalizeText(mb_strtolower($expectedAnswer));

        $matched = array_values(array_intersect($userWords, $expectedWords));
        $unmatched = array_values(array_diff($expectedWords, $userWords));

        return [
            'matched' => $matched,
            'missing' => array_slice($unmatched, 0, 5),
            'match_rate' => count($expectedWords) > 0
                ? round(count($matched) / count($expectedWords) * 100, 1)
                : 0,
        ];
    }
}
