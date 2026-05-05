<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Lesson extends Model
{
    use HasFactory;

    protected $fillable = [
        'chapter_id',
        'title',
        'slug',
        'description',
        'order',
        'status',
        'type',
    ];

    protected $casts = [
        'order' => 'integer',
    ];

    protected $attributes = [
        'status' => 'draft',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($lesson) {
            if (empty($lesson->slug) && !empty($lesson->title)) {
                $baseSlug = \Illuminate\Support\Str::slug($lesson->title);
                $slug = $baseSlug;
                $counter = 1;
                while (static::where('slug', $slug)->exists()) {
                    $slug = $baseSlug . '-' . $counter;
                    $counter++;
                }
                $lesson->slug = $slug;
            }
        });

        static::updating(function ($lesson) {
            if ($lesson->isDirty('title') && !$lesson->isDirty('slug')) {
                $baseSlug = \Illuminate\Support\Str::slug($lesson->title);
                $slug = $baseSlug;
                $counter = 1;
                while (static::where('slug', $slug)->where('id', '!=', $lesson->id)->exists()) {
                    $slug = $baseSlug . '-' . $counter;
                    $counter++;
                }
                $lesson->slug = $slug;
            }
        });
    }

    public function chapter(): BelongsTo
    {
        return $this->belongsTo(Chapter::class);
    }

    public function vocabularies(): HasMany
    {
        return $this->hasMany(Vocabulary::class);
    }

    public function grammars(): HasMany
    {
        return $this->hasMany(Grammar::class);
    }

    public function speakingExercises(): HasMany
    {
        return $this->hasMany(SpeakingExercise::class);
    }

    public function listenings(): HasMany
    {
        return $this->hasMany(Listening::class);
    }

    public function progress(): HasMany
    {
        return $this->hasMany(LessonProgress::class);
    }

    public function scopePublished($query)
    {
        return $query->where('status', 'published');
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('order');
    }

    public function getIsPublishedAttribute(): bool
    {
        return $this->status === 'published';
    }
}