<?php

namespace App\Events;

use App\Models\MessageClass;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PublicMessageSent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public MessageClass $message
    ) {
        $this->message->load(['user']);
    }

    public function broadcastOn(): array
    {
        return [
            new Channel('public-class-chat.' . $this->message->class_id),
        ];
    }

    public function broadcastWith(): array
    {
        return [
            'id' => $this->message->id,
            'class_id' => $this->message->class_id,
            'message' => $this->message->message,
            'sender_type' => $this->message->sender_type,
            'is_announcement' => $this->message->is_announcement,
            'created_at' => $this->message->created_at->toISOString(),
            'user' => [
                'id' => $this->message->user->id,
                'name' => $this->message->sender_name
            ],
            // Limited data for security (no sensitive info)
            'preview' => substr($this->message->message, 0, 100) . '...',
        ];
    }

    public function broadcastAs(): string
    {
        return 'message.sent';
    }
}
