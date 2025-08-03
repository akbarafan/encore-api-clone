<?php

namespace App\Http\Controllers;

use App\Models\Classes;
use Illuminate\Http\Request;

class ClassStudentController extends Controller
{
    public function enrollStudent(Request $request, $studentId)
    {
        return $this->handleTryCatch(function () use ($request, $studentId) {
            $request->validate([
                'class_id' => 'required|exists:classes,id',
            ]);

            $student = $request->user()->family->students()->where('id', $studentId)->first();
            if (! $student) {
                return $this->errorResponse('Student not found in your family.', null, 404);
            }

            $student->classes()->syncWithoutDetaching([$request->class_id]);

            return $this->successResponse(null, 'Student enrolled to class successfully');
        });
    }

    /**
     * Remove student from class
     */
    public function unenrollStudent(Request $request, $studentId)
    {
        return $this->handleTryCatch(function () use ($request, $studentId) {
            $request->validate([
                'class_id' => 'required|exists:classes,id',
            ]);

            $student = $request->user()->family->students()->where('id', $studentId)->first();
            if (! $student) {
                return $this->errorResponse('Student not found in your family.', null, 404);
            }

            $student->classes()->detach($request->class_id);

            return $this->successResponse(null, 'Student removed from class successfully');
        });
    }

    /**
     * List student's enrolled classes
     */
    public function myStudentsClasses(Request $request)
    {
        return $this->handleTryCatch(function () use ($request) {
            $family = $request->user()->family;
            $students = $family ? $family->students()->with('classes')->get() : [];

            return $this->successResponse($students, 'Students classes retrieved successfully');
        });
    }

    /**
     * Get all students enrolled in a specific class
     */
    public function getClassStudents(Request $request, $classId)
    {
        return $this->handleTryCatch(function () use ($classId) {
            $class = Classes::with('students')->findOrFail($classId);
            
            return $this->successResponse($class->students, 'Class students retrieved successfully');
        });
    }

    /**
     * Get enrollment status for a student in a class
     */
    public function getEnrollmentStatus(Request $request, $studentId, $classId)
    {
        return $this->handleTryCatch(function () use ($request, $studentId, $classId) {
            $student = $request->user()->family->students()->where('id', $studentId)->first();
            if (! $student) {
                return $this->errorResponse('Student not found in your family.', null, 404);
            }

            $isEnrolled = $student->classes()->where('class_id', $classId)->exists();
            
            return $this->successResponse([
                'student_id' => $studentId,
                'class_id' => $classId,
                'is_enrolled' => $isEnrolled
            ], 'Enrollment status retrieved successfully');
        });
    }

    /**
     * Bulk enroll student to multiple classes
     */
    public function bulkEnrollStudent(Request $request, $studentId)
    {
        return $this->handleTryCatch(function () use ($request, $studentId) {
            $request->validate([
                'class_ids' => 'required|array',
                'class_ids.*' => 'exists:classes,id',
            ]);

            $student = $request->user()->family->students()->where('id', $studentId)->first();
            if (! $student) {
                return $this->errorResponse('Student not found in your family.', null, 404);
            }

            $student->classes()->syncWithoutDetaching($request->class_ids);

            return $this->successResponse(null, 'Student enrolled to classes successfully');
        });
    }

    /**
     * Bulk unenroll student from multiple classes
     */
    public function bulkUnenrollStudent(Request $request, $studentId)
    {
        return $this->handleTryCatch(function () use ($request, $studentId) {
            $request->validate([
                'class_ids' => 'required|array',
                'class_ids.*' => 'exists:classes,id',
            ]);

            $student = $request->user()->family->students()->where('id', $studentId)->first();
            if (! $student) {
                return $this->errorResponse('Student not found in your family.', null, 404);
            }

            $student->classes()->detach($request->class_ids);

            return $this->successResponse(null, 'Student removed from classes successfully');
        });
    }
}
