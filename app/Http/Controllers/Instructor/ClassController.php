<?php

namespace App\Http\Controllers\Instructor;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Instructor;
use App\Models\Classes;
use App\Models\ClassCategory;
use App\Models\ClassType;
use App\Models\ClassTime;
use App\Models\ClassLocation;
use App\Models\Season;
use App\Helpers\InstructorHelper;

class ClassController extends Controller
{
    private function getCurrentInstructor()
    {
        // Use consistent dummy instructor (no authentication)
        return InstructorHelper::getCurrentInstructorRecord();
    }

    public function index(Request $request)
    {
        $instructor = $this->getCurrentInstructor();

        // Build query with filters
        $query = Classes::byInstructor($instructor->id)
            ->with(['category', 'type', 'season', 'classTime', 'classLocation']);

        // Apply filters
        if ($request->filled('season_id')) {
            $query->bySeason($request->season_id);
        }

        if ($request->filled('category_id')) {
            $query->byCategory($request->category_id);
        }

        if ($request->filled('type_id')) {
            $query->byType($request->type_id);
        }

        if ($request->filled('status')) {
            if ($request->status === 'approved') {
                $query->approved();
            } elseif ($request->status === 'pending') {
                $query->pending();
            }
        }

        if ($request->filled('search')) {
            $query->where('name', 'like', '%' . $request->search . '%');
        }

        $classes = $query->latest()->get();

        // Get statistics
        $stats = [
            'total' => Classes::byInstructor($instructor->id)->count(),
            'approved' => Classes::byInstructor($instructor->id)->approved()->count(),
            'pending' => Classes::byInstructor($instructor->id)->pending()->count(),
        ];

        // Ambil data untuk dropdown
        $categories = ClassCategory::all();
        $types = ClassType::all();
        $seasons = Season::all();
        $times = ClassTime::all();
        $locations = ClassLocation::all();

        return view('instructor.my-classes', compact(
            'classes',
            'categories',
            'types',
            'seasons',
            'times',
            'locations',
            'instructor',
            'stats'
        ));
    }

    public function store(Request $request)
    {
        $instructor = $this->getCurrentInstructor();

        $validated = $request->validate([
            'season_id' => 'required|exists:seasons,id',
            'class_category_id' => 'required|exists:class_categories,id',
            'class_type_id' => 'required|exists:class_types,id',
            'class_time_id' => 'required|exists:class_times,id',
            'class_location_id' => 'required|exists:class_locations,id',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'cost' => 'required|numeric|min:0',
            'scheduled_at' => 'nullable|date',
        ]);

        $validated['instructor_id'] = $instructor->id;
        $validated['is_approved'] = false; // Default belum approved

        Classes::create($validated);

        return redirect()->route('instructor.classes.index')
            ->with('success', 'Class has been successfully created and is awaiting admin approval.');
    }

    public function show($id)
    {
        $instructor = $this->getCurrentInstructor();
        $class = Classes::where('id', $id)
            ->where('instructor_id', $instructor->id)
            ->with(['category', 'type', 'season', 'classTime', 'classLocation'])
            ->firstOrFail();

        return response()->json($class);
    }

    public function update(Request $request, $id)
    {
        $instructor = $this->getCurrentInstructor();

        $class = Classes::where('id', $id)->where('instructor_id', $instructor->id)->firstOrFail();

        $validated = $request->validate([
            'season_id' => 'required|exists:seasons,id',
            'class_category_id' => 'required|exists:class_categories,id',
            'class_type_id' => 'required|exists:class_types,id',
            'class_time_id' => 'required|exists:class_times,id',
            'class_location_id' => 'required|exists:class_locations,id',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'cost' => 'required|numeric|min:0',
            'scheduled_at' => 'nullable|date',
        ]);

        // Reset approval jika ada perubahan
        $validated['is_approved'] = false;

        $class->update($validated);

        return redirect()->route('instructor.classes.index')
            ->with('success', 'Class has been successfully updated and is awaiting admin approval.');
    }

    public function destroy($id)
    {
        $instructor = $this->getCurrentInstructor();

        $class = Classes::where('id', $id)->where('instructor_id', $instructor->id)->firstOrFail();
        $class->delete();

        return redirect()->route('instructor.classes.index')
            ->with('success', 'Class has been successfully deleted.');
    }
}
