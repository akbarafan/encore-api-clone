<?php

namespace App\Helpers;

use App\Models\User;
use App\Models\Instructor;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;

class InstructorHelper
{
    /**
     * Get current authenticated instructor user
     * Falls back to dummy instructor for development if not authenticated
     */
    public static function getCurrentInstructor()
    {
        // Check if user is authenticated and is an instructor
        if (Auth::check() && Auth::user()->role === 2) {
            return Auth::user();
        }

        // Fallback to dummy instructor for development/testing
        return self::getDummyInstructor();
    }

    /**
     * Get current authenticated instructor record
     * Falls back to dummy instructor record for development
     */
    public static function getCurrentInstructorRecord()
    {
        $user = self::getCurrentInstructor();

        // Try to find existing instructor record
        $instructor = Instructor::where('user_id', $user->id)->first();

        if (!$instructor) {
            // Create instructor record if not exists
            $instructor = Instructor::create([
                'user_id' => $user->id,
                'name' => $user->name,
                'availability' => 'Full-Time',
                'payrate' => 100.00
            ]);
        }

        return $instructor;
    }

    /**
     * Get current authenticated instructor ID
     */
    public static function getCurrentInstructorId()
    {
        return self::getCurrentInstructor()->id;
    }

    /**
     * Check if current user is an instructor
     */
    public static function isInstructor()
    {
        return Auth::check() && Auth::user()->role === 2;
    }

    /**
     * Get dummy instructor user for development (DEPRECATED - use getCurrentInstructor instead)
     * @deprecated Use getCurrentInstructor() instead
     */
    public static function getDummyInstructor()
    {
        // Try to find existing dummy instructor first
        $user = User::where('email', 'instructor@example.com')->first();

        if (!$user) {
            // Create dummy instructor user if not exists
            $userId = Str::uuid();
            $user = User::create([
                'id' => $userId,
                'name' => 'John Doe',
                'email' => 'instructor@example.com', // Fixed: was john@example.com
                'password' => bcrypt('password'),
                'role' => 2 // Instructor role
            ]);
        }

        return $user;
    }

    /**
     * Get dummy instructor record for development (DEPRECATED)
     * @deprecated Use getCurrentInstructorRecord() instead
     */
    public static function getDummyInstructorRecord()
    {
        $user = self::getDummyInstructor();

        // Try to find existing instructor record
        $instructor = Instructor::where('user_id', $user->id)->first();

        if (!$instructor) {
            // Create dummy instructor record if not exists
            $instructor = Instructor::create([
                'user_id' => $user->id,
                'name' => 'John Doe',
                'availability' => 'Full-Time',
                'payrate' => 100.00
            ]);
        }

        return $instructor;
    }

    /**
     * Get dummy instructor user ID for quick access (DEPRECATED)
     * @deprecated Use getCurrentInstructorId() instead
     */
    public static function getDummyInstructorId()
    {
        return self::getDummyInstructor()->id;
    }

    /**
     * Get instructor by user ID
     */
    public static function getInstructorByUserId($userId)
    {
        $user = User::where('id', $userId)->where('role', 2)->first();

        if (!$user) {
            return null;
        }

        return $user;
    }

    /**
     * Get instructor record by user ID
     */
    public static function getInstructorRecordByUserId($userId)
    {
        return Instructor::where('user_id', $userId)->first();
    }

    /**
     * Create or update instructor profile
     */
    public static function createOrUpdateInstructorProfile($userId, $data)
    {
        $instructor = Instructor::where('user_id', $userId)->first();

        if ($instructor) {
            $instructor->update($data);
        } else {
            $data['user_id'] = $userId;
            $instructor = Instructor::create($data);
        }

        return $instructor;
    }
}
