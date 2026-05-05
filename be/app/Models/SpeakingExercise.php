<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SpeakingExercise extends Model
{
    use HasFactory;

    protected $fillable = [
        'lesson_id',
        'type',
        'content',
        'audio_url',
        'image_url',
        'keywords',
        'sample_answer',
        'order',
    ];

    protected $casts = [
        'order' => 'integer',
    ];

    const TYPE_REPEAT = 'repeat';
    const TYPE_READ = 'read';
    const TYPE_DESCRIBE = 'describe';
    const TYPE_QA = 'qa';

    const TYPE_LABELS = [
        self::TYPE_REPEAT => 'Repeat (Lặp lại)',
        self::TYPE_READ => 'Read (Đọc)',
        self::TYPE_DESCRIBE => 'Describe (Mô tả)',
        self::TYPE_QA => 'Q&A (Hỏi đáp)',
    ];

    public function lesson(): BelongsTo
    {
        return $this->belongsTo(Lesson::class);
    }

    public function scopeByLesson($query, $lessonId)
    {
        return $query->where('lesson_id', $lessonId);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('order')->orderBy('id');
    }

    public function scopeByType($query, $type)
    {
        return $query->where('type', $type);
    }

    public function scopePublished($query)
    {
        return $query->whereHas('lesson', function ($q) {
            $q->where('status', 'published')
              ->whereHas('chapter', function ($cq) {
                  $cq->where('status', 'published')
                     ->whereHas('course', function ($cc) {
                         $cc->where('status', 'published');
                     });
              });
        });
    }

    public function getTypeLabelAttribute(): string
    {
        return self::TYPE_LABELS[$this->type] ?? $this->type;
    }

    public function getKeywordsArrayAttribute(): array
    {
        if (empty($this->keywords)) {
            return [];
        }
        return array_map('trim', explode(',', $this->keywords));
    }
}
