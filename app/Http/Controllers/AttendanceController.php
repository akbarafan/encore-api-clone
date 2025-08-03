<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use Illuminate\Http\Request;

class AttendanceController extends Controller
{
    public function index()
    {
        return response()->json(Attendance::with(['student', 'class'])->get());
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'student_id' => 'required|exists:students,id',
            'class_id' => 'required|exists:classes,id',
            'date' => 'required|date',
            'status' => 'required|string',
        ]);

        $attendance = Attendance::create($validated);

        return response()->json(['message' => 'Attendance recorded.', 'data' => $attendance], 201);
    }

    public function show($id)
    {
        $attendance = Attendance::with(['student', 'class'])->findOrFail($id);
        return response()->json(['data' => $attendance]);
    }

    public function update(Request $request, $id)
    {
        $validated = $request->validate([
            'student_id' => 'exists:students,id',
            'class_id' => 'exists:classes,id',
            'date' => 'date',
            'status' => 'string',
        ]);

        $attendance = Attendance::findOrFail($id);
        $attendance->update($validated);

        return response()->json(['message' => 'Attendance updated.', 'data' => $attendance]);
    }

    public function destroy($id)
    {
        $attendance = Attendance::findOrFail($id);
        $attendance->delete();

        return response()->json(['message' => 'Attendance deleted.']);
    }
}
