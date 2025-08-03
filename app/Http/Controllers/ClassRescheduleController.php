<?php

namespace App\Http\Controllers;

use App\Models\Classes;
use App\Models\ClassReschedule;
use Illuminate\Http\Request;

class ClassRescheduleController extends Controller
{
    public function index()
    {
        return response()->json(ClassReschedule::all());
    }

    public function requestReschedule(Request $request, $classId)
    {
        $validated = $request->validate([
            'new_schedule' => 'required|date',
            'reason' => 'nullable|string',
        ]);

        $class = Classes::findOrFail($classId);

        $reschedule = ClassReschedule::create([
            'class_id' => $class->id,
            'student_id' => auth()->user()->family->students->first()->id ?? null,
            'old_schedule' => $class->scheduled_at,
            'new_schedule' => $validated['new_schedule'],
            'reason' => $validated['reason'],
            'status' => 'pending',
        ]);

        return response()->json(['message' => 'Reschedule request submitted.', 'reschedule' => $reschedule]);
    }

    public function approveReschedule($id)
    {
        $reschedule = ClassReschedule::findOrFail($id);
        $reschedule->status = 'approved';
        $reschedule->save();

        $reschedule->class->update(['scheduled_at' => $reschedule->new_schedule]);

        return response()->json(['message' => 'Reschedule approved.', 'class' => $reschedule->class]);
    }

    public function rejectReschedule($id)
    {
        $reschedule = ClassReschedule::findOrFail($id);
        $reschedule->status = 'rejected';
        $reschedule->save();

        return response()->json(['message' => 'Reschedule rejected.', 'reschedule' => $reschedule]);
    }

    public function destroy($id)
    {
        $reschedule = ClassReschedule::findOrFail($id);
        $reschedule->delete();

        return response()->json(['message' => 'Reschedule deleted.']);
    }
}
