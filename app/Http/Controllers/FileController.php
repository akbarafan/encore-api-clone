<?php

namespace App\Http\Controllers;

use App\Models\File;
use App\Models\FileDownload;
use App\Models\Schedule;
use App\Models\ScheduleFile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class FileController extends Controller
{
    /**
     * Display a listing of files
     */
    public function index(Request $request)
    {
        try {
            $query = File::with(['uploader', 'schedules']);

            // Filter by category
            if ($request->has('category')) {
                $query->byCategory($request->category);
            }

            // Filter by uploader (for instructor)
            if ($request->has('uploader_id')) {
                $query->byUploader($request->uploader_id);
            }

            // Search by name
            if ($request->has('search')) {
                $query->where('original_name', 'like', '%' . $request->search . '%');
            }

            // Pagination
            $perPage = $request->get('per_page', 15);
            $files = $query->latest()->paginate($perPage);

            return response()->json([
                'success' => true,
                'message' => 'Files retrieved successfully',
                'data' => $files
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve files',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Store a newly created file
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'file' => 'required|file|max:10240', // 10MB max
            'file_category' => 'required|in:material,assignment,resource,other',
            'uploaded_by' => 'required|exists:instructors,id'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $uploadedFile = $request->file('file');

            // Generate unique filename
            $fileName = time() . '_' . Str::random(10) . '.' . $uploadedFile->getClientOriginalExtension();

            // Store file
            $filePath = $uploadedFile->storeAs('files', $fileName, 'public');

            // Create file record
            $file = File::create([
                'original_name' => $uploadedFile->getClientOriginalName(),
                'file_name' => $fileName,
                'file_path' => $filePath,
                'mime_type' => $uploadedFile->getMimeType(),
                'file_size' => $uploadedFile->getSize(),
                'file_extension' => $uploadedFile->getClientOriginalExtension(),
                'file_category' => $request->file_category,
                'uploaded_by' => $request->uploaded_by
            ]);

            $file->load('uploader');

            return response()->json([
                'success' => true,
                'message' => 'File uploaded successfully',
                'data' => $file
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to upload file',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified file
     */
    public function show($id)
    {
        try {
            $file = File::with(['uploader', 'schedules', 'downloads.student'])->findOrFail($id);

            return response()->json([
                'success' => true,
                'message' => 'File retrieved successfully',
                'data' => $file
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'File not found',
                'error' => $e->getMessage()
            ], 404);
        }
    }

    /**
     * Update the specified file
     */
    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'file_category' => 'sometimes|in:material,assignment,resource,other',
            'file' => 'sometimes|file|max:10240'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $file = File::findOrFail($id);

            // Update file category if provided
            if ($request->has('file_category')) {
                $file->file_category = $request->file_category;
            }

            // Replace file if new file is uploaded
            if ($request->hasFile('file')) {
                // Delete old file
                $file->deleteFile();

                $uploadedFile = $request->file('file');
                $fileName = time() . '_' . Str::random(10) . '.' . $uploadedFile->getClientOriginalExtension();
                $filePath = $uploadedFile->storeAs('files', $fileName, 'public');

                $file->update([
                    'original_name' => $uploadedFile->getClientOriginalName(),
                    'file_name' => $fileName,
                    'file_path' => $filePath,
                    'mime_type' => $uploadedFile->getMimeType(),
                    'file_size' => $uploadedFile->getSize(),
                    'file_extension' => $uploadedFile->getClientOriginalExtension(),
                ]);
            }

            $file->save();
            $file->load('uploader');

            return response()->json([
                'success' => true,
                'message' => 'File updated successfully',
                'data' => $file
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update file',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified file
     */
    public function destroy($id)
    {
        try {
            $file = File::findOrFail($id);
            $file->delete(); // Will trigger deleteFile() in model boot method

            return response()->json([
                'success' => true,
                'message' => 'File deleted successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete file',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Download file
     */
    public function download(Request $request, $id)
    {
        try {
            $file = File::findOrFail($id);

            // Check if file exists in storage
            if (!Storage::disk('public')->exists($file->file_path)) {
                return response()->json([
                    'success' => false,
                    'message' => 'File not found in storage'
                ], 404);
            }

            // Log download if student_id is provided
            if ($request->has('student_id')) {
                FileDownload::create([
                    'file_id' => $file->id,
                    'student_id' => $request->student_id,
                    'schedule_id' => $request->schedule_id,
                    'downloaded_at' => now(),
                    'ip_address' => $request->ip()
                ]);
            }

            return Storage::disk('public')->download($file->file_path, $file->original_name);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to download file',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get file download statistics
     */
    public function downloadStats($id)
    {
        try {
            $file = File::with(['downloads.student'])->findOrFail($id);

            $stats = [
                'total_downloads' => $file->downloads->count(),
                'unique_students' => $file->downloads->unique('student_id')->count(),
                'recent_downloads' => $file->downloads()
                    ->with('student')
                    ->latest('downloaded_at')
                    ->take(10)
                    ->get(),
                'downloads_by_date' => $file->downloads()
                    ->selectRaw('DATE(downloaded_at) as date, COUNT(*) as count')
                    ->groupBy('date')
                    ->orderBy('date', 'desc')
                    ->take(30)
                    ->get()
            ];

            return response()->json([
                'success' => true,
                'message' => 'Download statistics retrieved successfully',
                'data' => $stats
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve download statistics',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
