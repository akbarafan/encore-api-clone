<?php

namespace App\Http\Controllers\Instructor;

use App\Http\Controllers\Controller;
use App\Models\Material;
use App\Models\Schedule;
use App\Models\File;
use App\Helpers\InstructorHelper;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class MaterialController extends Controller
{
    private function getCurrentInstructor()
    {
        return InstructorHelper::getCurrentInstructorRecord();
    }

    /**
     * Get materials for a specific schedule
     */
    public function getScheduleMaterials($scheduleId)
    {
        $instructor = $this->getCurrentInstructor();

        $schedule = Schedule::with(['class.category'])
            ->whereHas('class', function($q) use ($instructor) {
                $q->where('instructor_id', $instructor->id);
            })
            ->findOrFail($scheduleId);

        $materials = Material::with(['file'])
            ->where('schedule_id', $scheduleId)
            ->where('instructor_id', $instructor->id)
            ->orderBy('material_type')
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'schedule' => $schedule,
            'materials' => $materials
        ]);
    }

    /**
     * Store a new activity
     */
    public function store(Request $request)
    {
        $instructor = $this->getCurrentInstructor();

        $request->validate([
            'schedule_id' => 'required|exists:schedules,id',
            'material_type' => 'required|in:pre_class,post_class',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'instructions' => 'nullable|string',
            'file' => 'required|file|max:10240|mimes:pdf,doc,docx,ppt,pptx,xlsx,xls,jpg,jpeg,png,zip',
            'is_mandatory' => 'boolean',
            'available_from' => 'nullable|date',
            'due_date' => 'nullable|date|after:available_from',
        ]);

        Log::info('Store activity request', [
            'request_data' => $request->all(),
            'has_file' => $request->hasFile('file'),
            'instructor_id' => $instructor->id
        ]);

        // Verify schedule belongs to instructor
        $schedule = Schedule::whereHas('class', function($q) use ($instructor) {
            $q->where('instructor_id', $instructor->id);
        })->findOrFail($request->schedule_id);

        try {
            // Upload file
            $uploadedFile = $request->file('file');
            $fileName = time() . '_' . Str::random(10) . '.' . $uploadedFile->getClientOriginalExtension();
            $filePath = $uploadedFile->storeAs('activities', $fileName, 'public');

            // Create file record
            $file = File::create([
                'original_name' => $uploadedFile->getClientOriginalName(),
                'file_name' => $fileName,
                'file_path' => $filePath,
                'mime_type' => $uploadedFile->getMimeType(),
                'file_size' => $uploadedFile->getSize(),
                'file_extension' => $uploadedFile->getClientOriginalExtension(),
                'file_category' => $request->material_type === 'pre_class' ? 'material' : 'assignment',
                'uploaded_by' => $instructor->id,
            ]);

            // Create material
            $activity = Material::create([
                'instructor_id' => $instructor->id,
                'schedule_id' => $request->schedule_id,
                'file_id' => $file->id,
                'material_type' => $request->material_type,
                'title' => $request->title,
                'description' => $request->description,
                'instructions' => $request->instructions,
                'is_mandatory' => $request->boolean('is_mandatory'),
                'available_from' => $request->available_from,
                'due_date' => $request->due_date,
                'is_active' => true,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Activity created successfully',
                'activity' => $activity->load('file')
            ]);

        } catch (\Exception $e) {
            // Clean up uploaded file if activity creation fails
            if (isset($filePath) && Storage::disk('public')->exists($filePath)) {
                Storage::disk('public')->delete($filePath);
            }

            return response()->json([
                'success' => false,
                'message' => 'Failed to create activity: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Show specific activity
     */
    public function show($id)
    {
        $instructor = $this->getCurrentInstructor();

        $activity = Material::with(['file', 'schedule.class'])
            ->where('instructor_id', $instructor->id)
            ->findOrFail($id);

        return response()->json($activity);
    }

    /**
     * Update an activity
     */
    public function update(Request $request, $id)
    {
        $instructor = $this->getCurrentInstructor();

        $activity = Material::where('instructor_id', $instructor->id)
            ->findOrFail($id);

        $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'instructions' => 'nullable|string',
            'file' => 'nullable|file|max:10240|mimes:pdf,doc,docx,ppt,pptx,xlsx,xls,jpg,jpeg,png,zip',
            'is_mandatory' => 'boolean',
            'available_from' => 'nullable|date',
            'due_date' => 'nullable|date|after:available_from',
        ]);

        Log::info('Update activity request', [
            'activity_id' => $id,
            'request_data' => $request->all(),
            'has_file' => $request->hasFile('file')
        ]);

        try {
            $updateData = [
                'title' => $request->title,
                'description' => $request->description,
                'instructions' => $request->instructions,
                'is_mandatory' => $request->boolean('is_mandatory'),
                'available_from' => $request->available_from,
                'due_date' => $request->due_date,
            ];

            // Handle file update if new file is uploaded
            if ($request->hasFile('file')) {
                $uploadedFile = $request->file('file');
                $fileName = time() . '_' . Str::random(10) . '.' . $uploadedFile->getClientOriginalExtension();
                $filePath = $uploadedFile->storeAs('activities', $fileName, 'public');

                // Delete old file
                $oldFile = $activity->file;
                if ($oldFile && Storage::disk('public')->exists($oldFile->file_path)) {
                    Storage::disk('public')->delete($oldFile->file_path);
                }

                // Update file record
                $oldFile->update([
                    'original_name' => $uploadedFile->getClientOriginalName(),
                    'file_name' => $fileName,
                    'file_path' => $filePath,
                    'mime_type' => $uploadedFile->getMimeType(),
                    'file_size' => $uploadedFile->getSize(),
                    'file_extension' => $uploadedFile->getClientOriginalExtension(),
                ]);
            }

            $activity->update($updateData);

            return response()->json([
                'success' => true,
                'message' => 'Activity updated successfully',
                'activity' => $activity->load('file')
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update activity: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete an activity
     */
    public function destroy($id)
    {
        $instructor = $this->getCurrentInstructor();

        $activity = Material::where('instructor_id', $instructor->id)
            ->findOrFail($id);

        try {
            // Delete associated file
            $file = $activity->file;
            if ($file) {
                if (Storage::disk('public')->exists($file->file_path)) {
                    Storage::disk('public')->delete($file->file_path);
                }
                $file->delete();
            }

            $activity->delete();

            return response()->json([
                'success' => true,
                'message' => 'Activity deleted successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete activity: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Download file
     */
    public function downloadFile($fileId)
    {
        $instructor = $this->getCurrentInstructor();

        $file = File::whereHas('materials', function($q) use ($instructor) {
            $q->where('instructor_id', $instructor->id);
        })->findOrFail($fileId);

        $filePath = storage_path('app/public/' . $file->file_path);

        if (!file_exists($filePath)) {
            abort(404, 'File not found');
        }

        return response()->download($filePath, $file->original_name);
    }
}
