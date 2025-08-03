<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\ScheduleDisruption;
use App\Models\StudentResponse;
use App\Services\ScheduleDisruptionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ScheduleResponseController extends Controller
{
    public function index()
    {
        $studentId = $this->getStudentId();
        
        $pendingDisruptions = ScheduleDisruption::whereHas('schedule', function($query) use ($studentId) {
                $query->whereHas('class.classStudents', function($subQuery) use ($studentId) {
                    $subQuery->where('student_id', $studentId);
                });
            })
            ->where('status', 'pending')
            ->where('response_deadline', '>', now())
            ->with(['schedule.class', 'instructorLogHour.instructor', 'finalReplacementInstructor'])
            ->get();
            
        $respondedDisruptions = ScheduleDisruption::whereHas('studentResponses', function($query) use ($studentId) {
                $query->where('student_id', $studentId);
            })
            ->with(['schedule.class', 'studentResponses' => function($query) use ($studentId) {
                $query->where('student_id', $studentId);
            }])
            ->latest()
            ->take(10)
            ->get();

        return view('student.schedule-responses.index', compact('pendingDisruptions', 'respondedDisruptions'));
    }

    public function show($disruptionId)
    {
        $studentId = $this->getStudentId();
        
        $disruption = ScheduleDisruption::whereHas('schedule', function($query) use ($studentId) {
                $query->whereHas('class.classStudents', function($subQuery) use ($studentId) {
                    $subQuery->where('student_id', $studentId);
                });
            })
            ->with([
                'schedule.class', 
                'instructorLogHour.instructor', 
                'finalReplacementInstructor',
                'studentResponses' => function($query) use ($studentId) {
                    $query->where('student_id', $studentId);
                }
            ])
            ->findOrFail($disruptionId);

        $hasResponded = $disruption->studentResponses->isNotEmpty();

        return view('student.schedule-responses.show', compact('disruption', 'hasResponded'));
    }

    public function store(Request $request, $disruptionId)
    {
        $studentId = $this->getStudentId();
        
        $request->validate([
            'choice' => 'required|in:cancel,reschedule,replace_instructor',
            'preferred_date' => 'nullable|date|required_if:choice,reschedule',
            'preferred_start_time' => 'nullable|date_format:H:i|required_if:choice,reschedule',
            'preferred_end_time' => 'nullable|date_format:H:i|required_if:choice,reschedule|after:preferred_start_time',
            'preferred_replacement_instructor_id' => 'nullable|exists:instructors,id|required_if:choice,replace_instructor',
            'notes' => 'nullable|string|max:500'
        ]);

        $disruption = ScheduleDisruption::whereHas('schedule', function($query) use ($studentId) {
                $query->whereHas('class.classStudents', function($subQuery) use ($studentId) {
                    $subQuery->where('student_id', $studentId);
                });
            })
            ->where('status', 'pending')
            ->findOrFail($disruptionId);

        // Check if already responded
        $existingResponse = StudentResponse::where('schedule_disruption_id', $disruptionId)
                                         ->where('student_id', $studentId)
                                         ->first();

        if ($existingResponse) {
            return redirect()->back()->with('error', 'You have already responded to this schedule change.');
        }

        // Check deadline
        if (now() > $disruption->response_deadline) {
            return redirect()->back()->with('error', 'Response deadline has passed.');
        }

        StudentResponse::create([
            'schedule_disruption_id' => $disruptionId,
            'student_id' => $studentId,
            'choice' => $request->choice,
            'preferred_date' => $request->preferred_date,
            'preferred_start_time' => $request->preferred_start_time,
            'preferred_end_time' => $request->preferred_end_time,
            'preferred_replacement_instructor_id' => $request->preferred_replacement_instructor_id,
            'notes' => $request->notes,
            'responded_at' => now()
        ]);

        // Update vote distribution and check if processing is needed
        $service = new ScheduleDisruptionService();
        $service->updateVoteDistribution($disruption);

        return redirect()->route('student.schedule-responses.index')
                        ->with('success', 'Your response has been recorded successfully.');
    }

    public function update(Request $request, $disruptionId)
    {
        $studentId = $this->getStudentId();
        
        $request->validate([
            'choice' => 'required|in:cancel,reschedule,replace_instructor',
            'preferred_date' => 'nullable|date|required_if:choice,reschedule',
            'preferred_start_time' => 'nullable|date_format:H:i|required_if:choice,reschedule',
            'preferred_end_time' => 'nullable|date_format:H:i|required_if:choice,reschedule|after:preferred_start_time',
            'preferred_replacement_instructor_id' => 'nullable|exists:instructors,id|required_if:choice,replace_instructor',
            'notes' => 'nullable|string|max:500'
        ]);

        $disruption = ScheduleDisruption::where('status', 'pending')->findOrFail($disruptionId);
        
        $response = StudentResponse::where('schedule_disruption_id', $disruptionId)
                                 ->where('student_id', $studentId)
                                 ->firstOrFail();

        // Check deadline
        if (now() > $disruption->response_deadline) {
            return redirect()->back()->with('error', 'Response deadline has passed.');
        }

        $response->update([
            'choice' => $request->choice,
            'preferred_date' => $request->preferred_date,
            'preferred_start_time' => $request->preferred_start_time,
            'preferred_end_time' => $request->preferred_end_time,
            'preferred_replacement_instructor_id' => $request->preferred_replacement_instructor_id,
            'notes' => $request->notes,
            'responded_at' => now()
        ]);

        // Update vote distribution and check if processing is needed
        $service = new ScheduleDisruptionService();
        $service->updateVoteDistribution($disruption);

        return redirect()->route('student.schedule-responses.index')
                        ->with('success', 'Your response has been updated successfully.');
    }

    private function getStudentId()
    {
        // Assuming the user is related to a student through family
        // Adjust this logic based on your actual user-student relationship
        $user = Auth::user();
        $family = $user->family;
        
        if (!$family || !$family->students()->exists()) {
            abort(403, 'No student associated with this account.');
        }
        
        // If family has multiple students, you might want to handle student selection
        return $family->students()->first()->id;
    }
}
