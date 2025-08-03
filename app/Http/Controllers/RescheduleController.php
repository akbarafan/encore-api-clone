<?php

namespace App\Http\Controllers;

use App\Models\Reschedule;
use Illuminate\Http\Request;

class RescheduleController extends Controller
{
    public function index()
    {
        return response()->json(Reschedule::with(['schedule', 'student', 'instructor'])->get());
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'schedule_id' => 'required|exists:schedules,id',
            'student_id' => 'nullable|exists:students,id',
            'instructor_id' => 'nullable|exists:instructors,id',
            'new_date' => 'required|date',
            'new_start_time' => 'required',
            'new_end_time' => 'required',
            'reason' => 'nullable|string',
        ]);

        $reschedule = Reschedule::create($validated);

        return response()->json(['reschedule' => $reschedule], 201);
    }

    public function show($id)
    {
        return response()->json(Reschedule::with(['schedule', 'student', 'instructor'])->findOrFail($id));
    }

    public function update(Request $request, $id)
    {
        $reschedule = Reschedule::findOrFail($id);

        $validated = $request->validate([
            'new_date' => 'date',
            'new_start_time' => 'nullable',
            'new_end_time' => 'nullable',
            'reason' => 'nullable|string',
        ]);

        $reschedule->update($validated);

        return response()->json(['reschedule' => $reschedule]);
    }

    public function destroy($id)
    {
        Reschedule::findOrFail($id)->delete();
        return response()->json(['message' => 'Reschedule deleted.']);
    }

    // Approve reschedule
    public function approve($id)
    {
        $reschedule = Reschedule::findOrFail($id);
        $reschedule->update(['status' => 'approved']);
        return response()->json(['message' => 'Reschedule approved.', 'reschedule' => $reschedule]);
    }

    // Reject reschedule
    public function reject($id)
    {
        $reschedule = Reschedule::findOrFail($id);
        $reschedule->update(['status' => 'rejected']);
        return response()->json(['message' => 'Reschedule rejected.', 'reschedule' => $reschedule]);
    }
}
