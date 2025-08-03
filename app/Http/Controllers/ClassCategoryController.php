<?php

namespace App\Http\Controllers;

use App\Models\ClassCategory;
use Illuminate\Http\Request;

class ClassCategoryController extends Controller
{
    public function getData()
    {
        return response()->json(ClassCategory::all());
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string',
        ]);

        $category = ClassCategory::create($validated);

        return response()->json(['message' => 'Class category created.', 'data' => $category], 201);
    }

    public function show($id)
    {
        return response()->json(ClassCategory::findOrFail($id));
    }

    public function update(Request $request, $id)
    {
        $validated = $request->validate([
            'name' => 'string',
        ]);

        $category = ClassCategory::findOrFail($id);
        $category->update($validated);

        return response()->json(['message' => 'Class category updated.', 'data' => $category]);
    }

    public function destroy($id)
    {
        ClassCategory::findOrFail($id)->delete();

        return response()->json(['message' => 'Class category deleted.']);
    }
}
