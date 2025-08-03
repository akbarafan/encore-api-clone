<?php

namespace App\Http\Controllers;

use App\Models\Schedule;
use App\Models\Classes;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;

class ScheduleController extends Controller
{
    /**
     * Display a listing of schedules
     */
    public function index(Request $request)
    {
        try {
            $query = Schedule::with(['class', 'files.uploader', 'reschedules']);

            // Filter by class
            if ($request->has('class_id')) {
                $query->where('class_id', $request->class_id);
            }

            // Filter by date range
            if ($request->has('start_date')) {
                $query->where('date', '>=', $request->start_date);
            }

            if ($request->has('end_date')) {
                $query->where('date', '<=', $request->end_date);
            }

            // Filter by instructor (through class relationship)
            if ($request->has('instructor_id')) {
                $query->whereHas('class', function ($q) use ($request) {
                    $q->where('instructor_id', $request->instructor_id);
                });
            }

            // Search by title
            if ($request->has('search')) {
                $query->where('title', 'like', '%' . $request->search . '%');
            }

            // Pagination
            $perPage = $request->get('per_page', 15);
            $schedules = $query->latest('date')->paginate($perPage);

            return response()->json([
                'success' => true,
                'message' => 'Schedules retrieved successfully',
                'data' => $schedules
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve schedules',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Store a newly created schedule
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'class_id' => 'required|exists:classes,id',
            'title' => 'required|string|max:255',
            'date' => 'required|date',
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i|after:start_time',
            'notes' => 'nullable|string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $schedule = Schedule::create($request->all());
            $schedule->load(['class', 'files']);

            return response()->json([
                'success' => true,
                'message' => 'Schedule created successfully',
                'data' => $schedule
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create schedule',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified schedule
     */
    public function show($id)
    {
        try {
            $schedule = Schedule::with([
                'class.instructor',
                'class.students',
                'files.uploader',
                'reschedules.student',
                'reschedules.instructor'
            ])->findOrFail($id);

            return response()->json([
                'success' => true,
                'message' => 'Schedule retrieved successfully',
                'data' => $schedule
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Schedule not found',
                'error' => $e->getMessage()
            ], 404);
        }
    }

    /**
     * Update the specified schedule
     */
    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'class_id' => 'sometimes|exists:classes,id',
            'title' => 'sometimes|string|max:255',
            'date' => 'sometimes|date',
            'start_time' => 'sometimes|date_format:H:i',
            'end_time' => 'sometimes|date_format:H:i|after:start_time',
            'notes' => 'nullable|string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $schedule = Schedule::findOrFail($id);
            $schedule->update($request->all());
            $schedule->load(['class', 'files']);

            return response()->json([
                'success' => true,
                'message' => 'Schedule updated successfully',
                'data' => $schedule
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update schedule',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified schedule
     */
    public function destroy($id)
    {
        try {
            $schedule = Schedule::findOrFail($id);
            $schedule->delete();

            return response()->json([
                'success' => true,
                'message' => 'Schedule deleted successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete schedule',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get schedules for today
     */
    public function today()
    {
        try {
            $schedules = Schedule::with(['class.instructor', 'files'])
                ->whereDate('date', today())
                ->orderBy('start_time')
                ->get();

            return response()->json([
                'success' => true,
                'message' => 'Today schedules retrieved successfully',
                'data' => $schedules
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve today schedules',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get upcoming schedules
     */
    public function upcoming(Request $request)
    {
        try {
            $days = $request->get('days', 7); // Default 7 days

            $schedules = Schedule::with(['class.instructor', 'files'])
                ->where('date', '>', today())
                ->where('date', '<=', today()->addDays($days))
                ->orderBy('date')
                ->orderBy('start_time')
                ->get();

            return response()->json([
                'success' => true,
                'message' => 'Upcoming schedules retrieved successfully',
                'data' => $schedules
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve upcoming schedules',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get schedule statistics
     */
    public function statistics()
    {
        try {
            $stats = [
                'total_schedules' => Schedule::count(),
                'today_schedules' => Schedule::whereDate('date', today())->count(),
                'this_week_schedules' => Schedule::whereBetween('date', [
                    now()->startOfWeek(),
                    now()->endOfWeek()
                ])->count(),
                'this_month_schedules' => Schedule::whereMonth('date', now()->month)
                    ->whereYear('date', now()->year)
                    ->count(),
                'schedules_with_files' => Schedule::has('files')->count(),
                'total_files_attached' => \DB::table('schedule_files')->count(),
                'upcoming_schedules' => Schedule::where('date', '>', today())->count(),
                'past_schedules' => Schedule::where('date', '<', today())->count()
            ];

            return response()->json([
                'success' => true,
                'message' => 'Schedule statistics retrieved successfully',
                'data' => $stats
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve schedule statistics',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
