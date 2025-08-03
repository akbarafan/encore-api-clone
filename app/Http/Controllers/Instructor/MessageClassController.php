<?php

namespace App\Http\Controllers\Instructor;

use App\Events\ConversationUpdated;
use App\Events\PublicConversationUpdated;
use App\Http\Controllers\Controller;
use App\Models\MessageClass;
use App\Models\Classes;
use App\Models\User;
use App\Models\Student;
use App\Helpers\InstructorHelper;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class MessageClassController extends Controller
{
    private function getCurrentInstructor()
    {
        return InstructorHelper::getCurrentInstructorRecord();
    }

    /**
     * Show chat interface
     */
    public function index()
    {
        $instructor = $this->getCurrentInstructor();

        // Get instructor's classes with student counts (only show classes with active students)
        $classes = Classes::where('instructor_id', $instructor->id)
            ->where('is_approved', true)
            ->with(['category', 'type'])
            ->withCount(['students' => function ($query) {
                $query->whereHas('enrolls', function ($q) {
                    $q->where('status', 'active');
                });
            }])
            ->get();

        // Get recent conversations per class
        $conversations = [];
        foreach ($classes as $class) {
            // Only include classes that have students
            if ($class->students_count > 0) {
                $lastMessage = MessageClass::where('class_id', $class->id)
                    ->with(['user'])
                    ->latest()
                    ->first();

                $unreadCount = MessageClass::where('class_id', $class->id)
                    ->where('sender_type', 'student') // Only count student messages as unread for instructor
                    ->where('is_read', false)
                    ->count();

                $conversations[] = [
                    'class' => $class,
                    'last_message' => $lastMessage,
                    'unread_count' => $unreadCount
                ];
            }
        }

        // Sort conversations by last message date
        usort($conversations, function ($a, $b) {
            $aTime = $a['last_message'] ? $a['last_message']->created_at : null;
            $bTime = $b['last_message'] ? $b['last_message']->created_at : null;

            if (!$aTime && !$bTime) return 0;
            if (!$aTime) return 1;
            if (!$bTime) return -1;

            return $bTime <=> $aTime;
        });

        return view('instructor.chat.index', compact('classes', 'conversations'));
    }

    /**
     * Get messages for a specific class
     */
    public function getClassMessages(Request $request, $classId)
    {
        try {
            $instructor = $this->getCurrentInstructor();

            // Verify class belongs to instructor and has students
            $class = Classes::where('id', $classId)
                ->where('instructor_id', $instructor->id)
                ->where('is_approved', true)
                ->with(['category', 'students' => function ($query) {
                    $query->whereHas('enrolls', function ($q) {
                        $q->where('status', 'active');
                    });
                }])
                ->first();

            if (!$class) {
                return response()->json([
                    'success' => false,
                    'message' => 'Class not found or not accessible'
                ], 404);
            }

            // Get messages with pagination (newest at bottom like WhatsApp)
            $messages = MessageClass::where('class_id', $classId)
                ->with(['user', 'replyTo.user'])
                ->orderBy('created_at', 'asc')
                ->paginate(50);

            // Mark student messages as read for instructor
            MessageClass::where('class_id', $classId)
                ->where('sender_type', 'student')
                ->where('is_read', false)
                ->update(['is_read' => true, 'read_at' => now()]);

            // Transform messages data
            $messagesData = $messages->getCollection()->map(function ($message) {
                return [
                    'id' => $message->id,
                    'user_id' => $message->user_id,
                    'message' => $message->message,
                    'sender_type' => $message->sender_type,
                    'is_announcement' => $message->is_announcement,
                    'is_pinned' => $message->is_pinned,
                    'attachments' => $message->attachments,
                    'created_at' => $message->created_at,
                    'user' => [
                        'id' => $message->user->id,
                        'name' => $message->sender_name
                    ],
                    'reply_to' => $message->replyTo ? [
                        'id' => $message->replyTo->id,
                        'message' => $message->replyTo->message,
                        'user' => [
                            'name' => $message->replyTo->sender_name
                        ]
                    ] : null
                ];
            });

            return response()->json([
                'success' => true,
                'class' => [
                    'id' => $class->id,
                    'name' => $class->name,
                    'category' => $class->category,
                    'students_count' => $class->students->count()
                ],
                'messages' => [
                    'data' => $messagesData,
                    'current_page' => $messages->currentPage(),
                    'last_page' => $messages->lastPage(),
                    'total' => $messages->total()
                ],
                'current_user_id' => $instructor->user_id
            ]);
        } catch (\Exception $e) {
            Log::error('Error loading class messages: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error loading messages: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Send a message to class
     */
    public function sendMessage(Request $request)
    {
        try {
            $instructor = $this->getCurrentInstructor();

            $request->validate([
                'class_id' => 'required|exists:classes,id',
                'message' => 'required|string|max:2000',
                'reply_to' => 'nullable|exists:message_classes,id',
                'is_announcement' => 'boolean',
                'attachments' => 'nullable|array',
                'attachments.*' => 'file|max:10240|mimes:pdf,doc,docx,jpg,jpeg,png,zip'
            ]);

            // Verify class belongs to instructor
            $class = Classes::where('id', $request->class_id)
                ->where('instructor_id', $instructor->id)
                ->where('is_approved', true)
                ->first();

            if (!$class) {
                return response()->json([
                    'success' => false,
                    'message' => 'Class not found or not accessible'
                ], 404);
            }

            // Handle file attachments
            $attachments = [];
            if ($request->hasFile('attachments')) {
                foreach ($request->file('attachments') as $file) {
                    $fileName = time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
                    $filePath = $file->storeAs('chat-attachments', $fileName, 'public');

                    $attachments[] = [
                        'original_name' => $file->getClientOriginalName(),
                        'file_name' => $fileName,
                        'file_path' => $filePath,
                        'file_size' => $file->getSize(),
                        'mime_type' => $file->getMimeType()
                    ];
                }
            }

            // Create message
            $message = MessageClass::create([
                'user_id' => $instructor->user_id,
                'class_id' => $request->class_id,
                'sender_type' => 'instructor',
                'message' => $request->message,
                'reply_to' => $request->reply_to,
                'attachments' => !empty($attachments) ? $attachments : null,
                'is_announcement' => $request->boolean('is_announcement'),
                'is_read' => true, // Instructor's own message is already "read"
                'read_at' => now()
            ]);

            $message->load(['user', 'replyTo.user']);

            // Broadcast message to real-time channel (optional)
            try {
                event(new \App\Events\MessageSent($message));
            } catch (\Exception $e) {
                Log::warning('Failed to broadcast message: ' . $e->getMessage());
                // Continue anyway - message was saved successfully
            }

            // Broadcast conversation updates to public channels (No Auth Required) - PRIORITAS UNTUK MOBILE
            try {
                $this->broadcastPublicConversationUpdates($request->class_id, $message);
            } catch (\Exception $e) {
                Log::warning('Failed to broadcast public conversation updates: ' . $e->getMessage());
            }

            // Broadcast conversation updates to all participants (Private Channels) - FALLBACK
            try {
                $this->broadcastConversationUpdates($request->class_id, $message);
            } catch (\Exception $e) {
                Log::warning('Failed to broadcast conversation updates: ' . $e->getMessage());
            }

            return response()->json([
                'success' => true,
                'message' => 'Message sent successfully',
                'data' => $message
            ]);
        } catch (\Exception $e) {
            Log::error('Error sending message: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error sending message: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get class participants
     */
    public function getClassParticipants($classId)
    {
        try {
            $instructor = $this->getCurrentInstructor();

            // Verify class belongs to instructor
            $class = Classes::where('id', $classId)
                ->where('instructor_id', $instructor->id)
                ->where('is_approved', true)
                ->first();

            if (!$class) {
                return response()->json([
                    'success' => false,
                    'message' => 'Class not found'
                ], 404);
            }

            // Get active students in this class
            $students = Student::whereHas('enrolls', function ($query) use ($classId) {
                $query->where('class_id', $classId)
                    ->where('status', 'active');
            })
                ->with(['family'])
                ->get();

            return response()->json([
                'success' => true,
                'class' => $class,
                'instructor' => [
                    'id' => $instructor->user_id,
                    'name' => $instructor->name,
                    'type' => 'instructor'
                ],
                'students' => $students->map(function ($student) {
                    return [
                        'id' => $student->user_id ?? $student->id,
                        'name' => $student->first_name . ' ' . $student->last_name,
                        'type' => 'student',
                        'family' => $student->family ? $student->family->name : null
                    ];
                })
            ]);
        } catch (\Exception $e) {
            Log::error('Error loading participants: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error loading participants'
            ], 500);
        }
    }

    /**
     * Delete a message (instructor only)
     */
    public function deleteMessage($messageId)
    {
        try {
            $instructor = $this->getCurrentInstructor();

            $message = MessageClass::where('id', $messageId)
                ->where('user_id', $instructor->user_id)
                ->first();

            if (!$message) {
                return response()->json([
                    'success' => false,
                    'message' => 'Message not found or not authorized'
                ], 404);
            }

            $message->delete();

            // Broadcast message deletion
            event(new \App\Events\MessageDeleted(
                $messageId,
                $message->class_id,
                $instructor->user_id
            ));

            return response()->json([
                'success' => true,
                'message' => 'Message deleted successfully'
            ]);
        } catch (\Exception $e) {
            Log::error('Error deleting message: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error deleting message'
            ], 500);
        }
    }

    /**
     * Pin/Unpin a message
     */
    public function togglePin($messageId)
    {
        try {
            $instructor = $this->getCurrentInstructor();

            $message = MessageClass::whereHas('class', function ($query) use ($instructor) {
                $query->where('instructor_id', $instructor->id);
            })->find($messageId);

            if (!$message) {
                return response()->json([
                    'success' => false,
                    'message' => 'Message not found'
                ], 404);
            }

            $message->togglePin();

            // Broadcast pin status change
            event(new \App\Events\MessagePinned(
                $messageId,
                $message->class_id,
                $message->is_pinned,
                $instructor->user_id
            ));

            return response()->json([
                'success' => true,
                'message' => $message->is_pinned ? 'Message pinned' : 'Message unpinned',
                'is_pinned' => $message->is_pinned
            ]);
        } catch (\Exception $e) {
            Log::error('Error toggling pin: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error toggling pin'
            ], 500);
        }
    }

    /**
     * Get conversation summary for dashboard
     */
    public function getConversationSummary()
    {
        $instructor = $this->getCurrentInstructor();

        $totalUnread = MessageClass::whereHas('class', function ($query) use ($instructor) {
            $query->where('instructor_id', $instructor->id);
        })
            ->where('sender_type', 'student')
            ->where('is_read', false)
            ->count();

        $totalClasses = Classes::where('instructor_id', $instructor->id)
            ->where('is_approved', true)
            ->withCount(['students' => function ($query) {
                $query->whereHas('enrolls', function ($q) {
                    $q->where('status', 'active');
                });
            }])
            ->having('students_count', '>', 0)
            ->count();

        return response()->json([
            'total_unread' => $totalUnread,
            'total_classes' => $totalClasses
        ]);
    }

    /**
     * Handle typing indicator
     */
    public function updateTypingStatus(Request $request)
    {
        $request->validate([
            'class_id' => 'required|exists:classes,id',
            'is_typing' => 'required|boolean'
        ]);

        $instructor = $this->getCurrentInstructor();

        // Verify class belongs to instructor
        $class = Classes::where('id', $request->class_id)
            ->where('instructor_id', $instructor->id)
            ->where('is_approved', true)
            ->first();

        if (!$class) {
            return response()->json([
                'success' => false,
                'message' => 'Class not found'
            ], 404);
        }

        // Broadcast typing status
        event(new \App\Events\UserTyping(
            $request->class_id,
            $instructor->user_id,
            $instructor->name,
            'instructor',
            $request->boolean('is_typing')
        ));

        return response()->json(['success' => true]);
    }

    /**
     * Update online status
     */
    public function updateOnlineStatus(Request $request)
    {
        $request->validate([
            'class_id' => 'required|exists:classes,id',
            'is_online' => 'required|boolean'
        ]);

        $instructor = $this->getCurrentInstructor();

        // Verify class belongs to instructor
        $class = Classes::where('id', $request->class_id)
            ->where('instructor_id', $instructor->id)
            ->where('is_approved', true)
            ->first();

        if (!$class) {
            return response()->json([
                'success' => false,
                'message' => 'Class not found'
            ], 404);
        }

        // Broadcast online status
        event(new \App\Events\UserOnlineStatus(
            $request->class_id,
            $instructor->user_id,
            $instructor->name,
            'instructor',
            $request->boolean('is_online')
        ));

        return response()->json(['success' => true]);
    }

    private function formatMessageForApi($message, $currentUserId)
    {
        return [
            'id' => $message->id,
            'user_id' => $message->user_id,
            'message' => $message->message,
            'sender_type' => $message->sender_type,
            'is_announcement' => $message->is_announcement,
            'is_pinned' => $message->is_pinned,
            'is_own' => $message->user_id === $currentUserId,
            'attachments' => $message->attachments,
            'created_at' => $message->created_at->toISOString(),
            'user' => [
                'id' => $message->user->id,
                'name' => $message->sender_name
            ],
            'reply_to' => $message->replyTo ? [
                'id' => $message->replyTo->id,
                'message' => $message->replyTo->message,
                'user' => [
                    'name' => $message->replyTo->sender_name
                ]
            ] : null
        ];
    }

    /**
     * Broadcast conversation updates to all participants in the class
     */
    private function broadcastConversationUpdates($classId, $newMessage)
    {
        Log::info("Broadcasting conversation updates for class {$classId}");

        // Get class with instructor and students
        $class = Classes::with(['instructor.user', 'students.user', 'students.family.user'])
            ->find($classId);

        if (!$class) {
            Log::warning("Class {$classId} not found for conversation broadcast");
            return;
        }

        // Broadcast to instructor
        if ($class->instructor && $class->instructor->user) {
            $instructorConversations = $this->getInstructorConversationsData($class->instructor->user);

            event(new ConversationUpdated(
                0, // No specific student for instructor
                $instructorConversations,
                $class->instructor->id,
                null
            ));

            Log::info("Broadcast to instructor {$class->instructor->id}");
        }

        // Broadcast to each enrolled student and their families
        $enrolledStudents = $class->students()->whereHas('enrolls', function ($q) {
            $q->where('status', 'active');
        })->get();

        foreach ($enrolledStudents as $student) {
            // Broadcast to student if they have user account
            if ($student->user) {
                $studentConversations = $this->getStudentConversationsData($student->user);

                event(new ConversationUpdated(
                    $student->id,
                    $studentConversations,
                    null,
                    null
                ));

                Log::info("Broadcast to student {$student->id}");
            }

            // Broadcast to family
            if ($student->family && $student->family->user) {
                $familyConversations = $this->getFamilyStudentConversationsData($student->family->user, $student);

                event(new ConversationUpdated(
                    $student->id,
                    $familyConversations,
                    null,
                    $student->family->id
                ));

                Log::info("Broadcast to family {$student->family->id} for student {$student->id}");
            }
        }
    }

    /**
     * Get conversations data for instructor (returns array, not response)
     */
    private function getInstructorConversationsData($user)
    {
        $instructor = $user->instructor;

        $classes = Classes::where('instructor_id', $instructor->id)
            ->where('is_approved', true)
            ->with(['category', 'type'])
            ->withCount(['students' => function ($query) {
                $query->whereHas('enrolls', function ($q) {
                    $q->where('status', 'active');
                });
            }])
            ->get();

        $conversations = [];
        foreach ($classes as $class) {
            $lastMessage = MessageClass::where('class_id', $class->id)
                ->with(['user'])
                ->latest()
                ->first();

            $unreadCount = MessageClass::where('class_id', $class->id)
                ->where('sender_type', 'student')
                ->where('is_read', false)
                ->count();

            $conversations[] = [
                'class' => [
                    'id' => $class->id,
                    'name' => $class->name,
                    'category' => $class->category->name ?? 'General',
                    'students_count' => $class->students_count
                ],
                'last_message' => $lastMessage ? $this->formatMessageForApi($lastMessage, $user->id) : null,
                'unread_count' => $unreadCount
            ];
        }

        return $conversations;
    }

    /**
     * Get conversations data for student (returns array, not response)
     */
    private function getStudentConversationsData($user)
    {
        $student = $user->student;

        $classes = Classes::whereHas('students', function ($query) use ($student) {
            $query->where('student_id', $student->id)
                ->whereHas('enrolls', function ($q) {
                    $q->where('status', 'active');
                });
        })
            ->with(['category', 'type', 'instructor'])
            ->get();

        $conversations = [];
        foreach ($classes as $class) {
            $lastMessage = MessageClass::where('class_id', $class->id)
                ->with(['user'])
                ->latest()
                ->first();

            $unreadCount = MessageClass::where('class_id', $class->id)
                ->where('sender_type', 'instructor')
                ->where('is_read', false)
                ->count();

            $conversations[] = [
                'class' => [
                    'id' => $class->id,
                    'name' => $class->name,
                    'category' => $class->category->name ?? 'General',
                    'instructor' => $class->instructor->name
                ],
                'last_message' => $lastMessage ? $this->formatMessageForApi($lastMessage, $user->id) : null,
                'unread_count' => $unreadCount
            ];
        }

        return $conversations;
    }

    /**
     * Get conversations data for family student (returns array, not response)
     */
    private function getFamilyStudentConversationsData($user, $student)
    {
        $classes = Classes::whereHas('students', function ($query) use ($student) {
            $query->where('student_id', $student->id)
                ->whereHas('enrolls', function ($q) {
                    $q->where('status', 'active');
                });
        })
            ->with(['category', 'type', 'instructor'])
            ->get();

        $conversations = [];
        foreach ($classes as $class) {
            $lastMessage = MessageClass::where('class_id', $class->id)
                ->with(['user'])
                ->latest()
                ->first();

            $unreadCount = MessageClass::where('class_id', $class->id)
                ->where('sender_type', 'instructor')
                ->where('is_read', false)
                ->count();

            $conversations[] = [
                'class' => [
                    'id' => $class->id,
                    'name' => $class->name,
                    'category' => $class->category->name ?? 'General',
                    'instructor' => $class->instructor->name
                ],
                'student' => [
                    'id' => $student->id,
                    'name' => $student->name
                ],
                'last_message' => $lastMessage ? $this->formatMessageForApi($lastMessage, $user->id) : null,
                'unread_count' => $unreadCount
            ];
        }

        return $conversations;
    }

    /**
     * Broadcast conversation updates to public channels (No Auth Required)
     */
    private function broadcastPublicConversationUpdates($classId, $newMessage)
    {
        Log::info("Broadcasting PUBLIC conversation updates for class {$classId}");

        // Get class with instructor and students
        $class = Classes::with(['instructor', 'students'])
            ->find($classId);

        if (!$class) {
            Log::warning("Class {$classId} not found for public conversation broadcast");
            return;
        }

        // Broadcast to instructor (public channel)
        if ($class->instructor) {
            event(new PublicConversationUpdated(
                null, // student_id
                $class->instructor->id, // instructor_id
                $classId, // class_id
                [], // conversations data (can be empty for security)
                'new_message'
            ));

            Log::info("Public broadcast to instructor {$class->instructor->id}");
        }

        // Broadcast to each enrolled student (public channel)
        $enrolledStudents = $class->students()->whereHas('enrolls', function ($q) {
            $q->where('status', 'active');
        })->get();

        foreach ($enrolledStudents as $student) {
            event(new PublicConversationUpdated(
                $student->id, // student_id
                null, // instructor_id
                $classId, // class_id
                [], // conversations data (can be empty for security)
                'new_message'
            ));

            Log::info("Public broadcast to student {$student->id}");
        }

        // Broadcast to general class channel
        event(new PublicConversationUpdated(
            null, // student_id
            null, // instructor_id
            $classId, // class_id
            [
                'class_id' => $classId,
                'class_name' => $class->name,
                'last_message_preview' => substr($newMessage->message, 0, 50) . '...',
                'sender_type' => $newMessage->sender_type,
                'timestamp' => $newMessage->created_at->toISOString(),
            ],
            'new_message'
        ));

        Log::info("Public broadcast to class {$classId}");
    }
}
