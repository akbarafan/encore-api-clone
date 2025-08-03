<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ConversationUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public int $studentId,
        public array $conversationData,
        public ?int $instructorId = null,
        public ?int $familyId = null
    ) {}

    public function broadcastOn(): array
    {
        $channels = [
            new PrivateChannel('student-conversations.' . $this->studentId),
        ];

        // Also broadcast to instructor if provided
        if ($this->instructorId) {
            $channels[] = new PrivateChannel('instructor-conversations.' . $this->instructorId);
        }

        // Also broadcast to family if provided
        if ($this->familyId) {
            $channels[] = new PrivateChannel('family-conversations.' . $this->familyId);
        }

        return $channels;
    }

    public function broadcastWith(): array
    {
        return [
            'student_id' => $this->studentId,
            'conversations' => $this->conversationData,
            'instructor_id' => $this->instructorId,
            'family_id' => $this->familyId,
            'updated_at' => now()->toISOString(),
        ];
    }

    public function broadcastAs(): string
    {
        return 'conversations.updated';
    }
}
