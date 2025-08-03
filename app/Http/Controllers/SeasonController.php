<?php

namespace App\Http\Controllers;

use App\Models\Season;
use Illuminate\Http\Request;

class SeasonController extends Controller
{
    public function index()
    {
        return response()->json(Season::all());
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string',
            'start_date' => 'required|date',
            'end_date' => 'required|date',
        ]);

        $season = Season::create($validated);

        return response()->json(['message' => 'Season created.', 'data' => $season], 201);
    }

    public function show($id)
    {
        return response()->json(Season::findOrFail($id));
    }

    public function update(Request $request, $id)
    {
        $validated = $request->validate([
            'name' => 'string',
            'start_date' => 'date',
            'end_date' => 'date',
        ]);

        $season = Season::findOrFail($id);
        $season->update($validated);

        return response()->json(['message' => 'Season updated.', 'data' => $season]);
    }

    public function destroy($id)
    {
        Season::findOrFail($id)->delete();

        return response()->json(['message' => 'Season deleted.']);
    }
}
