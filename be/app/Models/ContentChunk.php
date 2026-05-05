<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class ContentChunk extends Model
{
    protected $fillable = [
        'content_type',
        'content_id',
        'title',
        'chunk_text',
        'embedding',
        'chunk_index',
        'is_embedded',
    ];

    protected $casts = [
        'embedding' => 'array',
        'is_embedded' => 'boolean',
        'chunk_index' => 'integer',
    ];

    public function chunkable(): MorphTo
    {
        return $this->morphTo('chunkable', 'content_type', 'content_id');
    }

    public function markAsEmbedded(): void
    {
        $this->update(['is_embedded' => true]);
    }

    public function scopeEmbedded($query)
    {
        return $query->where('is_embedded', true);
    }

    public function scopeOfType($query, string $type)
    {
        return $query->where('content_type', $type);
    }
}
