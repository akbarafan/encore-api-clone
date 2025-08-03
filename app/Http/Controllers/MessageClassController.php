<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\MessageClass;
use App\Models\Classes;
use App\Models\User;
use App\Models\Student;
use App\Models\Instructor;
use App\Events\ConversationUpdated;
use App\Events\PublicConversationUpdated;
use App\Events\PublicMessageSent;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class MessageClassController extends Controller
{
    /**
     * Get family's students list
     */
    public function getFamilyStudents(Request $request)
    {
        try {
            $user = Auth::user();
            $userType = $this->getUserType($user);

            if ($userType !== 'family') {
                return response()->json([
                    'success' => false,
                    'message' => 'Access denied. Only family can access this endpoint'
                ], 403);
            }

            $students = $user->family->students()
                ->select('id', 'first_name', 'last_name')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $students->map(function ($student) {
                    return [
                        'id' => $student->id,
                        'name' => $student->name
                    ];
                })
            ]);
        } catch (\Exception $e) {
            Log::error('Error getting family students: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error getting students: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get user's conversations
     */
    public function getConversations(Request $request)
    {
        try {
            $user = Auth::user();
            Log::info('API: Getting conversations for user ID: ' . $user->id);

            $userType = $this->getUserType($user);
            Log::info('API: User type: ' . $userType);

            if ($userType === 'instructor') {
                return $this->getInstructorConversations($user);
            } elseif ($userType === 'family') {
                // Family harus pilih student mana
                $request->validate([
                    'student_id' => 'required|exists:students,id'
                ]);

                // Verify student belongs to this family
                $student = Student::where('id', $request->student_id)
                    ->where('family_id', $user->family->id)
                    ->first();

                if (!$student) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Student not found or not belongs to your family'
                    ], 403);
                }

                return $this->getFamilyStudentConversations($user, $student);
            } else {
                return $this->getStudentConversations($user);
            }
        } catch (\Exception $e) {
            Log::error('API: Error getting conversations: ' . $e->getMessage());
            Log::error('API: Stack trace: ' . $e->getTraceAsString());
            return response()->json([
                'success' => false,
                'message' => 'Error getting conversations: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Debug method untuk testing API
     */
    public function debugInfo(Request $request)
    {
        try {
            $user = Auth::user();
            $userType = $this->getUserType($user);

            $info = [
                'user_id' => $user->id,
                'user_name' => $user->name,
                'user_type' => $userType,
                'debug_info' => []
            ];

            if ($userType === 'instructor') {
                $instructor = $user->instructor;
                $classes = Classes::where('instructor_id', $instructor->id)
                    ->where('is_approved', true)
                    ->withCount(['students' => function ($query) {
                        $query->whereHas('enrolls', function ($q) {
                            $q->where('status', 'active');
                        });
                    }])
                    ->get();

                $info['instructor_id'] = $instructor->id;
                $info['instructor_name'] = $instructor->name;
                $info['total_classes'] = $classes->count();
                $info['classes'] = $classes->map(function ($class) {
                    return [
                        'id' => $class->id,
                        'name' => $class->name,
                        'students_count' => $class->students_count,
                        'is_approved' => $class->is_approved
                    ];
                });

                // Check messages
                $totalMessages = MessageClass::whereHas('class', function ($query) use ($instructor) {
                    $query->where('instructor_id', $instructor->id);
                })->count();

                $info['total_messages'] = $totalMessages;
            } elseif ($userType === 'student') {
                $student = $user->student;
                $classes = Classes::whereHas('students', function ($query) use ($student) {
                    $query->where('student_id', $student->id)
                        ->whereHas('enrolls', function ($q) {
                            $q->where('status', 'active');
                        });
                })->get();

                $info['student_id'] = $student->id;
                $info['student_name'] = $student->first_name . ' ' . $student->last_name;
                $info['total_classes'] = $classes->count();
                $info['classes'] = $classes->map(function ($class) {
                    return [
                        'id' => $class->id,
                        'name' => $class->name,
                        'instructor' => $class->instructor->name ?? 'N/A'
                    ];
                });
            }

            return response()->json([
                'success' => true,
                'data' => $info
            ]);
        } catch (\Exception $e) {
            Log::error('API Debug Error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Debug error: ' . $e->getMessage(),
                'user_id' => Auth::id() ?? 'Not authenticated'
            ], 500);
        }
    }

    /**
     * Get messages for a class
     */
    public function getMessages(Request $request, $classId)
    {
        try {
            Log::info("API: Getting messages for class {$classId}");

            $request->validate([
                'page' => 'nullable|integer|min:1',
                'per_page' => 'nullable|integer|min:1|max:100',
                'student_id' => 'nullable|exists:students,id'  // Required for family
            ]);

            $user = Auth::user();
            $userType = $this->getUserType($user);
            $perPage = $request->get('per_page', 50);

            Log::info("API: User type: {$userType}, User ID: {$user->id}");

            // For family, validate student_id and ownership
            $student = null;
            if ($userType === 'family') {
                if (!$request->student_id) {
                    return response()->json([
                        'success' => false,
                        'message' => 'student_id is required for family users'
                    ], 400);
                }

                $student = Student::where('id', $request->student_id)
                    ->where('family_id', $user->family->id)
                    ->first();

                if (!$student) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Student not found or not belongs to your family'
                    ], 403);
                }
            }

            // Verify user has access to this class
            if (!$this->userHasAccessToClass($user, $classId, $userType, $student)) {
                Log::error("API: User {$user->id} doesn't have access to class {$classId}");
                return response()->json([
                    'success' => false,
                    'message' => 'Access denied to this class'
                ], 403);
            }

            Log::info("API: User has access to class {$classId}");

            // Get messages with pagination (newest at bottom)
            $messages = MessageClass::where('class_id', $classId)
                ->with(['user', 'replyTo.user'])
                ->orderBy('created_at', 'asc')
                ->paginate($perPage);

            Log::info("API: Found {$messages->count()} messages for class {$classId}");

            // Mark messages as read for current user
            $this->markMessagesAsRead($classId, $user->id, $userType);

            // Transform messages data
            $messagesData = $messages->getCollection()->map(function ($message) use ($user) {
                return $this->formatMessageForApi($message, $user->id);
            });

            return response()->json([
                'success' => true,
                'data' => [
                    'messages' => $messagesData,
                    'student' => $student ? [
                        'id' => $student->id,
                        'name' => $student->name
                    ] : null,
                    'pagination' => [
                        'current_page' => $messages->currentPage(),
                        'last_page' => $messages->lastPage(),
                        'per_page' => $messages->perPage(),
                        'total' => $messages->total(),
                        'has_more' => $messages->hasMorePages()
                    ]
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('API: Error getting messages: ' . $e->getMessage());
            Log::error('API: Stack trace: ' . $e->getTraceAsString());
            return response()->json([
                'success' => false,
                'message' => 'Error getting messages: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Send a message
     */
    public function sendMessage(Request $request)
    {
        try {
            $request->validate([
                'class_id' => 'required|exists:classes,id',
                'message' => 'required|string|max:2000',
                'reply_to' => 'nullable|exists:message_classes,id',
                'is_announcement' => 'boolean',
                'student_id' => 'nullable|exists:students,id',  // Required for family
                'attachments' => 'nullable|array',
                'attachments.*' => 'file|max:10240|mimes:pdf,doc,docx,jpg,jpeg,png,zip'
            ]);

            $user = Auth::user();
            $userType = $this->getUserType($user);

            // For family, validate student_id and ownership
            $student = null;
            $actualSenderType = $userType;

            if ($userType === 'family') {
                if (!$request->student_id) {
                    return response()->json([
                        'success' => false,
                        'message' => 'student_id is required for family users'
                    ], 400);
                }

                $student = Student::where('id', $request->student_id)
                    ->where('family_id', $user->family->id)
                    ->first();

                if (!$student) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Student not found or not belongs to your family'
                    ], 403);
                }

                // Family sends message as "student"
                $actualSenderType = 'student';
            }

            // Verify user has access to this class
            if (!$this->userHasAccessToClass($user, $request->class_id, $userType, $student)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Access denied to this class'
                ], 403);
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
                'user_id' => $user->id,
                'class_id' => $request->class_id,
                'student_id' => $student ? $student->id : null, // Untuk family yang kirim atas nama student
                'sender_type' => $actualSenderType,
                'message' => $request->message,
                'reply_to' => $request->reply_to,
                'attachments' => !empty($attachments) ? $attachments : null,
                'is_announcement' => $userType === 'instructor' ? $request->boolean('is_announcement') : false,
                'is_read' => true,
                'read_at' => now()
            ]);

            $message->load(['user', 'replyTo.user']);

            // Broadcast message to private channel (with auth)
            try {
                event(new \App\Events\MessageSent($message));
            } catch (\Exception $e) {
                Log::warning('Failed to broadcast private message: ' . $e->getMessage());
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
                'data' => $this->formatMessageForApi($message, $user->id)
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
     * Delete a message
     */
    public function deleteMessage($messageId)
    {
        try {
            $user = Auth::user();

            $message = MessageClass::where('id', $messageId)
                ->where('user_id', $user->id)
                ->first();

            if (!$message) {
                return response()->json([
                    'success' => false,
                    'message' => 'Message not found or not authorized'
                ], 404);
            }

            $classId = $message->class_id;
            $message->delete();

            // Broadcast message deletion (optional)
            try {
                event(new \App\Events\MessageDeleted($messageId, $classId, $user->id));
            } catch (\Exception $e) {
                Log::warning('Failed to broadcast message deletion: ' . $e->getMessage());
            }

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
     * Toggle message pin
     */
    public function togglePin($messageId)
    {
        try {
            $user = Auth::user();
            $userType = $this->getUserType($user);

            $message = MessageClass::whereHas('class', function ($query) use ($user, $userType) {
                if ($userType === 'instructor') {
                    $query->where('instructor_id', $user->instructor->id);
                } else {
                    $query->whereHas('students', function ($q) use ($user) {
                        $q->where('user_id', $user->id);
                    });
                }
            })->find($messageId);

            if (!$message) {
                return response()->json([
                    'success' => false,
                    'message' => 'Message not found'
                ], 404);
            }

            $message->togglePin();

            // Broadcast pin status change (optional)
            try {
                event(new \App\Events\MessagePinned(
                    $messageId,
                    $message->class_id,
                    $message->is_pinned,
                    $user->id
                ));
            } catch (\Exception $e) {
                Log::warning('Failed to broadcast pin status: ' . $e->getMessage());
            }

            return response()->json([
                'success' => true,
                'message' => $message->is_pinned ? 'Message pinned' : 'Message unpinned',
                'data' => [
                    'is_pinned' => $message->is_pinned
                ]
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
     * Update typing status
     */
    public function updateTypingStatus(Request $request)
    {
        try {
            $request->validate([
                'class_id' => 'required|exists:classes,id',
                'is_typing' => 'required|boolean'
            ]);

            $user = Auth::user();
            $userType = $this->getUserType($user);

            // Verify user has access to this class
            if (!$this->userHasAccessToClass($user, $request->class_id, $userType)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Access denied to this class'
                ], 403);
            }

            $userName = $userType === 'instructor' ?
                $user->instructor->name : ($user->family ? $user->family->guardians_name : $user->name);

            // Broadcast typing status (optional)
            try {
                event(new \App\Events\UserTyping(
                    $request->class_id,
                    $user->id,
                    $userName,
                    $userType,
                    $request->boolean('is_typing')
                ));
            } catch (\Exception $e) {
                Log::warning('Failed to broadcast typing status: ' . $e->getMessage());
            }

            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            Log::error('Error updating typing status: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error updating typing status'
            ], 500);
        }
    }

    /**
     * Update online status
     */
    public function updateOnlineStatus(Request $request)
    {
        try {
            $request->validate([
                'class_id' => 'required|exists:classes,id',
                'is_online' => 'required|boolean'
            ]);

            $user = Auth::user();
            $userType = $this->getUserType($user);

            // Verify user has access to this class
            if (!$this->userHasAccessToClass($user, $request->class_id, $userType)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Access denied to this class'
                ], 403);
            }

            $userName = $userType === 'instructor' ?
                $user->instructor->name : ($user->family ? $user->family->guardians_name : $user->name);

            // Broadcast online status (optional)
            try {
                event(new \App\Events\UserOnlineStatus(
                    $request->class_id,
                    $user->id,
                    $userName,
                    $userType,
                    $request->boolean('is_online')
                ));
            } catch (\Exception $e) {
                Log::warning('Failed to broadcast online status: ' . $e->getMessage());
            }

            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            Log::error('Error updating online status: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error updating online status'
            ], 500);
        }
    }

    // Helper Methods

    private function getUserType($user)
    {
        if ($user->instructor) {
            return 'instructor';
        } elseif ($user->family) {
            return 'family';
        }

        throw new \Exception('User type not found');
    }

    private function getInstructorConversations($user)
    {
        $instructor = $user->instructor;

        // Debug log
        Log::info('API: Getting instructor conversations for instructor ID: ' . $instructor->id);

        $classes = Classes::where('instructor_id', $instructor->id)
            ->where('is_approved', true)
            ->with(['category', 'type'])
            ->withCount(['students' => function ($query) {
                $query->whereHas('enrolls', function ($q) {
                    $q->where('status', 'active');
                });
            }])
            ->get();

        Log::info('API: Found ' . $classes->count() . ' classes for instructor');

        $conversations = [];
        foreach ($classes as $class) {
            // Show ALL classes for testing (ignore student count temporarily)
            Log::info("API: Processing class {$class->name} with {$class->students_count} students");

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

            Log::info("API: Added conversation for class {$class->name}");
        }

        Log::info('API: Total conversations: ' . count($conversations));

        return response()->json([
            'success' => true,
            'data' => $conversations
        ]);
    }

    private function getFamilyStudentConversations($user, $student)
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

        return response()->json([
            'success' => true,
            'data' => $conversations
        ]);
    }

    private function getStudentConversations($user)
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

        return response()->json([
            'success' => true,
            'data' => $conversations
        ]);
    }

    private function userHasAccessToClass($user, $classId, $userType, $student = null)
    {
        if ($userType === 'instructor') {
            return Classes::where('id', $classId)
                ->where('instructor_id', $user->instructor->id)
                ->where('is_approved', true)
                ->exists();
        } elseif ($userType === 'family') {
            // Family has access if their student is enrolled in the class
            if (!$student) {
                return false;
            }

            return Classes::where('id', $classId)
                ->whereHas('students', function ($query) use ($student) {
                    $query->where('student_id', $student->id)
                        ->whereHas('enrolls', function ($q) {
                            $q->where('status', 'active');
                        });
                })
                ->exists();
        } else {
            // Direct student access (if still used)
            return Classes::where('id', $classId)
                ->whereHas('students', function ($query) use ($user) {
                    $query->where('user_id', $user->id)
                        ->whereHas('enrolls', function ($q) {
                            $q->where('status', 'active');
                        });
                })
                ->exists();
        }
    }

    private function markMessagesAsRead($classId, $userId, $userType)
    {
        $query = MessageClass::where('class_id', $classId)
            ->where('is_read', false);

        if ($userType === 'instructor') {
            $query->where('sender_type', 'student');
        } else {
            $query->where('sender_type', 'instructor');
        }

        $query->update(['is_read' => true, 'read_at' => now()]);
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
