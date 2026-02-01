<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class VoteUpdated implements ShouldBroadcast, ShouldDispatchAfterCommit
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public int $questionId,
        public string $votableType,
        public int $votableId,
        public int $newScore
    ) {
    }

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('question.'.$this->questionId),
        ];
    }

    public function broadcastAs(): string
    {
        return 'VoteUpdated';
    }

    public function broadcastWith(): array
    {
        return [
            'votable_type' => $this->votableType,
            'votable_id' => $this->votableId,
            'new_score' => $this->newScore,
        ];
    }
}
