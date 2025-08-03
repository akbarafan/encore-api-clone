<?php

namespace App\Http\Controllers;

use App\Models\ClassType;
use Illuminate\Http\Request;

class ClassTypeController extends Controller
{
    public function getData()
    {
        return response()->json(ClassType::all());
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string',
        ]);

        $type = ClassType::create($validated);

        return response()->json(['message' => 'Class type created.', 'data' => $type], 201);
    }

    public function show($id)
    {
        return response()->json(ClassType::findOrFail($id));
    }

    public function update(Request $request, $id)
    {
        $validated = $request->validate([
            'name' => 'string',
        ]);

        $type = ClassType::findOrFail($id);
        $type->update($validated);

        return response()->json(['message' => 'Class type updated.', 'data' => $type]);
    }

    public function destroy($id)
    {
        ClassType::findOrFail($id)->delete();

        return response()->json(['message' => 'Class type deleted.']);
    }
}
