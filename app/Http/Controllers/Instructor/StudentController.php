<?php

namespace App\Http\Controllers\Instructor;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Instructor;
use App\Models\Student;
use App\Models\Classes;
use App\Models\Enroll;
use App\Helpers\InstructorHelper;

class StudentController extends Controller
{
    private function getCurrentInstructor()
    {
        // Use consistent dummy instructor (no authentication)
        return InstructorHelper::getCurrentInstructorRecord();
    }

    public function index(Request $request)
    {
        $instructor = $this->getCurrentInstructor();

        // Query untuk student yang ada di class instructor dengan filter
        $query = Student::whereHas('enrolls', function($q) use ($instructor) {
                $q->whereHas('class', function($classQuery) use ($instructor) {
                    $classQuery->where('instructor_id', $instructor->id)
                              ->where('is_approved', true);
                })
                ->where('status', 'active');
            })
            ->with([
                'activeClasses' => function($q) use ($instructor) {
                    $q->where('instructor_id', $instructor->id)
                      ->where('is_approved', true)
                      ->with(['category', 'type', 'season', 'classTime', 'classLocation']);
                },
                'enrolls' => function($q) use ($instructor) {
                    $q->whereHas('class', function($classQuery) use ($instructor) {
                        $classQuery->where('instructor_id', $instructor->id)
                                  ->where('is_approved', true);
                    })
                    ->where('status', 'active')
                    ->with('class');
                },
                'family',
                'attendances' => function($q) {
                    $q->latest()->limit(5);
                }
            ]);

        // Filter by class/course
        if ($request->filled('class_id')) {
            $query->whereHas('enrolls', function($q) use ($request) {
                $q->where('class_id', $request->class_id)
                  ->where('status', 'active');
            });
        }

        // Filter by class category
        if ($request->filled('category_id')) {
            $query->whereHas('activeClasses', function($q) use ($request) {
                $q->where('class_category_id', $request->category_id);
            });
        }

        // Filter by class type
        if ($request->filled('type_id')) {
            $query->whereHas('activeClasses', function ($q) use ($request) {
                $q->where('class_type_id', $request->type_id);
            });
        }

        // Search by name
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        // Filter by enrollment status
        if ($request->filled('status')) {
            if ($request->status === 'active') {
                $query->whereHas('attendances', function ($q) {
                    $q->where('date', '>=', now()->subDays(30));
                });
            } elseif ($request->status === 'inactive') {
                $query->whereDoesntHave('attendances', function ($q) {
                    $q->where('date', '>=', now()->subDays(30));
                });
            }
        }

        $students = $query->get();

        // Get instructor's classes for filter dropdown
        $instructorClasses = Classes::where('instructor_id', $instructor->id)
            ->where('is_approved', true)
            ->with(['category', 'type'])
            ->get();

        // Get categories and types for filtering
        $categories = \App\Models\ClassCategory::whereHas('classes', function ($q) use ($instructor) {
            $q->where('instructor_id', $instructor->id)->where('is_approved', true);
        })->get();

        $types = \App\Models\ClassType::whereHas('classes', function ($q) use ($instructor) {
            $q->where('instructor_id', $instructor->id)->where('is_approved', true);
        })->get();

        // Dashboard stats for students
        $totalStudents = Student::whereHas('enrolls', function ($q) use ($instructor) {
            $q->whereHas('class', function ($classQuery) use ($instructor) {
                $classQuery->where('instructor_id', $instructor->id)
                    ->where('is_approved', true);
            })
                ->where('status', 'active');
        })->count();

        $activeStudents = Student::whereHas('enrolls', function ($q) use ($instructor) {
            $q->whereHas('class', function ($classQuery) use ($instructor) {
                $classQuery->where('instructor_id', $instructor->id)
                    ->where('is_approved', true);
            })
                ->where('status', 'active');
        })->whereHas('attendances', function ($q) {
            $q->where('date', '>=', now()->subDays(30));
        })->count();

        $recentEnrollments = Enroll::byInstructor($instructor->id)
            ->where('created_at', '>=', now()->subDays(7))
            ->count();

        // Calculate stats
        $stats = [
            'total' => $totalStudents,
            'active' => $activeStudents,
            'recent' => $recentEnrollments,
            'private_classes' => $students->filter(function ($student) {
                return $student->activeClasses->where('type.name', 'Private')->count() > 0;
            })->count(),
            'group_classes' => $students->filter(function ($student) {
                return $student->activeClasses->where('type.name', 'Group')->count() > 0;
            })->count()
        ];

        // Group students by class category
        $studentsByCategory = $students->groupBy(function ($student) {
            $firstClass = $student->activeClasses->first();
            return $firstClass && $firstClass->category ? $firstClass->category->name : 'Uncategorized';
        });

        return view('instructor.my-students', compact(
            'students',
            'instructorClasses',
            'categories',
            'types',
            'stats',
            'studentsByCategory'
        ));
    }

    public function show($id)
    {
        $instructor = $this->getCurrentInstructor();

        $student = Student::whereHas('enrolls', function ($query) use ($instructor) {
            $query->whereHas('class', function ($classQuery) use ($instructor) {
                $classQuery->where('instructor_id', $instructor->id)
                    ->where('is_approved', true);
            })
                ->where('status', 'active');
        })->with([
            'activeClasses' => function ($query) use ($instructor) {
                $query->where('instructor_id', $instructor->id)
                    ->where('is_approved', true)
                    ->with(['category', 'type', 'season', 'classTime', 'classLocation']);
            },
            'enrolls' => function ($query) use ($instructor) {
                $query->whereHas('class', function ($classQuery) use ($instructor) {
                    $classQuery->where('instructor_id', $instructor->id)
                        ->where('is_approved', true);
                })
                    ->where('status', 'active')
                    ->with('class');
            },
            'family',
            'attendances' => function ($query) {
                $query->orderBy('date', 'desc')->limit(10);
            }
        ])->findOrFail($id);

        return response()->json([
            'student' => $student,
            'classes' => $student->activeClasses,
            'enrolls' => $student->enrolls,
            'recent_attendances' => $student->attendances,
            'total_classes' => $student->activeClasses->count(),
            'attendance_rate' => $this->calculateAttendanceRate($student)
        ]);
    }

    private function calculateAttendanceRate($student)
    {
        $totalSessions = 0;
        foreach ($student->activeClasses as $class) {
            $totalSessions += $class->schedules()->count();
        }

        $attendedSessions = $student->attendances()->count();

        return $totalSessions > 0 ? round(($attendedSessions / $totalSessions) * 100, 2) : 0;
    }

    public function export(Request $request)
    {
        $instructor = $this->getCurrentInstructor();

        // Logic untuk export students
        // Implementasi sesuai kebutuhan

        return response()->json(['message' => 'Export functionality would be implemented here']);
    }
}
