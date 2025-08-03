<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Material;
use App\Models\File;
use App\Models\Schedule;
use App\Models\Instructor;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ActivitiesController extends Controller
{
    /**
     * Get all activities for a specific schedule
     */
    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'schedule_id' => 'required|exists:schedules,id',
            'activity_type' => 'sometimes|in:pre_class,post_class',
            'is_active' => 'sometimes|boolean'
        ]);

        $query = Material::with(['instructor', 'schedule', 'file'])
            ->where('schedule_id', $request->schedule_id);

        // Filter by activity type if provided
        if ($request->has('activity_type')) {
            $query->where('activity_type', $request->activity_type);
        }

        // Filter by active status if provided
        if ($request->has('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        $activities = $query->orderBy('created_at', 'desc')->get();

        return response()->json([
            'success' => true,
            'data' => $activities->map(function ($activity) {
                return [
                    'id' => $activity->id,
                    'title' => $activity->title,
                    'description' => $activity->description,
                    'instructions' => $activity->instructions,
                    'activity_type' => $activity->activity_type,
                    'is_mandatory' => $activity->is_mandatory,
                    'available_from' => $activity->available_from,
                    'due_date' => $activity->due_date,
                    'is_active' => $activity->is_active,
                    'created_at' => $activity->created_at,
                    'instructor' => [
                        'id' => $activity->instructor->id,
                        'name' => $activity->instructor->name,
                        'email' => $activity->instructor->email
                    ],
                    'schedule' => [
                        'id' => $activity->schedule->id,
                        'title' => $activity->schedule->title,
                        'date' => $activity->schedule->date,
                        'start_time' => $activity->schedule->start_time,
                        'end_time' => $activity->schedule->end_time
                    ],
                    'file' => $activity->file ? [
                        'id' => $activity->file->id,
                        'original_name' => $activity->file->original_name,
                        'file_size' => $activity->file->file_size,
                        'mime_type' => $activity->file->mime_type,
                        'file_extension' => $activity->file->file_extension,
                        'download_url' => route('api.activities.download', $activity->file->id)
                    ] : null
                ];
            })
        ]);
    }

    /**
     * Get a specific activity by ID
     */
    public function show($id): JsonResponse
    {
        $activity = Material::with(['instructor', 'schedule', 'file'])
            ->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $activity->id,
                'title' => $activity->title,
                'description' => $activity->description,
                'instructions' => $activity->instructions,
                'activity_type' => $activity->activity_type,
                'is_mandatory' => $activity->is_mandatory,
                'available_from' => $activity->available_from,
                'due_date' => $activity->due_date,
                'is_active' => $activity->is_active,
                'created_at' => $activity->created_at,
                'instructor' => [
                    'id' => $activity->instructor->id,
                    'name' => $activity->instructor->name,
                    'email' => $activity->instructor->email
                ],
                'schedule' => [
                    'id' => $activity->schedule->id,
                    'title' => $activity->schedule->title,
                    'date' => $activity->schedule->date,
                    'start_time' => $activity->schedule->start_time,
                    'end_time' => $activity->schedule->end_time
                ],
                'file' => $activity->file ? [
                    'id' => $activity->file->id,
                    'original_name' => $activity->file->original_name,
                    'file_size' => $activity->file->file_size,
                    'mime_type' => $activity->file->mime_type,
                    'file_extension' => $activity->file->file_extension,
                    'download_url' => route('api.activities.download', $activity->file->id)
                ] : null
            ]
        ]);
    }

    /**
     * Get activities by student (based on their enrollments)
     */
    public function studentActivities(Request $request): JsonResponse
    {
        $request->validate([
            'student_id' => 'required|exists:students,id',
            'activity_type' => 'sometimes|in:pre_class,post_class',
            'date_from' => 'sometimes|date',
            'date_to' => 'sometimes|date|after_or_equal:date_from'
        ]);

        // Get student's enrolled classes and their schedules with activities
        $query = Material::with(['instructor', 'schedule.class', 'file'])
            ->whereHas('schedule.class.students', function ($q) use ($request) {
                $q->where('student_id', $request->student_id);
            })
            ->where('is_active', true);

        // Filter by activity type if provided
        if ($request->has('activity_type')) {
            $query->where('activity_type', $request->activity_type);
        }

        // Filter by date range if provided
        if ($request->has('date_from')) {
            $query->whereHas('schedule', function ($q) use ($request) {
                $q->where('date', '>=', $request->date_from);
            });
        }

        if ($request->has('date_to')) {
            $query->whereHas('schedule', function ($q) use ($request) {
                $q->where('date', '<=', $request->date_to);
            });
        }

        $activities = $query->orderBy('created_at', 'desc')->get();

        return response()->json([
            'success' => true,
            'data' => $activities->map(function ($activity) {
                return [
                    'id' => $activity->id,
                    'title' => $activity->title,
                    'description' => $activity->description,
                    'instructions' => $activity->instructions,
                    'activity_type' => $activity->activity_type,
                    'is_mandatory' => $activity->is_mandatory,
                    'available_from' => $activity->available_from,
                    'due_date' => $activity->due_date,
                    'created_at' => $activity->created_at,
                    'instructor' => [
                        'id' => $activity->instructor->id,
                        'name' => $activity->instructor->name,
                        'email' => $activity->instructor->email
                    ],
                    'schedule' => [
                        'id' => $activity->schedule->id,
                        'title' => $activity->schedule->title,
                        'date' => $activity->schedule->date,
                        'start_time' => $activity->schedule->start_time,
                        'end_time' => $activity->schedule->end_time,
                        'class' => [
                            'id' => $activity->schedule->class->id,
                            'name' => $activity->schedule->class->name
                        ]
                    ],
                    'file' => $activity->file ? [
                        'id' => $activity->file->id,
                        'original_name' => $activity->file->original_name,
                        'file_size' => $activity->file->file_size,
                        'mime_type' => $activity->file->mime_type,
                        'file_extension' => $activity->file->file_extension,
                        'download_url' => route('api.activities.download', $activity->file->id)
                    ] : null
                ];
            })
        ]);
    }

    /**
     * Download activity file
     */
    public function download($fileId): BinaryFileResponse
    {
        $file = File::findOrFail($fileId);

        // Check if file exists in storage
        if (!Storage::exists($file->file_path)) {
            abort(404, 'File not found in storage');
        }

        return response()->download(
            Storage::path($file->file_path),
            $file->original_name,
            [
                'Content-Type' => $file->mime_type,
                'Content-Disposition' => 'attachment; filename="' . $file->original_name . '"'
            ]
        );
    }

    /**
     * Get activities statistics for a student
     */
    public function studentStats(Request $request): JsonResponse
    {
        $request->validate([
            'student_id' => 'required|exists:students,id',
            'date_from' => 'sometimes|date',
            'date_to' => 'sometimes|date|after_or_equal:date_from'
        ]);

        $query = Material::whereHas('schedule.class.students', function ($q) use ($request) {
            $q->where('student_id', $request->student_id);
        })
            ->where('is_active', true);

        // Apply date filters if provided
        if ($request->has('date_from')) {
            $query->whereHas('schedule', function ($q) use ($request) {
                $q->where('date', '>=', $request->date_from);
            });
        }

        if ($request->has('date_to')) {
            $query->whereHas('schedule', function ($q) use ($request) {
                $q->where('date', '<=', $request->date_to);
            });
        }

        $totalActivities = $query->count();
        $preClassActivities = (clone $query)->where('activity_type', 'pre_class')->count();
        $postClassActivities = (clone $query)->where('activity_type', 'post_class')->count();
        $mandatoryActivities = (clone $query)->where('is_mandatory', true)->count();

        return response()->json([
            'success' => true,
            'data' => [
                'total_activities' => $totalActivities,
                'pre_class_activities' => $preClassActivities,
                'post_class_activities' => $postClassActivities,
                'mandatory_activities' => $mandatoryActivities,
                'optional_activities' => $totalActivities - $mandatoryActivities
            ]
        ]);
    }

    /**
     * Create a new activity with optional file upload
     */
    public function store(Request $request): JsonResponse
    {
        // Determine if request has file upload
        $hasFile = $request->hasFile('file');
        
        // Base validation rules
        $rules = [
            'instructor_id' => 'required|exists:instructors,id',
            'schedule_id' => 'required|exists:schedules,id',
            'activity_type' => 'required|in:pre_class,post_class',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'instructions' => 'nullable|string',
            'is_mandatory' => 'boolean',
            'available_from' => 'nullable|date',
            'due_date' => 'nullable|date|after:available_from',
            'is_active' => 'boolean'
        ];

        // Add file validation if file is uploaded
        if ($hasFile) {
            $rules['file'] = 'required|file|max:10240'; // 10MB max
            $rules['file_category'] = 'sometimes|in:material,assignment,resource,other';
        } else {
            // If no file upload, allow existing file_id
            $rules['file_id'] = 'nullable|exists:files,id';
        }

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $fileId = null;

            // Handle file upload if present
            if ($hasFile) {
                $uploadedFile = $request->file('file');
                
                // Generate unique filename
                $fileName = time() . '_' . Str::random(10) . '.' . $uploadedFile->getClientOriginalExtension();
                
                // Store file
                $filePath = $uploadedFile->storeAs('files/activities', $fileName, 'public');
                
                // Create file record
                $file = File::create([
                    'original_name' => $uploadedFile->getClientOriginalName(),
                    'file_name' => $fileName,
                    'file_path' => $filePath,
                    'mime_type' => $uploadedFile->getMimeType(),
                    'file_size' => $uploadedFile->getSize(),
                    'file_extension' => $uploadedFile->getClientOriginalExtension(),
                    'file_category' => $request->get('file_category', 'material'),
                    'uploaded_by' => $request->instructor_id
                ]);
                
                $fileId = $file->id;
            } else {
                $fileId = $request->file_id;
            }

            // Create activity
            $activity = Material::create([
                'instructor_id' => $request->instructor_id,
                'schedule_id' => $request->schedule_id,
                'file_id' => $fileId,
                'activity_type' => $request->activity_type,
                'title' => $request->title,
                'description' => $request->description,
                'instructions' => $request->instructions,
                'is_mandatory' => $request->boolean('is_mandatory', false),
                'available_from' => $request->available_from,
                'due_date' => $request->due_date,
                'is_active' => $request->boolean('is_active', true)
            ]);

            $activity->load(['instructor', 'schedule', 'file']);

            return response()->json([
                'success' => true,
                'message' => 'Activity created successfully' . ($hasFile ? ' with file uploaded' : ''),
                'data' => [
                    'id' => $activity->id,
                    'title' => $activity->title,
                    'description' => $activity->description,
                    'instructions' => $activity->instructions,
                    'activity_type' => $activity->activity_type,
                    'is_mandatory' => $activity->is_mandatory,
                    'available_from' => $activity->available_from,
                    'due_date' => $activity->due_date,
                    'is_active' => $activity->is_active,
                    'created_at' => $activity->created_at,
                    'instructor' => [
                        'id' => $activity->instructor->id,
                        'name' => $activity->instructor->name,
                        'email' => $activity->instructor->email
                    ],
                    'schedule' => [
                        'id' => $activity->schedule->id,
                        'title' => $activity->schedule->title,
                        'date' => $activity->schedule->date,
                        'start_time' => $activity->schedule->start_time,
                        'end_time' => $activity->schedule->end_time
                    ],
                    'file' => $activity->file ? [
                        'id' => $activity->file->id,
                        'original_name' => $activity->file->original_name,
                        'file_size' => $activity->file->file_size,
                        'mime_type' => $activity->file->mime_type,
                        'file_extension' => $activity->file->file_extension,
                        'download_url' => route('api.activities.download', $activity->file->id)
                    ] : null
                ]
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create activity',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update an existing activity
     */
    public function update(Request $request, $id): JsonResponse
    {
        $activity = Material::findOrFail($id);

        $request->validate([
            'instructor_id' => 'sometimes|exists:instructors,id',
            'schedule_id' => 'sometimes|exists:schedules,id',
            'file_id' => 'nullable|exists:files,id',
            'activity_type' => 'sometimes|in:pre_class,post_class',
            'title' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'instructions' => 'nullable|string',
            'is_mandatory' => 'boolean',
            'available_from' => 'nullable|date',
            'due_date' => 'nullable|date|after:available_from',
            'is_active' => 'boolean'
        ]);

        $activity->update($request->only([
            'instructor_id',
            'schedule_id',
            'file_id',
            'activity_type',
            'title',
            'description',
            'instructions',
            'is_mandatory',
            'available_from',
            'due_date',
            'is_active'
        ]));

        $activity->load(['instructor', 'schedule', 'file']);

        return response()->json([
            'success' => true,
            'message' => 'Activity updated successfully',
            'data' => [
                'id' => $activity->id,
                'title' => $activity->title,
                'description' => $activity->description,
                'instructions' => $activity->instructions,
                'activity_type' => $activity->activity_type,
                'is_mandatory' => $activity->is_mandatory,
                'available_from' => $activity->available_from,
                'due_date' => $activity->due_date,
                'is_active' => $activity->is_active,
                'created_at' => $activity->created_at,
                'updated_at' => $activity->updated_at,
                'instructor' => [
                    'id' => $activity->instructor->id,
                    'name' => $activity->instructor->name,
                    'email' => $activity->instructor->email
                ],
                'schedule' => [
                    'id' => $activity->schedule->id,
                    'title' => $activity->schedule->title,
                    'date' => $activity->schedule->date,
                    'start_time' => $activity->schedule->start_time,
                    'end_time' => $activity->schedule->end_time
                ],
                'file' => $activity->file ? [
                    'id' => $activity->file->id,
                    'original_name' => $activity->file->original_name,
                    'file_size' => $activity->file->file_size,
                    'mime_type' => $activity->file->mime_type,
                    'file_extension' => $activity->file->file_extension,
                    'download_url' => route('api.activities.download', $activity->file->id)
                ] : null
            ]
        ]);
    }

    /**
     * Delete an activity (soft delete)
     */
    public function destroy($id): JsonResponse
    {
        $activity = Material::findOrFail($id);
        $activity->delete();

        return response()->json([
            'success' => true,
            'message' => 'Activity deleted successfully'
        ]);
    }
}
