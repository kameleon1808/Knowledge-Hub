<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class NotificationCreated implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public int $userId,
        public string $notificationId,
        public string $type,
        public array $data,
        public string $createdAt,
        public int $unreadCount
    ) {
    }

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('user.'.$this->userId.'.notifications'),
        ];
    }

    public function broadcastAs(): string
    {
        return 'NotificationCreated';
    }

    public function broadcastWith(): array
    {
        return [
            'notification_id' => $this->notificationId,
            'type' => $this->type,
            'data' => $this->data,
            'created_at' => $this->createdAt,
            'unread_count' => $this->unreadCount,
        ];
    }
}
