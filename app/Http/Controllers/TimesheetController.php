<?php

namespace App\Http\Controllers;

use App\Models\Timesheet;
use Illuminate\Http\Request;

class TimesheetController extends Controller
{
    public function index(Request $request)
    {
        $timesheets = Timesheet::with('instructor')->get();
        return response()->json($timesheets);
    }

    // Show single timesheet
    public function show($id)
    {
        $timesheet = Timesheet::with('instructor')->findOrFail($id);
        return response()->json($timesheet);
    }

    // Create new timesheet
    public function store(Request $request)
    {
        $validated = $request->validate([
            'instructor_id' => 'required|exists:instructors,id',
            'month' => 'required|string',
            'total_hours' => 'required|numeric',
            'status' => 'required|string',
            'approved_by' => 'nullable|string',
        ]);

        $timesheet = Timesheet::create($validated);

        return response()->json(['message' => 'Timesheet created.', 'timesheet' => $timesheet], 201);
    }

    // Update timesheet
    public function update(Request $request, $id)
    {
        $validated = $request->validate([
            'instructor_id' => 'exists:instructors,id',
            'month' => 'string',
            'total_hours' => 'numeric',
            'status' => 'string',
            'approved_by' => 'nullable|string',
        ]);

        $timesheet = Timesheet::findOrFail($id);
        $timesheet->update($validated);

        return response()->json(['message' => 'Timesheet updated.', 'timesheet' => $timesheet]);
    }

    // Delete timesheet (soft delete)
    public function destroy($id)
    {
        $timesheet = Timesheet::findOrFail($id);
        $timesheet->delete();

        return response()->json(['message' => 'Timesheet deleted.']);
    }
}
