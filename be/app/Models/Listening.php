<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Listening extends Model
{
    use HasFactory;

    protected $fillable = [
        'lesson_id',
        'title',
        'script',
        'audio',
    ];

    public function lesson(): BelongsTo
    {
        return $this->belongsTo(Lesson::class);
    }

    public function exercises(): HasMany
    {
        return $this->hasMany(ListeningExercise::class);
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
