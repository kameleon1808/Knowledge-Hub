<?php

namespace App\Events;

use App\Models\Answer;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class NewAnswerPosted implements ShouldBroadcastNow, ShouldDispatchAfterCommit
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public Answer $answer
    ) {
    }

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('question.'.$this->answer->question_id),
        ];
    }

    public function broadcastAs(): string
    {
        return 'NewAnswerPosted';
    }

    public function broadcastWith(): array
    {
        $this->answer->load(['author', 'attachments']);
        $score = (int) $this->answer->votes()->sum('value');

        $attachments = $this->answer->attachments->map(fn ($a) => [
            'id' => $a->id,
            'url' => $a->url,
            'original_name' => $a->original_name,
            'mime_type' => $a->mime_type,
            'size_bytes' => $a->size_bytes,
        ])->values()->all();

        return [
            'id' => $this->answer->id,
            'question_id' => $this->answer->question_id,
            'body_html' => $this->answer->body_html,
            'created_at' => $this->answer->created_at?->toIso8601String(),
            'author' => [
                'id' => $this->answer->author?->id,
                'name' => $this->answer->author?->name,
                'reputation' => $this->answer->author?->reputation ?? 0,
            ],
            'score' => $score,
            'is_accepted' => false,
            'attachments' => $attachments,
            'comments' => [],
            'can' => [
                'update' => false,
                'delete' => false,
                'vote' => true,
            ],
        ];
    }
}
