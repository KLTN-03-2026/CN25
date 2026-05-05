<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ListeningExercise extends Model
{
    use HasFactory;

    protected $fillable = [
        'listening_id',
        'question',
        'type',
        'options',
        'correct_answer',
        'explanation',
    ];

    protected $casts = [
        'options' => 'array',
    ];

    /**
     * Loại câu hỏi
     */
    public static function getTypes()
    {
        return [
            'multiple_choice' => 'Trắc nghiệm',
            'fill_blank' => 'Điền từ',
            'true_false' => 'Đúng/Sai',
        ];
    }

    public function listening(): BelongsTo
    {
        return $this->belongsTo(Listening::class);
    }
}
