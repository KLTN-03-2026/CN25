<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CourseQuiz extends Model
{
    use HasFactory;

    protected $fillable = [
        'course_id',
        'title',
        'duration',
        'pass_score',
    ];

    protected $casts = [
        'duration' => 'integer',
        'pass_score' => 'integer',
    ];

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function questions(): HasMany
    {
        return $this->hasMany(CourseQuizQuestion::class, 'quiz_id')->orderBy('order');
    }
}
