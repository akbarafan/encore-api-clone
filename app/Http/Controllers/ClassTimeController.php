<?php

namespace App\Http\Controllers;

use App\Models\ClassTime;
use Exception;
use Illuminate\Http\Request;

class ClassTimeController extends Controller
{
    public function getData()
    {
        $classTimes = ClassTime::all()->select('id', 'name');
        return response()->json($classTimes);
    }
    
    public function store(Request $request)
    {
        try {
            $request->validate([
                'name' => 'required|string|max:255',
            ]);

            $classTime = ClassTime::create([
                'name' => $request->name,
            ]);

            if (!$classTime) {
                return response()->json(['message' => 'Failed to store class time'], 500);
            } else {
                return response()->json([
                    'message' => 'Class time created successfully',
                    'data' => $classTime,
                ]);
            }
        } catch (Exception $e) {
            return response()->json(['message' => 'Failed to store class time'], 500);
        }
    }

    public function show($id)
    {
        $classTime = ClassTime::findOrFail($id);
        return response()->json($classTime);
    }

    public function update(Request $request, $id)
    {
        try {
            $request->validate([
                'name' => 'required|string|max:255',
            ]);

            $classTime = ClassTime::findOrFail($id);
            $update = $classTime->update([
                'name' => $request->name,
            ]);

            if (!$update) {
                return response()->json(['message' => 'Failed to update class time'], 500);
            }

            return response()->json([
                'message' => 'Class time updated successfully',
                'data' => $classTime,
            ]);
        } catch (Exception $e) {
            return response()->json(['message' => 'Failed to update class time'], 500);
        }
    }

    public function destroy($id)
    {
        try {
            $classTime = ClassTime::findOrFail($id);
            $classTime->delete();

            if (!$classTime) {
                return response()->json(['message' => 'Class time not found'], 500);
            }

            return response()->json(['message' => 'Class time deleted successfully']);
        } catch (Exception $e) {
            return response()->json(['message' => 'Failed to delete class time'], 500);
        }
    }
}
