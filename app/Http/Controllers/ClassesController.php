<?php

namespace App\Http\Controllers;

use App\Models\Classes;
use App\Models\Enroll;
use App\Services\FCMService;
use Illuminate\Http\Request;

class ClassesController extends Controller
{
    public function index(Request $request)
    {
        return $this->handleTryCatch(function () use ($request) {
            $query = Classes::with(['season', 'category', 'type', 'classTime', 'classLocation', 'instructor', 'students', 'schedules']);

            // Filter berdasarkan class_time_id
            if ($request->has('class_time_id') && $request->class_time_id != '') {
                $query->where('class_time_id', $request->class_time_id);
            }

            // Filter berdasarkan class_category_id
            if ($request->has('class_category_id') && $request->class_category_id != '') {
                $query->where('class_category_id', $request->class_category_id);
            }

            // Filter berdasarkan class_type_id
            if ($request->has('class_type_id') && $request->class_type_id != '') {
                $query->where('class_type_id', $request->class_type_id);
            }

            // Filter berdasarkan instructor_id
            if ($request->has('instructor_id') && $request->instructor_id != '') {
                $query->where('instructor_id', $request->instructor_id);
            }

            // Filter berdasarkan nama course
            if ($request->has('name') && $request->name != '') {
                $query->where('name', 'like', '%' . $request->name . '%');
            }

            // Filter berdasarkan class_location_id
            if ($request->has('class_location_id') && $request->class_location_id != '') {
                $query->where('class_location_id', $request->class_location_id);
            }

            // Sorting berdasarkan favorit (jumlah student enrollment)
            if ($request->has('sort_by_favorite') && $request->sort_by_favorite == 'true') {
                $query->withCount('students')->orderBy('students_count', 'desc');
            } else {
                $query->orderBy('created_at', 'desc');
            }

            $classes = $query->get();
            $data = [];
            foreach ($classes as $class) {
                $data[] = [
                    'id' => $class->id,
                    'name' => $class->name,
                    'description' => $class->description,
                    'cost' => $class->cost,
                    'scheduled_at' => $class->scheduled_at,
                    'is_approved' => $class->is_approved,
                    'season' => $class->season ? $class->season->name : null,
                    'category' => $class->category ? $class->category->name : null,
                    'type' => $class->type ? $class->type->name : null,
                    'class_time' => $class->classTime ? $class->classTime->name : null,
                    'class_location' => $class->classLocation ? $class->classLocation->city : null,
                    'instructor' => $class->instructor ? $class->instructor->name : null,
                    'students_count' => $class->students->count(),
                    'schedules' => $class->schedules,
                ];
            }
            return $this->successResponse($data, 'Classes retrieved successfully');
        });
    }

    public function store(Request $request)
    {
        return $this->handleTryCatch(function () use ($request) {
            $validated = $request->validate([
                'season_id' => 'required|exists:seasons,id',
                'class_category_id' => 'required|exists:class_categories,id',
                'class_type_id' => 'required|exists:class_types,id',
                'class_time_id' => 'required|exists:class_times,id',
                'class_location_id' => 'required|exists:class_locations,id',
                'name' => 'required|string',
                'description' => 'nullable|string',
                'instructor_id' => 'required|exists:instructors,id',
                'cost' => 'required|numeric',
                'is_approved' => 'boolean',
            ]);

            $class = Classes::create($validated);

            return $this->successResponse($class, 'Class created successfully', 201);
        });
    }

    public function show($id)
    {
        return $this->handleTryCatch(function () use ($id) {
            $class = Classes::with(['season', 'category', 'type', 'classTime', 'classLocation', 'instructor', 'students', 'schedules'])->findOrFail($id);
            return $this->successResponse($class, 'Class retrieved successfully');
        });
    }

    public function update(Request $request, $id)
    {
        return $this->handleTryCatch(function () use ($request, $id) {
            $validated = $request->validate([
                'season_id' => 'exists:seasons,id',
                'class_category_id' => 'exists:class_categories,id',
                'class_type_id' => 'exists:class_types,id',
                'class_time_id' => 'exists:class_times,id',
                'class_location_id' => 'exists:class_locations,id',
                'name' => 'string',
                'description' => 'nullable|string',
                'instructor_id' => 'exists:instructors,id',
                'cost' => 'numeric',
                'is_approved' => 'boolean',
            ]);

            $class = Classes::findOrFail($id);
            $class->update($validated);

            return $this->successResponse($class, 'Class updated successfully');
        });
    }

    public function destroy($id)
    {
        return $this->handleTryCatch(function () use ($id) {
            $class = Classes::findOrFail($id);
            $class->delete();

            return $this->successResponse(null, 'Class deleted successfully');
        });
    }

    /**
     * Approve class
     */
    public function approve($id)
    {
        return $this->handleTryCatch(function () use ($id) {
            $class = Classes::findOrFail($id);
            $class->update(['is_approved' => true]);

            return $this->successResponse($class, 'Class approved successfully');
        });
    }

    /**
     * Reject/Unapprove class
     */
    public function reject($id)
    {
        return $this->handleTryCatch(function () use ($id) {
            $class = Classes::findOrFail($id);
            $class->update(['is_approved' => false]);

            return $this->successResponse($class, 'Class rejected successfully');
        });
    }

    /**
     * Get classes by instructor
     */
    public function getByInstructor($instructorId)
    {
        return $this->handleTryCatch(function () use ($instructorId) {
            $classes = Classes::where('instructor_id', $instructorId)
                ->with(['season', 'category', 'type', 'classTime', 'classLocation', 'instructor', 'students', 'schedules'])
                ->get();

            return $this->successResponse($classes, 'Instructor classes retrieved successfully');
        });
    }

    /**
     * Get classes by season
     */
    public function getBySeason($seasonId)
    {
        return $this->handleTryCatch(function () use ($seasonId) {
            $classes = Classes::where('season_id', $seasonId)
                ->with(['season', 'category', 'type', 'classTime', 'classLocation', 'instructor', 'students', 'schedules'])
                ->get();

            return $this->successResponse($classes, 'Season classes retrieved successfully');
        });
    }

    /**
     * Get approved classes only
     */
    public function getApproved()
    {
        return $this->handleTryCatch(function () {
            $classes = Classes::where('is_approved', true)
                ->with(['season', 'category', 'type', 'classTime', 'classLocation', 'instructor', 'students', 'schedules'])
                ->get();

            return $this->successResponse($classes, 'Approved classes retrieved successfully');
        });
    }

    public function getStudentClasses($studentId)
    {
        $classes = Classes::whereHas('enrolls', function ($query) use ($studentId) {
            $query->where('student_id', $studentId);
        })
            ->with(['season', 'category', 'type', 'classTime', 'classLocation', 'instructor', 'students', 'schedules'])
            ->get();

        $data = [];
        foreach ($classes as $class) {
            $data[] = [
                'id' => $class->id,
                'name' => $class->name,
                'description' => $class->description,
                'cost' => $class->cost,
                'scheduled_at' => $class->scheduled_at,
                'is_approved' => $class->is_approved,
                'season' => $class->season ? $class->season->name : null,
                'category' => $class->category ? $class->category->name : null,
                'type' => $class->type ? $class->type->name : null,
                'class_time' => $class->classTime ? $class->classTime->name : null,
                'class_location' => $class->classLocation ? $class->classLocation->city : null,
                'instructor' => $class->instructor ? $class->instructor->name : null,
                'students_count' => $class->students->count(),
                'schedules' => $class->schedules,
            ];
        }
        return $this->successResponse($data, 'Student classes retrieved successfully');
    }
    public function enrollClass(Request $request){
        try{
            $enroll = Enroll::create([
                'class_id' => $request->class_id,
                'student_id' => $request->student_id,
                'date' => now(), 
            ]);

            // Get authenticated user from bearer token
            $user = $request->user();
            
            // Get class details for notification
            $class = Classes::find($request->class_id);
            
            // Send FCM notification and save to database
            $fcmService = app(FCMService::class);
            $fcmService->sendNotification(
                $user->id,
                $user->fcm_token,
                'Class Enrollment Successful',
                'You have successfully enrolled in the class: ' . ($class ? $class->name : 'Unknown Class'),
                [], // FCM data
                'navigate', // Action type
                [ // Action data
                    'route' => 'class_detail',
                    'class_id' => $request->class_id,
                    'class_name' => $class ? $class->name : 'Unknown Class',
                    'enrollment_id' => $enroll->id
                ]
            );

            return $this->successResponse($enroll, 'Successfully enrolled in class');
        }catch (\Exception $e) {
            return $this->errorResponse('Failed to enroll class: ' . $e->getMessage(), 500);
        }
    }
}
