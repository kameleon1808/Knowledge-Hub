<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class ReputationEvent extends Model
{
    use HasFactory;

    public const TYPE_UPVOTE_QUESTION = 'UPVOTE_Q';
    public const TYPE_UPVOTE_ANSWER = 'UPVOTE_A';
    public const TYPE_DOWNVOTE = 'DOWNVOTE';
    public const TYPE_ACCEPTED = 'ACCEPTED';

    protected $fillable = [
        'user_id',
        'actor_user_id',
        'subject_type',
        'subject_id',
        'event_type',
        'points',
        'metadata',
    ];

    protected $casts = [
        'points' => 'integer',
        'metadata' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_user_id');
    }

    public function subject(): MorphTo
    {
        return $this->morphTo();
    }
}
