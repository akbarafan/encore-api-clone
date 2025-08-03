<?php

namespace App\Http\Controllers;

use App\Models\File;
use App\Models\Schedule;
use App\Models\ScheduleFile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class ScheduleFileController extends Controller
{
    /**
     * Get files for a specific schedule
     */
    public function index($scheduleId)
    {
        try {
            $schedule = Schedule::with(['files' => function($query) {
                $query->with('uploader');
            }])->findOrFail($scheduleId);

            return response()->json([
                'success' => true,
                'message' => 'Schedule files retrieved successfully',
                'data' => [
                    'schedule' => $schedule,
                    'files' => $schedule->files
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve schedule files',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Attach file to schedule
     */
    public function store(Request $request, $scheduleId)
    {
        $validator = Validator::make($request->all(), [
            'file_id' => 'required|exists:files,id',
            'title' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'order' => 'nullable|integer|min:0',
            'is_required' => 'nullable|boolean',
            'available_from' => 'nullable|date',
            'available_until' => 'nullable|date|after:available_from'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $schedule = Schedule::findOrFail($scheduleId);
            $file = File::findOrFail($request->file_id);

            // Check if file is already attached to this schedule
            if ($schedule->files()->where('file_id', $request->file_id)->exists()) {
                return response()->json([
                    'success' => false,
                    'message' => 'File is already attached to this schedule'
                ], 409);
            }

            // Get next order if not provided
            $order = $request->order ?? ($schedule->files()->max('schedule_files.order') + 1);

            // Attach file to schedule
            $schedule->files()->attach($request->file_id, [
                'title' => $request->title,
                'description' => $request->description,
                'order' => $order,
                'is_required' => $request->is_required ?? false,
                'available_from' => $request->available_from,
                'available_until' => $request->available_until,
                'created_at' => now(),
                'updated_at' => now()
            ]);

            // Load the attached file with pivot data
            $scheduleFile = $schedule->files()->where('file_id', $request->file_id)->first();

            return response()->json([
                'success' => true,
                'message' => 'File attached to schedule successfully',
                'data' => $scheduleFile
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to attach file to schedule',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update schedule file relationship
     */
    public function update(Request $request, $scheduleId, $fileId)
    {
        $validator = Validator::make($request->all(), [
            'title' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'order' => 'nullable|integer|min:0',
            'is_required' => 'nullable|boolean',
            'available_from' => 'nullable|date',
            'available_until' => 'nullable|date|after:available_from'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $schedule = Schedule::findOrFail($scheduleId);

            // Check if file is attached to this schedule
            if (!$schedule->files()->where('file_id', $fileId)->exists()) {
                return response()->json([
                    'success' => false,
                    'message' => 'File is not attached to this schedule'
                ], 404);
            }

            // Update pivot data
            $updateData = array_filter([
                'title' => $request->title,
                'description' => $request->description,
                'order' => $request->order,
                'is_required' => $request->is_required,
                'available_from' => $request->available_from,
                'available_until' => $request->available_until,
                'updated_at' => now()
            ], function($value) {
                return $value !== null;
            });

            $schedule->files()->updateExistingPivot($fileId, $updateData);

            // Load updated file with pivot data
            $scheduleFile = $schedule->files()->where('file_id', $fileId)->first();

            return response()->json([
                'success' => true,
                'message' => 'Schedule file updated successfully',
                'data' => $scheduleFile
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update schedule file',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Detach file from schedule
     */
    public function destroy($scheduleId, $fileId)
    {
        try {
            $schedule = Schedule::findOrFail($scheduleId);

            // Check if file is attached to this schedule
            if (!$schedule->files()->where('file_id', $fileId)->exists()) {
                return response()->json([
                    'success' => false,
                    'message' => 'File is not attached to this schedule'
                ], 404);
            }

            // Detach file from schedule
            $schedule->files()->detach($fileId);

            return response()->json([
                'success' => true,
                'message' => 'File detached from schedule successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to detach file from schedule',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Reorder files in schedule
     */
    public function reorder(Request $request, $scheduleId)
    {
        $validator = Validator::make($request->all(), [
            'files' => 'required|array',
            'files.*.file_id' => 'required|exists:files,id',
            'files.*.order' => 'required|integer|min:0'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $schedule = Schedule::findOrFail($scheduleId);

            DB::transaction(function() use ($schedule, $request) {
                foreach ($request->files as $fileData) {
                    $schedule->files()->updateExistingPivot($fileData['file_id'], [
                        'order' => $fileData['order'],
                        'updated_at' => now()
                    ]);
                }
            });

            // Load reordered files
            $files = $schedule->files()->orderBy('schedule_files.order')->get();

            return response()->json([
                'success' => true,
                'message' => 'Files reordered successfully',
                'data' => $files
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to reorder files',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get available files for students
     */
    public function availableFiles($scheduleId)
    {
        try {
            $schedule = Schedule::findOrFail($scheduleId);

            $availableFiles = $schedule->files()
                ->wherePivot('available_from', '<=', now())
                ->where(function($query) {
                    $query->whereNull('schedule_files.available_until')
                          ->orWherePivot('available_until', '>=', now());
                })
                ->with('uploader')
                ->orderBy('schedule_files.order')
                ->get();

            return response()->json([
                'success' => true,
                'message' => 'Available files retrieved successfully',
                'data' => [
                    'schedule' => $schedule,
                    'files' => $availableFiles
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve available files',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Bulk attach files to schedule
     */
    public function bulkAttach(Request $request, $scheduleId)
    {
        $validator = Validator::make($request->all(), [
            'files' => 'required|array',
            'files.*.file_id' => 'required|exists:files,id',
            'files.*.title' => 'nullable|string|max:255',
            'files.*.description' => 'nullable|string',
            'files.*.is_required' => 'nullable|boolean',
            'files.*.available_from' => 'nullable|date',
            'files.*.available_until' => 'nullable|date|after:files.*.available_from'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $schedule = Schedule::findOrFail($scheduleId);
            $attachedFiles = [];
            $errors = [];

            DB::transaction(function() use ($schedule, $request, &$attachedFiles, &$errors) {
                $currentMaxOrder = $schedule->files()->max('schedule_files.order') ?? 0;

                foreach ($request->files as $index => $fileData) {
                    // Check if file is already attached
                    if ($schedule->files()->where('file_id', $fileData['file_id'])->exists()) {
                        $errors[] = "File ID {$fileData['file_id']} is already attached to this schedule";
                        continue;
                    }

                    $currentMaxOrder++;

                    $schedule->files()->attach($fileData['file_id'], [
                        'title' => $fileData['title'] ?? null,
                        'description' => $fileData['description'] ?? null,
                        'order' => $currentMaxOrder,
                        'is_required' => $fileData['is_required'] ?? false,
                        'available_from' => $fileData['available_from'] ?? null,
                        'available_until' => $fileData['available_until'] ?? null,
                        'created_at' => now(),
                        'updated_at' => now()
                    ]);

                    $attachedFiles[] = $fileData['file_id'];
                }
            });

            // Load attached files
            $files = $schedule->files()->whereIn('file_id', $attachedFiles)->get();

            return response()->json([
                'success' => true,
                'message' => 'Files attached successfully',
                'data' => [
                    'attached_files' => $files,
                    'errors' => $errors
                ]
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to bulk attach files',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
