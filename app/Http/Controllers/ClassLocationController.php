<?php

namespace App\Http\Controllers;

use App\Models\ClassLocation;
use Exception;
use Illuminate\Http\Request;

class ClassLocationController extends Controller
{
    public function getData()
    {
        $locations = ClassLocation::all()->select('id', 'city', 'address');
        return response()->json($locations);
    }
    public function store(Request $request)
    {
        try {
            $request->validate([
                'city' => 'required|string|max:255',
                'address' => 'nullable|string|max:255',
            ]);

            $location = ClassLocation::create([
                'city' => $request->city,
                'address' => $request->address,
            ]);
            if (!$location) {
                return response()->json(['message' => 'Failed to store class location'], 500);
            }else{
                return response()->json([
                    'message' => 'Class location created successfully',
                    'data' => $location,
                ]);
            }
        } catch (Exception $e) {
            return response()->json(['message' => 'Failed to store class location'], 500);
        }

        return response()->json($location, 201);
    }
    public function show($id)
    {
        $location = ClassLocation::findOrFail($id);
        return response()->json($location);
    }
    public function update(Request $request, $id)
    {
        try {
            $request->validate([
                'city' => 'required|string|max:255',
                'address' => 'nullable|string|max:255',
            ]);

            $location = ClassLocation::findOrFail($id);
            $update = $location->update([
                'city' => $request->city,
                'address' => $request->address,
            ]);
            if (!$update) {
                return response()->json(['message' => 'Failed to update class location'], 500);
            }
            return response()->json( [
                'message' => 'Class location updated successfully',
                'data' => $location,
            ],200);
        } catch (Exception $e) {
            return response()->json(
                [
                    'message' => 'Error updating class location: ' . $e->getMessage(),
                ],
                500,
            );
        }

        return response()->json($location);
    }
    public function destroy($id)
    {
        try {
            $location = ClassLocation::findOrFail($id);
            $location->delete();
            if (!$location) {
                return response()->json(['message' => 'Class location not found'],500);
            }
            return response()->json(['message' => 'Class location deleted successfully']);
        } catch (Exception $e) {
            return response()->json(
                [
                    'message' => 'Error deleting class location: ' . $e->getMessage(),
                ],
                500,
            );
        }
    }
}
