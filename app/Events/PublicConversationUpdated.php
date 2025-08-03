<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PublicConversationUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public ?int $studentId = null,
        public ?int $instructorId = null,
        public ?int $classId = null,
        public array $conversationData = [],
        public string $updateType = 'conversation_updated'
    ) {}

    public function broadcastOn(): array
    {
        $channels = [];

        // Broadcast to general public conversations
        $channels[] = new Channel('public-conversations');

        // Broadcast to specific student if provided
        if ($this->studentId) {
            $channels[] = new Channel('public-student-conversations.' . $this->studentId);
        }

        // Broadcast to specific instructor if provided
        if ($this->instructorId) {
            $channels[] = new Channel('public-instructor-conversations.' . $this->instructorId);
        }

        // Broadcast to specific class if provided
        if ($this->classId) {
            $channels[] = new Channel('public-class-conversations.' . $this->classId);
        }

        return $channels;
    }

    public function broadcastWith(): array
    {
        return [
            'student_id' => $this->studentId,
            'instructor_id' => $this->instructorId,
            'class_id' => $this->classId,
            'conversations' => $this->conversationData,
            'update_type' => $this->updateType,
            'timestamp' => now()->toISOString(),
        ];
    }

    public function broadcastAs(): string
    {
        return 'conversation.updated';
    }
}
