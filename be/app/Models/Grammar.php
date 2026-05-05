<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Grammar extends Model
{
    use HasFactory;

    protected $fillable = [
        'lesson_id',
        'title',
        'explanation',
        'structure',
        'example',
        'youtube_url',
        'signals',
    ];

    public function lesson(): BelongsTo
    {
        return $this->belongsTo(Lesson::class);
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
}
