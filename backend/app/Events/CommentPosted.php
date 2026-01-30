<?php

namespace App\Events;

use App\Models\Comment;
use App\Models\Question;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class CommentPosted implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public Comment $comment
    ) {
    }

    public function broadcastOn(): array
    {
        $commentable = $this->comment->commentable;
        $questionId = $commentable instanceof Question
            ? $commentable->id
            : $commentable->question_id;

        return [
            new PrivateChannel('question.'.$questionId),
        ];
    }

    public function broadcastAs(): string
    {
        return 'CommentPosted';
    }

    public function broadcastWith(): array
    {
        $this->comment->load(['user', 'commentable']);
        $commentable = $this->comment->commentable;
        $commentableType = $commentable instanceof Question ? 'question' : 'answer';
        $commentableId = $commentable->id;

        return [
            'id' => $this->comment->id,
            'body_html' => $this->comment->body_html ?: $this->comment->body_markdown,
            'body_markdown' => $this->comment->body_markdown,
            'created_at' => $this->comment->created_at?->toIso8601String(),
            'author' => $this->comment->user?->only(['id', 'name']) ?? null,
            'can' => [
                'update' => false,
                'delete' => false,
            ],
            'commentable_type' => $commentableType,
            'commentable_id' => $commentableId,
        ];
    }
}
