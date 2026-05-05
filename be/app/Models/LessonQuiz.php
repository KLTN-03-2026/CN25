<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LessonQuiz extends Model
{
    protected $fillable = [
        'course_id',
        'type',
        'question',
        'options',
        'correct_answer',
        'extra_data',
        'order'
    ];

    protected $casts = [
        'options' => 'array',
        'extra_data' => 'array'
    ];

    public function course(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Course::class);
    }
}
