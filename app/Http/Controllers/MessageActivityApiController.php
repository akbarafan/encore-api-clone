<?php

namespace App\Http\Controllers;

use App\Models\MessageActivity;
use App\Models\Classes;
use App\Models\Instructor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Carbon\Carbon;

class MessageActivityApiController extends Controller
{
    /**
     * Get all message activities with filters
     */
    public function index(Request $request)
    {
        return $this->handleTryCatch(function () use ($request) {
            $query = MessageActivity::with(['class.category', 'class.type', 'instructor']);

            // Filter by instructor_id
            if ($request->has('instructor_id') && $request->instructor_id != '') {
                $query->where('instructor_id', $request->instructor_id);
            }

            // Filter by class_id
            if ($request->has('class_id') && $request->class_id != '') {
                $query->where('class_id', $request->class_id);
            }

            // Filter by activity date
            if ($request->has('activity_date') && $request->activity_date != '') {
                $query->whereDate('activity_date', $request->activity_date);
            }

            // Filter by date range
            if ($request->has('date_from') && $request->date_from != '') {
                $query->whereDate('activity_date', '>=', $request->date_from);
            }

            if ($request->has('date_to') && $request->date_to != '') {
                $query->whereDate('activity_date', '<=', $request->date_to);
            }

            // Filter by active status
            if ($request->has('is_active') && $request->is_active != '') {
                $query->where('is_active', $request->boolean('is_active'));
            }

            // Filter by pinned status
            if ($request->has('is_pinned') && $request->is_pinned != '') {
                $query->where('is_pinned', $request->boolean('is_pinned'));
            }

            // Search by title or message
            if ($request->has('search') && $request->search != '') {
                $search = $request->search;
                $query->where(function($q) use ($search) {
                    $q->where('title', 'like', '%' . $search . '%')
                      ->orWhere('message', 'like', '%' . $search . '%');
                });
            }

            // Sorting
            if ($request->has('sort_by_pinned') && $request->sort_by_pinned == 'true') {
                $query->orderBy('is_pinned', 'desc')
                      ->orderBy('activity_date', 'desc')
                      ->orderBy('created_at', 'desc');
            } else {
                $query->orderBy('activity_date', 'desc')
                      ->orderBy('created_at', 'desc');
            }

            // Pagination
            $perPage = $request->get('per_page', 15);
            $activities = $query->paginate($perPage);

            // Transform data
            $data = [];
            foreach ($activities as $activity) {
                $data[] = [
                    'id' => $activity->id,
                    'title' => $activity->title,
                    'message' => $activity->message,
                    'activity_date' => $activity->activity_date->format('Y-m-d'),
                    'is_pinned' => $activity->is_pinned,
                    'is_active' => $activity->is_active,
                    'attachments_count' => $activity->attachments ? count($activity->attachments) : 0,
                    'attachments' => $activity->attachments,
                    'class' => [
                        'id' => $activity->class->id,
                        'name' => $activity->class->name,
                        'category' => $activity->class->category ? $activity->class->category->name : null,
                        'type' => $activity->class->type ? $activity->class->type->name : null,
                    ],
                    'instructor' => [
                        'id' => $activity->instructor->id,
                        'name' => $activity->instructor->name,
                    ],
                    'created_at' => $activity->created_at->format('Y-m-d H:i:s'),
                    'updated_at' => $activity->updated_at->format('Y-m-d H:i:s'),
                ];
            }

            return $this->successResponse([
                'activities' => $data,
                'pagination' => [
                    'current_page' => $activities->currentPage(),
                    'per_page' => $activities->perPage(),
                    'total' => $activities->total(),
                    'last_page' => $activities->lastPage(),
                    'has_more_pages' => $activities->hasMorePages(),
                ]
            ], 'Message activities retrieved successfully');
        });
    }

    /**
     * Store new message activity
     */
    public function store(Request $request)
    {
        return $this->handleTryCatch(function () use ($request) {
            $validated = $request->validate([
                'instructor_id' => 'required|exists:instructors,id',
                'class_id' => 'required|exists:classes,id',
                'title' => 'nullable|string|max:255',
                'message' => 'required|string',
                'activity_date' => 'required|date',
                'attachments' => 'nullable|array',
                'attachments.*' => 'file|max:10240', // 10MB max per file
                'is_pinned' => 'nullable|boolean',
                'is_active' => 'nullable|boolean',
            ]);

            // Handle file attachments
            $attachments = [];
            if ($request->hasFile('attachments')) {
                foreach ($request->file('attachments') as $file) {
                    $filename = time() . '_' . Str::random(10) . '.' . $file->getClientOriginalExtension();
                    $path = $file->storeAs('message-activities', $filename, 'public');
                    
                    $attachments[] = [
                        'original_name' => $file->getClientOriginalName(),
                        'filename' => $filename,
                        'path' => $path,
                        'size' => $file->getSize(),
                        'mime_type' => $file->getMimeType()
                    ];
                }
            }

            // Create message activity
            $activity = MessageActivity::create([
                'instructor_id' => $validated['instructor_id'],
                'class_id' => $validated['class_id'],
                'title' => $validated['title'],
                'message' => $validated['message'],
                'activity_date' => $validated['activity_date'],
                'attachments' => !empty($attachments) ? $attachments : null,
                'is_pinned' => $request->boolean('is_pinned'),
                'is_active' => $request->boolean('is_active', true)
            ]);

            // Load relationships for response
            $activity->load(['class.category', 'class.type', 'instructor']);

            return $this->successResponse($activity, 'Message activity created successfully', 201);
        });
    }

    /**
     * Show specific message activity
     */
    public function show($id)
    {
        return $this->handleTryCatch(function () use ($id) {
            $activity = MessageActivity::with(['class.category', 'class.type', 'instructor'])
                ->findOrFail($id);

            $data = [
                'id' => $activity->id,
                'title' => $activity->title,
                'message' => $activity->message,
                'activity_date' => $activity->activity_date->format('Y-m-d'),
                'is_pinned' => $activity->is_pinned,
                'is_active' => $activity->is_active,
                'attachments_count' => $activity->attachments ? count($activity->attachments) : 0,
                'attachments' => $activity->attachments,
                'class' => [
                    'id' => $activity->class->id,
                    'name' => $activity->class->name,
                    'description' => $activity->class->description,
                    'category' => $activity->class->category ? $activity->class->category->name : null,
                    'type' => $activity->class->type ? $activity->class->type->name : null,
                ],
                'instructor' => [
                    'id' => $activity->instructor->id,
                    'name' => $activity->instructor->name,
                ],
                'created_at' => $activity->created_at->format('Y-m-d H:i:s'),
                'updated_at' => $activity->updated_at->format('Y-m-d H:i:s'),
            ];

            return $this->successResponse($data, 'Message activity retrieved successfully');
        });
    }

    /**
     * Update message activity
     */
    public function update(Request $request, $id)
    {
        return $this->handleTryCatch(function () use ($request, $id) {
            $validated = $request->validate([
                'class_id' => 'exists:classes,id',
                'title' => 'nullable|string|max:255',
                'message' => 'string',
                'activity_date' => 'date',
                'attachments' => 'nullable|array',
                'attachments.*' => 'file|max:10240',
                'is_pinned' => 'nullable|boolean',
                'is_active' => 'nullable|boolean',
            ]);

            $activity = MessageActivity::findOrFail($id);

            // Handle new file attachments
            $existingAttachments = $activity->attachments ?? [];
            $newAttachments = [];

            if ($request->hasFile('attachments')) {
                foreach ($request->file('attachments') as $file) {
                    $filename = time() . '_' . Str::random(10) . '.' . $file->getClientOriginalExtension();
                    $path = $file->storeAs('message-activities', $filename, 'public');
                    
                    $newAttachments[] = [
                        'original_name' => $file->getClientOriginalName(),
                        'filename' => $filename,
                        'path' => $path,
                        'size' => $file->getSize(),
                        'mime_type' => $file->getMimeType()
                    ];
                }
            }

            // Merge existing and new attachments
            $allAttachments = array_merge($existingAttachments, $newAttachments);

            // Update activity
            $updateData = array_filter($validated, function($value) {
                return $value !== null;
            });

            if (!empty($allAttachments)) {
                $updateData['attachments'] = $allAttachments;
            }

            if (isset($validated['is_pinned'])) {
                $updateData['is_pinned'] = $request->boolean('is_pinned');
            }

            if (isset($validated['is_active'])) {
                $updateData['is_active'] = $request->boolean('is_active');
            }

            $activity->update($updateData);
            $activity->load(['class.category', 'class.type', 'instructor']);

            return $this->successResponse($activity, 'Message activity updated successfully');
        });
    }

    /**
     * Delete message activity
     */
    public function destroy($id)
    {
        return $this->handleTryCatch(function () use ($id) {
            $activity = MessageActivity::findOrFail($id);

            // Delete associated files
            if ($activity->attachments) {
                foreach ($activity->attachments as $attachment) {
                    if (isset($attachment['path'])) {
                        Storage::disk('public')->delete($attachment['path']);
                    }
                }
            }

            $activity->delete();

            return $this->successResponse(null, 'Message activity deleted successfully');
        });
    }

    /**
     * Toggle pin status
     */
    public function togglePin($id)
    {
        return $this->handleTryCatch(function () use ($id) {
            $activity = MessageActivity::findOrFail($id);
            $activity->update(['is_pinned' => !$activity->is_pinned]);

            return $this->successResponse([
                'id' => $activity->id,
                'is_pinned' => $activity->is_pinned
            ], $activity->is_pinned ? 'Activity pinned successfully' : 'Activity unpinned successfully');
        });
    }

    /**
     * Toggle active status
     */
    public function toggleActive($id)
    {
        return $this->handleTryCatch(function () use ($id) {
            $activity = MessageActivity::findOrFail($id);
            $activity->update(['is_active' => !$activity->is_active]);

            return $this->successResponse([
                'id' => $activity->id,
                'is_active' => $activity->is_active
            ], $activity->is_active ? 'Activity activated successfully' : 'Activity deactivated successfully');
        });
    }

    /**
     * Get activities by instructor
     */
    public function getByInstructor($instructorId)
    {
        return $this->handleTryCatch(function () use ($instructorId) {
            $activities = MessageActivity::where('instructor_id', $instructorId)
                ->with(['class.category', 'class.type', 'instructor'])
                ->orderBy('activity_date', 'desc')
                ->orderBy('created_at', 'desc')
                ->get();

            return $this->successResponse($activities, 'Instructor activities retrieved successfully');
        });
    }

    /**
     * Get activities by class
     */
    public function getByClass($classId)
    {
        return $this->handleTryCatch(function () use ($classId) {
            $activities = MessageActivity::where('class_id', $classId)
                ->where('is_active', true)
                ->with(['class.category', 'class.type', 'instructor'])
                ->orderBy('is_pinned', 'desc')
                ->orderBy('activity_date', 'desc')
                ->orderBy('created_at', 'desc')
                ->get();

            return $this->successResponse($activities, 'Class activities retrieved successfully');
        });
    }

    /**
     * Get today's activities
     */
    public function getTodayActivities()
    {
        return $this->handleTryCatch(function () {
            $activities = MessageActivity::whereDate('activity_date', today())
                ->where('is_active', true)
                ->with(['class.category', 'class.type', 'instructor'])
                ->orderBy('is_pinned', 'desc')
                ->orderBy('created_at', 'desc')
                ->get();

            return $this->successResponse($activities, 'Today\'s activities retrieved successfully');
        });
    }

    /**
     * Get pinned activities
     */
    public function getPinned()
    {
        return $this->handleTryCatch(function () {
            $activities = MessageActivity::where('is_pinned', true)
                ->where('is_active', true)
                ->with(['class.category', 'class.type', 'instructor'])
                ->orderBy('activity_date', 'desc')
                ->orderBy('created_at', 'desc')
                ->get();

            return $this->successResponse($activities, 'Pinned activities retrieved successfully');
        });
    }

    /**
     * Download attachment file
     */
    public function downloadAttachment($id, $attachmentIndex)
    {
        return $this->handleTryCatch(function () use ($id, $attachmentIndex) {
            $activity = MessageActivity::findOrFail($id);

            if (!$activity->attachments || !isset($activity->attachments[$attachmentIndex])) {
                return $this->errorResponse('Attachment not found', null, 404);
            }

            $attachment = $activity->attachments[$attachmentIndex];
            
            if (!Storage::disk('public')->exists($attachment['path'])) {
                return $this->errorResponse('File not found', null, 404);
            }

            return Storage::disk('public')->download(
                $attachment['path'], 
                $attachment['original_name']
            );
        });
    }

    /**
     * Get activities statistics
     */
    public function getStatistics()
    {
        return $this->handleTryCatch(function () {
            $totalActivities = MessageActivity::count();
            $activeActivities = MessageActivity::where('is_active', true)->count();
            $pinnedActivities = MessageActivity::where('is_pinned', true)->count();
            $todayActivities = MessageActivity::whereDate('activity_date', today())->count();
            $thisWeekActivities = MessageActivity::whereBetween('activity_date', [
                Carbon::now()->startOfWeek(),
                Carbon::now()->endOfWeek()
            ])->count();
            $thisMonthActivities = MessageActivity::whereMonth('activity_date', Carbon::now()->month)
                ->whereYear('activity_date', Carbon::now()->year)
                ->count();

            $data = [
                'total_activities' => $totalActivities,
                'active_activities' => $activeActivities,
                'pinned_activities' => $pinnedActivities,
                'today_activities' => $todayActivities,
                'this_week_activities' => $thisWeekActivities,
                'this_month_activities' => $thisMonthActivities,
            ];

            return $this->successResponse($data, 'Activity statistics retrieved successfully');
        });
    }
}
