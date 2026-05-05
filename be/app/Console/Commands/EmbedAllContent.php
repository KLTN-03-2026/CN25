<?php

namespace App\Console\Commands;

use App\Models\Grammar;
use App\Models\Vocabulary;
use App\Models\Lesson;
use App\Models\ContentChunk;
use App\Models\Listening;
use App\Services\GeminiService;
use Illuminate\Console\Command;

class EmbedAllContent extends Command
{
    protected $signature = 'chatbot:embed {--force : Xóa dữ liệu cũ và embed lại}';
    protected $description = 'Embed tất cả nội dung bài học (Grammar, Vocabulary, Lesson) vào vector database';

    public function handle(GeminiService $geminiService): int
    {
        $this->info('=== Bắt đầu Embedding toàn bộ nội dung ===');

        if (config('gemini.api_key')) {
            $this->info('Gemini API Key: Đã cấu hình');
        } else {
            $this->error('GEMINI_API_KEY chưa được cấu hình trong file .env');
            return self::FAILURE;
        }

        if ($this->option('force')) {
            $this->warn('Xóa dữ liệu embedding cũ...');
            ContentChunk::truncate();
        }

        $this->info('');
        $this->info('--- Embedding Grammar ---');
        $this->embedGrammar($geminiService);

        $this->info('');
        $this->info('--- Embedding Vocabulary ---');
        $this->embedVocabulary($geminiService);

        $this->info('');
        $this->info('--- Embedding Lessons ---');
        $this->embedLessons($geminiService);

        $this->info('');
        $this->info('--- Embedding Listening ---');
        $this->embedListening($geminiService);

        $total = ContentChunk::count();
        $embedded = ContentChunk::where('is_embedded', true)->count();

        $this->info('');
        $this->info("=== Hoàn tất! ===");
        $this->info("Tổng chunks: {$total}");
        $this->info("Đã embedding: {$embedded}");
        $this->info("Chưa embedding: " . ($total - $embedded));

        return self::SUCCESS;
    }

    private function embedGrammar(GeminiService $geminiService): void
    {
        $grammars = Grammar::all();
        $bar = $this->output->createProgressBar($grammars->count());
        $bar->start();

        foreach ($grammars as $grammar) {
            $text = ($grammar->title ?? '') . ". " . ($grammar->structure ?? '') . " " . ($grammar->explanation ?? '');
            $text = trim($text);

            if (mb_strlen($text) < 20) {
                $bar->advance();
                continue;
            }

            $chunks = $geminiService->chunkText($text, $grammar->title ?? 'Ngữ pháp');

            foreach ($chunks as $idx => $chunkText) {
                if (ContentChunk::where('content_type', 'grammar')
                    ->where('content_id', $grammar->id)
                    ->where('chunk_index', $idx)
                    ->exists()
                ) {
                    continue;
                }

                $this->embedChunk($geminiService, 'grammar', $grammar->id, $grammar->title, $chunkText, $idx);
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
    }

    private function embedVocabulary(GeminiService $geminiService): void
    {
        $vocabs = Vocabulary::all();
        $bar = $this->output->createProgressBar($vocabs->count());
        $bar->start();

        foreach ($vocabs as $vocab) {
            $text = "TỪ: {$vocab->word}. PHIÊN ÂM: {$vocab->phonetic}. NGHĨA: {$vocab->definition}. VÍ DỤ: {$vocab->example}";
            $text = trim($text);

            if (mb_strlen($text) < 10) {
                $bar->advance();
                continue;
            }

            if (ContentChunk::where('content_type', 'vocabulary')
                ->where('content_id', $vocab->id)
                ->where('chunk_index', 0)
                ->exists()
            ) {
                $bar->advance();
                continue;
            }

            $this->embedChunk($geminiService, 'vocabulary', $vocab->id, $vocab->word, $text, 0);
            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
    }

    private function embedLessons(GeminiService $geminiService): void
    {
        $lessons = Lesson::with(['grammars', 'vocabularies'])->get();
        $bar = $this->output->createProgressBar($lessons->count());
        $bar->start();

        foreach ($lessons as $lesson) {
            $text = "BÀI HỌC: " . ($lesson->title ?? '') . ". " . ($lesson->description ?? '') . " " . ($lesson->content ?? '');
            $text = trim($text);

            if (mb_strlen($text) < 20) {
                $bar->advance();
                continue;
            }

            $chunks = $geminiService->chunkText($text, $lesson->title ?? 'Bài học');

            foreach ($chunks as $idx => $chunkText) {
                if (ContentChunk::where('content_type', 'lesson')
                    ->where('content_id', $lesson->id)
                    ->where('chunk_index', $idx)
                    ->exists()
                ) {
                    continue;
                }

                $this->embedChunk($geminiService, 'lesson', $lesson->id, $lesson->title, $chunkText, $idx);
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
    }

    private function embedListening(GeminiService $geminiService): void
    {
        $listenings = Listening::all();
        $bar = $this->output->createProgressBar($listenings->count());
        $bar->start();

        foreach ($listenings as $listening) {
            $text = "BÀI LUYỆN NGHE: " . ($listening->title ?? '') . ". " . ($listening->transcript ?? '') . " " . ($listening->description ?? '');
            $text = trim($text);

            if (mb_strlen($text) < 20) {
                $bar->advance();
                continue;
            }

            $chunks = $geminiService->chunkText($text, $listening->title ?? 'Bài nghe');

            foreach ($chunks as $idx => $chunkText) {
                if (ContentChunk::where('content_type', 'listening')
                    ->where('content_id', $listening->id)
                    ->where('chunk_index', $idx)
                    ->exists()
                ) {
                    continue;
                }

                $this->embedChunk($geminiService, 'listening', $listening->id, $listening->title, $chunkText, $idx);
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
    }

    private function embedChunk(
        GeminiService $geminiService,
        string $type,
        int $id,
        ?string $title,
        string $text,
        int $index
    ): void {
        $embedding = $geminiService->getEmbedding($text);

        ContentChunk::create([
            'content_type' => $type,
            'content_id' => $id,
            'title' => $title,
            'chunk_text' => $text,
            'embedding' => $embedding,
            'chunk_index' => $index,
            'is_embedded' => !is_null($embedding),
        ]);

        if (is_null($embedding)) {
            $this->warn("  [!] Embedding thất bại cho: {$title} (chunk {$index})");
        }
    }
}
