<?php

namespace App\Http\Controllers;

use App\Models\Instructor;
use App\Models\InstructorLogHour;
use App\Services\ScheduleDisruptionService;
use Illuminate\Http\Request;

class InstructorLogHourController extends Controller
{
    // List all log hours of the logged-in user's instructors
    public function index(Request $request)
    {
        $userId = $request->user()->id;
        $instructorIds = Instructor::where('user_id', $userId)->pluck('id');

        $logs = InstructorLogHour::whereIn('instructor_id', $instructorIds)->get();

        return response()->json($logs);
    }

    public function show(Request $request, $id)
    {
        $log = InstructorLogHour::findOrFail($id);

        if ($log->instructor->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Unauthorized.'], 403);
        }

        return response()->json($log);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'instructor_id' => 'required|exists:instructors,id',
            'schedule_id' => 'nullable|exists:schedules,id',
            'date' => 'required|date',
            'clock_in' => 'nullable|date_format:Y-m-d H:i:s',
            'clock_out' => 'nullable|date_format:Y-m-d H:i:s',
            'activity_type' => 'required|in:teaching,admin,overtime,time_off,sick',
            'clock_in_notes' => 'nullable|string|max:500',
            'clock_out_notes' => 'nullable|string|max:500',
            'approval_status' => 'nullable|in:pending,approved,rejected'
        ]);

        $instructor = Instructor::where('user_id', $request->user()->id)
            ->findOrFail($validated['instructor_id']);

        // Set default approval status
        $validated['approval_status'] = $validated['approval_status'] ?? 'approved';

        $log = InstructorLogHour::create($validated);

        // Check if this is a sick/time_off activity that affects a schedule
        if (in_array($log->activity_type, ['sick', 'time_off']) && $log->schedule_id) {
            $disruptionService = new ScheduleDisruptionService();
            $disruption = $disruptionService->createDisruptionFromLogHour($log);
            
            if ($disruption) {
                return response()->json([
                    'message' => 'Log hour created and schedule disruption notification sent to students.',
                    'log' => $log,
                    'disruption' => $disruption
                ], 201);
            }
        }

        return response()->json(['message' => 'Log hour created.', 'log' => $log], 201);
    }

    public function update(Request $request, $id)
    {
        $validated = $request->validate([
            'schedule_id' => 'nullable|exists:schedules,id',
            'date' => 'date',
            'clock_in' => 'nullable|date_format:Y-m-d H:i:s',
            'clock_out' => 'nullable|date_format:Y-m-d H:i:s',
            'activity_type' => 'nullable|in:teaching,admin,overtime,time_off,sick',
            'clock_in_notes' => 'nullable|string|max:500',
            'clock_out_notes' => 'nullable|string|max:500',
            'approval_status' => 'nullable|in:pending,approved,rejected'
        ]);

        $log = InstructorLogHour::findOrFail($id);

        if ($log->instructor->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Unauthorized.'], 403);
        }

        // Store old values for comparison
        $oldActivityType = $log->activity_type;
        $oldScheduleId = $log->schedule_id;

        $log->update($validated);

        // Check if activity type changed to sick/time_off and affects a schedule
        if (in_array($log->activity_type, ['sick', 'time_off']) && 
            $log->schedule_id && 
            ($oldActivityType !== $log->activity_type || $oldScheduleId !== $log->schedule_id)) {
            
            $disruptionService = new ScheduleDisruptionService();
            $disruption = $disruptionService->createDisruptionFromLogHour($log);
            
            if ($disruption) {
                return response()->json([
                    'message' => 'Log hour updated and schedule disruption notification sent to students.',
                    'log' => $log,
                    'disruption' => $disruption
                ]);
            }
        }

        return response()->json(['message' => 'Log hour updated.', 'log' => $log]);
    }

    public function destroy(Request $request, $id)
    {
        $log = InstructorLogHour::findOrFail($id);

        if ($log->instructor->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Unauthorized.'], 403);
        }

        $log->delete();

        return response()->json(['message' => 'Log hour deleted.']);
    }
}
