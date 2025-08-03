<?php
// routes/channels.php (UPDATE existing file)

use Illuminate\Support\Facades\Broadcast;
use App\Models\Classes;

/*
|--------------------------------------------------------------------------
| Broadcast Channels
|--------------------------------------------------------------------------
*/

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

// Chat Channels
Broadcast::channel('class-chat.{classId}', function ($user, $classId) {
    // Check if user (instructor, family, or student) has access to this class
    if ($user->instructor) {
        return Classes::where('id', $classId)
            ->where('instructor_id', $user->instructor->id)
            ->where('is_approved', true)
            ->exists();
    } elseif ($user->family) {
        // Family has access if any of their students are enrolled in this class
        return Classes::where('id', $classId)
            ->whereHas('students', function ($query) use ($user) {
                $query->where('family_id', $user->family->id)
                    ->whereHas('enrolls', function ($q) {
                        $q->where('status', 'active');
                    });
            })
            ->exists();
    } elseif ($user->student) {
        return Classes::where('id', $classId)
            ->whereHas('students', function ($query) use ($user) {
                $query->where('user_id', $user->id)
                    ->whereHas('enrolls', function ($q) {
                        $q->where('status', 'active');
                    });
            })
            ->exists();
    }

    return false;
});

// Conversation Updates Channels
Broadcast::channel('student-conversations.{studentId}', function ($user, $studentId) {
    // Check if user is the student or family member of the student
    if ($user->student && $user->student->id == $studentId) {
        return true;
    }
    
    if ($user->family) {
        return $user->family->students()->where('id', $studentId)->exists();
    }
    
    return false;
});

Broadcast::channel('instructor-conversations.{instructorId}', function ($user, $instructorId) {
    // Check if user is the instructor
    return $user->instructor && $user->instructor->id == $instructorId;
});

Broadcast::channel('family-conversations.{familyId}', function ($user, $familyId) {
    // Check if user is part of the family
    return $user->family && $user->family->id == $familyId;
});

// Public Conversation Channels (No Auth Required)
Broadcast::channel('public-conversations', function () {
    // Always return true for public channel
    return true;
});

Broadcast::channel('public-student-conversations.{studentId}', function () {
    // Public channel - no authentication required
    return true;
});

Broadcast::channel('public-instructor-conversations.{instructorId}', function () {
    // Public channel - no authentication required  
    return true;
});

Broadcast::channel('public-class-conversations.{classId}', function () {
    // Public channel for class conversations - no auth required
    return true;
});

Broadcast::channel('public-class-chat.{classId}', function () {
    // Public channel for class chat messages - no auth required
    return true;
});
