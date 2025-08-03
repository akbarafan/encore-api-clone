<?php
// app/Events/MessagePinned.php
namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MessagePinned implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public int $messageId,
        public int $classId,
        public bool $isPinned,
        public int $userId
    ) {}

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('class-chat.' . $this->classId),
        ];
    }

    public function broadcastWith(): array
    {
        return [
            'message_id' => $this->messageId,
            'is_pinned' => $this->isPinned,
            'pinned_by' => $this->userId,
            'timestamp' => now()->toISOString()
        ];
    }

    public function broadcastAs(): string
    {
        return 'message.pinned';
    }
}
