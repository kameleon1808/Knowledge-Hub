<?php

namespace App\Models;

use App\Models\Comment;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Answer extends Model
{
    use HasFactory;

    protected $fillable = [
        'question_id',
        'user_id',
        'body_markdown',
        'body_html',
        'ai_generated',
        'ai_audit_log_id',
    ];

    protected function casts(): array
    {
        return [
            'ai_generated' => 'boolean',
        ];
    }

    public function question(): BelongsTo
    {
        return $this->belongsTo(Question::class);
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function aiAuditLog(): BelongsTo
    {
        return $this->belongsTo(AiAuditLog::class, 'ai_audit_log_id');
    }

    public function attachments(): MorphMany
    {
        return $this->morphMany(Attachment::class, 'attachable');
    }

    public function votes(): MorphMany
    {
        return $this->morphMany(Vote::class, 'votable');
    }

    public function comments(): MorphMany
    {
        return $this->morphMany(Comment::class, 'commentable')->orderBy('created_at');
    }

    public function getScoreAttribute($value): int
    {
        if ($value !== null) {
            return (int) $value;
        }

        return (int) $this->votes()->sum('value');
    }
}
