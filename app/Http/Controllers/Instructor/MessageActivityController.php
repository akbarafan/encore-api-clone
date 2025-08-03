<?php

namespace App\Http\Controllers\Instructor;

use App\Http\Controllers\Controller;
use App\Models\MessageActivity;
use App\Models\Classes;
use App\Helpers\InstructorHelper;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class MessageActivityController extends Controller
{
    private function getCurrentInstructor()
    {
        return InstructorHelper::getCurrentInstructorRecord();
    }

    /**
     * Display a listing of message activities
     */
    public function index(Request $request)
    {
        $instructor = $this->getCurrentInstructor();

        // Get instructor's classes
        $instructorClasses = Classes::where('instructor_id', $instructor->id)
            ->with(['category', 'type'])
            ->get();

        // If instructor has no classes, we still show the page but with empty state
        if ($instructorClasses->isEmpty()) {
            Log::info('Instructor has no classes assigned', ['instructor_id' => $instructor->id]);
        }

        // Build query for message activities
        $query = MessageActivity::with(['class.category', 'class.type'])
            ->where('instructor_id', $instructor->id);

        // Apply filters
        if ($request->filled('class_id')) {
            $query->where('class_id', $request->class_id);
        }

        if ($request->filled('date_from')) {
            $query->whereDate('activity_date', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('activity_date', '<=', $request->date_to);
        }

        if ($request->filled('is_pinned')) {
            $query->where('is_pinned', $request->is_pinned);
        }

        if ($request->filled('is_active')) {
            $query->where('is_active', $request->is_active);
        }

        // Get activities with pagination
        $activities = $query->orderBy('activity_date', 'desc')
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        // Get statistics
        $totalActivities = MessageActivity::where('instructor_id', $instructor->id)->count();
        $activeActivities = MessageActivity::where('instructor_id', $instructor->id)->active()->count();
        $pinnedActivities = MessageActivity::where('instructor_id', $instructor->id)->pinned()->count();
        $todayActivities = MessageActivity::where('instructor_id', $instructor->id)
            ->byDate(today())
            ->count();

        // Debug: Log what we're passing to view
        \Log::info('MessageActivity Controller Data:', [
            'activities_count' => $activities->count(),
            'classes_count' => $instructorClasses->count(),
            'total_activities' => $totalActivities,
            'instructor_id' => $instructor->id
        ]);

        return view('instructor.message-activities.index', compact(
            'activities',
            'instructorClasses',
            'totalActivities',
            'activeActivities',
            'pinnedActivities',
            'todayActivities'
        ));
    }

    /**
     * Store a newly created message activity
     */
    public function store(Request $request)
    {
        Log::info('MessageActivity store called', $request->all());

        $instructor = $this->getCurrentInstructor();

        if (!$instructor) {
            Log::error('No instructor found');
            return response()->json(['success' => false, 'message' => 'Instructor not found'], 403);
        }

            $request->validate([
                'class_id' => 'required|exists:classes,id',
                'title' => 'nullable|string|max:255',
                'message' => 'required|string',
                'activity_date' => 'required|date',
                'attachments' => 'nullable|array',
                'attachments.*' => 'file|max:10240', // 10MB max per file
                'is_pinned' => 'nullable|boolean',
                'is_active' => 'nullable|boolean'
            ]);

            // Verify class belongs to instructor
            $class = Classes::where('id', $request->class_id)
                ->where('instructor_id', $instructor->id)
                ->first();

            if (!$class) {
                return response()->json(['success' => false, 'message' => 'Class not found or access denied'], 403);
            }

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
                'instructor_id' => $instructor->id,
                'class_id' => $request->class_id,
                'title' => $request->title,
                'message' => $request->message,
                'activity_date' => $request->activity_date,
                'attachments' => !empty($attachments) ? $attachments : null,
                'is_pinned' => $request->boolean('is_pinned'),
                'is_active' => $request->boolean('is_active', true)
            ]);

            return redirect()->route('instructor.message-activities.index')->with('success', 'Message activity created successfully.');


    }

    /**
     * Display the specified message activity
     */
    public function show($id)
    {
        $instructor = $this->getCurrentInstructor();

        $activity = MessageActivity::with(['class.category', 'class.type'])
            ->where('instructor_id', $instructor->id)
            ->findOrFail($id);

        return response()->json([
            'success' => true,
            'activity' => $activity
        ]);
    }

    /**
     * Update the specified message activity
     */
    public function update(Request $request, $id)
    {
        $instructor = $this->getCurrentInstructor();

        $activity = MessageActivity::where('instructor_id', $instructor->id)
            ->findOrFail($id);

        $request->validate([
            'class_id' => 'required|exists:classes,id',
            'title' => 'nullable|string|max:255',
            'message' => 'required|string',
            'activity_date' => 'required|date',
            'attachments' => 'nullable|array',
            'attachments.*' => 'file|max:10240',
            'is_pinned' => 'nullable|boolean',
            'is_active' => 'nullable|boolean'
        ]);

        // Verify class belongs to instructor
        $class = Classes::where('id', $request->class_id)
            ->where('instructor_id', $instructor->id)
            ->firstOrFail();

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
        $activity->update([
            'class_id' => $request->class_id,
            'title' => $request->title,
            'message' => $request->message,
            'activity_date' => $request->activity_date,
            'attachments' => !empty($allAttachments) ? $allAttachments : null,
            'is_pinned' => $request->boolean('is_pinned'),
            'is_active' => $request->boolean('is_active', true)
        ]);

        return redirect()->route('instructor.message-activities.index')->with('success', 'Message activity updated successfully.');

    }

    /**
     * Remove the specified message activity
     */
    public function destroy($id)
    {
        $instructor = $this->getCurrentInstructor();

        $activity = MessageActivity::where('instructor_id', $instructor->id)
            ->findOrFail($id);

        // Delete associated files
        if ($activity->attachments) {
            foreach ($activity->attachments as $attachment) {
                if (isset($attachment['path'])) {
                    Storage::disk('public')->delete($attachment['path']);
                }
            }
        }

        $activity->delete();

        return redirect()->route('instructor.message-activities.index')->with('success', 'Message activity deleted successfully.');
    }

    /**
     * Toggle pin status
     */
    public function togglePin($id)
    {
        $instructor = $this->getCurrentInstructor();

        $activity = MessageActivity::where('instructor_id', $instructor->id)
            ->findOrFail($id);

        $activity->update([
            'is_pinned' => !$activity->is_pinned
        ]);

        return response()->json([
            'success' => true,
            'message' => $activity->is_pinned ? 'Activity pinned successfully' : 'Activity unpinned successfully',
            'is_pinned' => $activity->is_pinned
        ]);
    }

    /**
     * Toggle active status
     */
    public function toggleActive($id)
    {
        $instructor = $this->getCurrentInstructor();

        $activity = MessageActivity::where('instructor_id', $instructor->id)
            ->findOrFail($id);

        $activity->update([
            'is_active' => !$activity->is_active
        ]);

        return response()->json([
            'success' => true,
            'message' => $activity->is_active ? 'Activity activated successfully' : 'Activity deactivated successfully',
            'is_active' => $activity->is_active
        ]);
    }

    /**
     * Get activities for a specific class (for API)
     */
    public function getClassActivities($classId)
    {
        $instructor = $this->getCurrentInstructor();

        // Verify class belongs to instructor
        $class = Classes::where('id', $classId)
            ->where('instructor_id', $instructor->id)
            ->firstOrFail();

        $activities = MessageActivity::where('class_id', $classId)
            ->where('instructor_id', $instructor->id)
            ->active()
            ->orderBy('activity_date', 'desc')
            ->orderBy('is_pinned', 'desc')
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'class' => $class,
            'activities' => $activities
        ]);
    }

    /**
     * Download attachment file
     */
    public function downloadAttachment($id, $attachmentIndex)
    {
        $instructor = $this->getCurrentInstructor();

        $activity = MessageActivity::where('instructor_id', $instructor->id)
            ->findOrFail($id);

        if (!$activity->attachments || !isset($activity->attachments[$attachmentIndex])) {
            abort(404, 'Attachment not found');
        }

        $attachment = $activity->attachments[$attachmentIndex];

        if (!Storage::disk('public')->exists($attachment['path'])) {
            abort(404, 'File not found');
        }

        return Storage::disk('public')->download(
            $attachment['path'],
            $attachment['original_name']
        );
    }
}
