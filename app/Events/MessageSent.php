<?php

// app/Events/MessageSent.php
namespace App\Events;

use App\Models\MessageClass;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MessageSent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public MessageClass $message
    ) {
        $this->message->load(['user', 'replyTo.user']);
    }

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('class-chat.' . $this->message->class_id),
        ];
    }

    public function broadcastWith(): array
    {
        return [
            'id' => $this->message->id,
            'user_id' => $this->message->user_id,
            'class_id' => $this->message->class_id,
            'message' => $this->message->message,
            'sender_type' => $this->message->sender_type,
            'is_announcement' => $this->message->is_announcement,
            'is_pinned' => $this->message->is_pinned,
            'attachments' => $this->message->attachments,
            'created_at' => $this->message->created_at->toISOString(),
            'user' => [
                'id' => $this->message->user->id,
                'name' => $this->message->sender_name
            ],
            'reply_to' => $this->message->replyTo ? [
                'id' => $this->message->replyTo->id,
                'message' => $this->message->replyTo->message,
                'user' => [
                    'name' => $this->message->replyTo->sender_name
                ]
            ] : null
        ];
    }

    public function broadcastAs(): string
    {
        return 'message.sent';
    }
}
