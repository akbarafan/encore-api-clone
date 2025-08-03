<?php

namespace App\Http\Controllers;

use App\Models\Student;
use App\Models\Schedule;
use App\Models\File;
use App\Models\Classes;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class StudentController extends Controller
{
    public function index(Request $request)
    {
        $family = $request->user()->family;
        if (! $family) {
            return response()->json(['message' => 'Family profile not found.'], 404);
        }

        $students = $family->students()
            ->with(['enrolls.class', 'attendances'])
            ->get();

        return response()->json(['students' => $students]);
    }

    public function store(Request $request)
    {
        $family = $request->user()->family;
        if (! $family) {
            return response()->json(['message' => 'Family profile not found.'], 404);
        }

        $validated = $request->validate([
            'first_name' => 'required|string',
            'last_name' => 'required|string',
            'gender' => 'required|in:male,female',
            'medical_condition' => 'nullable|string',
            'one_time_reg_fee' => 'nullable|numeric',
            'email' => 'nullable|email',
            'phone' => 'nullable|string',
            'date_of_birth' => 'nullable|date',
            'emergency_contact' => 'nullable|string',
            'medical_notes' => 'nullable|string'
        ]);

        $student = Student::create(array_merge($validated, [
            'family_id' => $family->id,
        ]));

        return response()->json([
            'message' => 'Student created successfully.',
            'student' => $student,
        ], 201);
    }

    public function show(Request $request, $id)
    {
        $student = $this->findStudentForFamily($request, $id);
        $student->load([
            'enrolls.class.instructor',
            'attendances.schedule',
        ]);

        return response()->json($student);
    }

    public function update(Request $request, $id)
    {
        $student = $this->findStudentForFamily($request, $id);

        $validated = $request->validate([
            'first_name' => 'string',
            'last_name' => 'string',
            'gender' => 'in:male,female',
            'medical_condition' => 'nullable|string',
            'one_time_reg_fee' => 'nullable|numeric',
            'email' => 'nullable|email',
            'phone' => 'nullable|string',
            'date_of_birth' => 'nullable|date',
            'emergency_contact' => 'nullable|string',
            'medical_notes' => 'nullable|string',
            'status' => 'in:active,inactive'
        ]);

        $student->update($validated);

        return response()->json([
            'message' => 'Student updated successfully.',
            'student' => $student,
        ]);
    }

    public function destroy(Request $request, $id)
    {
        $student = $this->findStudentForFamily($request, $id);
        $student->delete();

        return response()->json(['message' => 'Student deleted successfully.']);
    }

    /**
     * Get student's enrolled classes
     */
    public function getClasses(Request $request, $id)
    {
        try {
            $student = $this->findStudentForFamily($request, $id);

            $classes = $student->getCurrentClasses();

            return response()->json([
                'success' => true,
                'message' => 'Student classes retrieved successfully',
                'data' => [
                    'student' => $student,
                    'classes' => $classes
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve student classes',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get student's schedules
     */
    public function getSchedules(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'class_id' => 'nullable|exists:classes,id'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $student = $this->findStudentForFamily($request, $id);

            $schedules = $student->getSchedules(
                $request->start_date,
                $request->end_date
            );

            // Filter by class if specified
            if ($request->class_id) {
                $schedules = $schedules->where('class_id', $request->class_id);
            }

            return response()->json([
                'success' => true,
                'message' => 'Student schedules retrieved successfully',
                'data' => [
                    'student' => $student,
                    'schedules' => $schedules->values()
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve student schedules',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get student's available files
     */
    public function getAvailableFiles(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'schedule_id' => 'nullable|exists:schedules,id',
            'class_id' => 'nullable|exists:classes,id',
            'file_category' => 'nullable|in:material,assignment,resource,other'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $student = $this->findStudentForFamily($request, $id);

            $files = $student->getAvailableFiles($request->schedule_id);

            // Filter by class if specified
            if ($request->class_id) {
                $files = $files->filter(function ($file) use ($request) {
                    return $file->schedules->contains(function ($schedule) use ($request) {
                        return $schedule->class_id == $request->class_id;
                    });
                });
            }

            // Filter by file category if specified
            if ($request->file_category) {
                $files = $files->where('file_category', $request->file_category);
            }

            return response()->json([
                'success' => true,
                'message' => 'Student available files retrieved successfully',
                'data' => [
                    'student' => $student,
                    'files' => $files->values()
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve student available files',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get student's learning progress
     */
    public function getLearningProgress(Request $request, $id)
    {
        try {
            $student = $this->findStudentForFamily($request, $id);

            $progress = $student->getLearningProgress();

            return response()->json([
                'success' => true,
                'message' => 'Student learning progress retrieved successfully',
                'data' => [
                    'student' => $student,
                    'progress' => $progress
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve student learning progress',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get student's attendance history
     */
    public function getAttendanceHistory(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'class_id' => 'nullable|exists:classes,id',
            'status' => 'nullable|in:present,absent,late,excused'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $student = $this->findStudentForFamily($request, $id);

            $query = $student->attendances()->with(['schedule.class']);

            // Filter by date range
            if ($request->start_date) {
                $query->whereHas('schedule', function ($q) use ($request) {
                    $q->where('date', '>=', $request->start_date);
                });
            }

            if ($request->end_date) {
                $query->whereHas('schedule', function ($q) use ($request) {
                    $q->where('date', '<=', $request->end_date);
                });
            }

            // Filter by class
            if ($request->class_id) {
                $query->whereHas('schedule', function ($q) use ($request) {
                    $q->where('class_id', $request->class_id);
                });
            }

            // Filter by status
            if ($request->status) {
                $query->where('status', $request->status);
            }

            $attendances = $query->orderBy('created_at', 'desc')->get();

            // Calculate statistics
            $stats = [
                'total_sessions' => $attendances->count(),
                'present_count' => $attendances->where('status', 'present')->count(),
                'absent_count' => $attendances->where('status', 'absent')->count(),
                'late_count' => $attendances->where('status', 'late')->count(),
                'excused_count' => $attendances->where('status', 'excused')->count(),
                'attendance_rate' => $attendances->count() > 0 ?
                    round(($attendances->whereIn('status', ['present', 'late'])->count() / $attendances->count()) * 100, 2) : 0
            ];

            return response()->json([
                'success' => true,
                'message' => 'Student attendance history retrieved successfully',
                'data' => [
                    'student' => $student,
                    'attendances' => $attendances,
                    'statistics' => $stats
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve student attendance history',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get today's schedule for student
     */
    public function getTodaySchedule(Request $request, $id)
    {
        try {
            $student = $this->findStudentForFamily($request, $id);

            $todaySchedules = $student->getSchedules(today(), today());

            return response()->json([
                'success' => true,
                'message' => 'Student today schedule retrieved successfully',
                'data' => [
                    'student' => $student,
                    'schedules' => $todaySchedules,
                    'count' => $todaySchedules->count()
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve student today schedule',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get upcoming schedules for student
     */
    public function getUpcomingSchedules(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'days' => 'nullable|integer|min:1|max:30'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $student = $this->findStudentForFamily($request, $id);
            $days = $request->get('days', 7);

            $upcomingSchedules = $student->getSchedules(
                today()->addDay(),
                today()->addDays($days)
            );

            return response()->json([
                'success' => true,
                'message' => 'Student upcoming schedules retrieved successfully',
                'data' => [
                    'student' => $student,
                    'schedules' => $upcomingSchedules,
                    'count' => $upcomingSchedules->count(),
                    'days_ahead' => $days
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve student upcoming schedules',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Check if student is enrolled in specific class
     */
    public function checkEnrollment(Request $request, $id, $classId)
    {
        try {
            $student = $this->findStudentForFamily($request, $id);
            $class = Classes::findOrFail($classId);

            $isEnrolled = $student->isEnrolledInClass($classId);
            $enrollment = null;

            if ($isEnrolled) {
                $enrollment = $student->enrolls()
                    ->where('class_id', $classId)
                    ->where('status', 'active')
                    ->with('class')
                    ->first();
            }

            return response()->json([
                'success' => true,
                'message' => 'Enrollment status checked successfully',
                'data' => [
                    'student' => $student,
                    'class' => $class,
                    'is_enrolled' => $isEnrolled,
                    'enrollment' => $enrollment
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to check enrollment status',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get student dashboard data
     */
    public function getDashboard(Request $request, $id)
    {
        try {
            $student = $this->findStudentForFamily($request, $id);

            $dashboard = [
                'student' => $student,
                'progress' => $student->getLearningProgress(),
                'today_schedules' => $student->getSchedules(today(), today()),
                'upcoming_schedules' => $student->getSchedules(
                    today()->addDay(),
                    today()->addDays(7)
                ),
                'current_classes' => $student->getCurrentClasses()
            ];

            return response()->json([
                'success' => true,
                'message' => 'Student dashboard data retrieved successfully',
                'data' => $dashboard
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve student dashboard data',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // Helper untuk ambil student dari family yang login
    private function findStudentForFamily(Request $request, $studentId)
    {
        $family = $request->user()->family;
        if (! $family) {
            abort(404, 'Family profile not found.');
        }

        return $family->students()->findOrFail($studentId);
    }
}
