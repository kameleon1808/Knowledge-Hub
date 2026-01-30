<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RagQuery extends Model
{
    protected $fillable = [
        'project_id',
        'user_id',
        'question_text',
        'answer_text',
        'cited_chunk_ids',
        'provider',
        'model',
    ];

    protected function casts(): array
    {
        return [
            'cited_chunk_ids' => 'array',
        ];
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
