<?php

namespace App\Http\Controllers;

use App\Models\Instructor;
use Illuminate\Http\Request;

class InstructorController extends Controller
{
    public function index(Request $request)
    {
        $instructors = Instructor::all();
        return response()->json($instructors);
    }

    public function show(Request $request, $id)
    {
        $instructor = Instructor::where('user_id', $request->user()->id)->findOrFail($id);
        return response()->json($instructor);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string',
            'availability' => 'nullable|string',
            'payrate' => 'nullable|numeric',
        ]);

        $instructor = Instructor::create([
            'user_id' => $request->user()->id,
            'name' => $validated['name'],
            'availability' => $validated['availability'] ?? null,
            'payrate' => $validated['payrate'] ?? null,
        ]);

        return response()->json(['message' => 'Instructor created.', 'instructor' => $instructor], 201);
    }

    public function update(Request $request, $id)
    {
        $validated = $request->validate([
            'name' => 'string',
            'availability' => 'nullable|string',
            'payrate' => 'nullable|numeric',
        ]);

        $instructor = Instructor::where('user_id', $request->user()->id)->findOrFail($id);
        $instructor->update($validated);

        return response()->json(['message' => 'Instructor updated.', 'instructor' => $instructor]);
    }

    public function destroy(Request $request, $id)
    {
        $instructor = Instructor::where('user_id', $request->user()->id)->findOrFail($id);
        $instructor->delete();

        return response()->json(['message' => 'Instructor deleted.']);
    }
}
