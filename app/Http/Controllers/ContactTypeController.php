<?php

namespace App\Http\Controllers;

use App\Models\ContactType;
use Illuminate\Http\Request;

class ContactTypeController extends Controller
{
    public function index()
    {
        $contactTypes = ContactType::all();
        return response()->json($contactTypes);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
        ]);

        $contactType = ContactType::create($validated);

        return response()->json([
            'message' => 'Contact type created successfully.',
            'data' => $contactType,
        ], 201);
    }

    public function show($id)
    {
        $contactType = ContactType::findOrFail($id);
        return response()->json($contactType);
    }

    public function update(Request $request, $id)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
        ]);

        $contactType = ContactType::findOrFail($id);
        $contactType->update($validated);

        return response()->json([
            'message' => 'Contact type updated successfully.',
            'data' => $contactType,
        ]);
    }

    public function destroy($id)
    {
        $contactType = ContactType::findOrFail($id);
        $contactType->delete();

        return response()->json(['message' => 'Contact type deleted successfully.']);
    }
}
