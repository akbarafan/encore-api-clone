<?php

namespace App\Http\Controllers\Instructor;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Instructor;
use App\Models\Classes;
use App\Models\Schedule;
use App\Models\Reschedule;
use App\Helpers\InstructorHelper;
use Carbon\Carbon;

class ScheduleController extends Controller
{
    private function getCurrentInstructor()
    {
        // Use consistent dummy instructor (no authentication)
        return InstructorHelper::getCurrentInstructorRecord();
    }

    public function index(Request $request)
    {
        $instructor = $this->getCurrentInstructor();

        // Query untuk schedules yang ada di class instructor dengan reschedule yang approved
        $schedulesQuery = Schedule::whereHas('class', function($query) use ($instructor) {
                $query->where('instructor_id', $instructor->id);
            })
            ->with([
                'class.category',
                'class.type',
                'reschedules' => function($query) {
                    $query->where('status', 'approved')->latest();
                }
            ]);

        // Filter by class
        if ($request->has('class_id') && $request->class_id != '') {
            $schedulesQuery->where('class_id', $request->class_id);
        }

        // Filter by date range
        if ($request->has('date_from') && $request->date_from != '') {
            $schedulesQuery->where('date', '>=', $request->date_from);
        }
        if ($request->has('date_to') && $request->date_to != '') {
            $schedulesQuery->where('date', '<=', $request->date_to);
        }

        // Filter by status
        if ($request->has('status') && $request->status != '') {
            $today = Carbon::today();
            switch ($request->status) {
                case 'upcoming':
                    $schedulesQuery->where('date', '>', $today);
                    break;
                case 'today':
                    $schedulesQuery->whereDate('date', $today);
                    break;
                case 'past':
                    $schedulesQuery->where('date', '<', $today);
                    break;
                case 'rescheduled':
                    $schedulesQuery->whereHas('reschedules', function($query) {
                        $query->where('status', 'approved');
                    });
                    break;
            }
        }

        $schedules = $schedulesQuery->orderBy('date', 'asc')
            ->orderBy('start_time', 'asc')
            ->paginate(15);

        // Transform schedules to include reschedule info and status
        $schedules->getCollection()->transform(function ($schedule) {
            $today = Carbon::today();
            $scheduleDate = Carbon::parse($schedule->date);

            // Get latest approved reschedule
            $latestReschedule = $schedule->reschedules->first();

            // Determine actual schedule details
            if ($latestReschedule) {
                $schedule->actual_date = $latestReschedule->new_date;
                $schedule->actual_start_time = $latestReschedule->new_start_time;
                $schedule->actual_end_time = $latestReschedule->new_end_time;
                $schedule->is_rescheduled = true;
                $schedule->reschedule_info = $latestReschedule;
            } else {
                $schedule->actual_date = $schedule->date;
                $schedule->actual_start_time = $schedule->start_time;
                $schedule->actual_end_time = $schedule->end_time;
                $schedule->is_rescheduled = false;
                $schedule->reschedule_info = null;
            }

            // Determine status
            $actualDate = Carbon::parse($schedule->actual_date);
            if ($actualDate->isToday()) {
                $schedule->status = 'today';
                $schedule->status_badge_class = 'bg-warning-subtle text-warning';
            } elseif ($actualDate->isFuture()) {
                $schedule->status = 'upcoming';
                $schedule->status_badge_class = 'bg-primary-subtle text-primary';
            } else {
                $schedule->status = 'completed';
                $schedule->status_badge_class = 'bg-success-subtle text-success';
            }

            // Check if can be edited/rescheduled (only future schedules)
            $schedule->can_reschedule = $actualDate->isFuture();
            $schedule->can_edit = $actualDate->isFuture() && !$schedule->is_rescheduled;

            return $schedule;
        });

        // Get instructor's classes for filter dropdown
        $instructorClasses = Classes::where('instructor_id', $instructor->id)->get();

        // Dashboard stats for schedules
        $totalSchedules = Schedule::whereHas('class', function($query) use ($instructor) {
            $query->where('instructor_id', $instructor->id);
        })->count();

        $todaySchedules = Schedule::whereHas('class', function($query) use ($instructor) {
            $query->where('instructor_id', $instructor->id);
        })->whereDate('date', today())->count();

        $upcomingSchedules = Schedule::whereHas('class', function($query) use ($instructor) {
            $query->where('instructor_id', $instructor->id);
        })->where('date', '>', today())->count();

        $thisWeekSchedules = Schedule::whereHas('class', function($query) use ($instructor) {
            $query->where('instructor_id', $instructor->id);
        })->whereBetween('date', [now()->startOfWeek(), now()->endOfWeek()])->count();

        $rescheduledSchedules = Schedule::whereHas('class', function($query) use ($instructor) {
            $query->where('instructor_id', $instructor->id);
        })->whereHas('reschedules', function($query) {
            $query->where('status', 'approved');
        })->count();

        // Pending reschedule requests
        $pendingReschedules = Reschedule::whereHas('schedule.class', function($query) use ($instructor) {
            $query->where('instructor_id', $instructor->id);
        })->where('status', 'pending')->count();

        // Available statuses for filter
        $availableStatuses = [
            'upcoming' => 'Upcoming',
            'today' => 'Today',
            'past' => 'Past',
            'rescheduled' => 'Rescheduled'
        ];

        return view('instructor.my-schedules', compact(
            'schedules',
            'instructorClasses',
            'totalSchedules',
            'todaySchedules',
            'upcomingSchedules',
            'thisWeekSchedules',
            'rescheduledSchedules',
            'pendingReschedules',
            'availableStatuses'
        ));
    }

    public function store(Request $request)
    {
        $instructor = $this->getCurrentInstructor();

        $validated = $request->validate([
            'class_id' => 'required|exists:classes,id',
            'title' => 'required|string|max:255',
            'date' => 'required|date|after_or_equal:today',
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i|after:start_time',
            'notes' => 'nullable|string|max:1000',
        ]);

        // Pastikan class milik instructor
        $class = Classes::where('id', $validated['class_id'])
            ->where('instructor_id', $instructor->id)
            ->firstOrFail();

        // Check for time conflicts
        $conflictExists = Schedule::whereHas('class', function($query) use ($instructor) {
                $query->where('instructor_id', $instructor->id);
            })
            ->where('date', $validated['date'])
            ->where(function($query) use ($validated) {
                $query->whereBetween('start_time', [$validated['start_time'], $validated['end_time']])
                      ->orWhereBetween('end_time', [$validated['start_time'], $validated['end_time']])
                      ->orWhere(function($q) use ($validated) {
                          $q->where('start_time', '<=', $validated['start_time'])
                            ->where('end_time', '>=', $validated['end_time']);
                      });
            })
            ->exists();

        if ($conflictExists) {
            return redirect()->back()->withErrors(['time_conflict' => 'You already have a schedule at this time.']);
        }

        Schedule::create($validated);

        return redirect()->route('instructor.schedules.index')->with('success', 'Schedule created successfully.');
    }

    public function update(Request $request, $id)
    {
        $instructor = $this->getCurrentInstructor();

        $schedule = Schedule::whereHas('class', function($query) use ($instructor) {
            $query->where('instructor_id', $instructor->id);
        })->findOrFail($id);

        // Check if schedule can be edited (no approved reschedules and future date)
        if ($schedule->reschedules()->where('status', 'approved')->exists() ||
            Carbon::parse($schedule->date)->isPast()) {
            return redirect()->back()->withErrors(['edit_error' => 'This schedule cannot be edited.']);
        }

        $validated = $request->validate([
            'class_id' => 'required|exists:classes,id',
            'title' => 'required|string|max:255',
            'date' => 'required|date|after_or_equal:today',
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i|after:start_time',
            'notes' => 'nullable|string|max:1000',
        ]);

        // Pastikan class milik instructor
        $class = Classes::where('id', $validated['class_id'])
            ->where('instructor_id', $instructor->id)
            ->firstOrFail();

        // Check for time conflicts (excluding current schedule)
        $conflictExists = Schedule::whereHas('class', function($query) use ($instructor) {
                $query->where('instructor_id', $instructor->id);
            })
            ->where('id', '!=', $id)
            ->where('date', $validated['date'])
            ->where(function($query) use ($validated) {
                $query->whereBetween('start_time', [$validated['start_time'], $validated['end_time']])
                      ->orWhereBetween('end_time', [$validated['start_time'], $validated['end_time']])
                      ->orWhere(function($q) use ($validated) {
                          $q->where('start_time', '<=', $validated['start_time'])
                            ->where('end_time', '>=', $validated['end_time']);
                      });
            })
            ->exists();

        if ($conflictExists) {
            return redirect()->back()->withErrors(['time_conflict' => 'You already have a schedule at this time.']);
        }

        $schedule->update($validated);

        return redirect()->route('instructor.schedules.index')->with('success', 'Schedule updated successfully.');
    }

    public function destroy($id)
    {
        $instructor = $this->getCurrentInstructor();

        $schedule = Schedule::whereHas('class', function($query) use ($instructor) {
            $query->where('instructor_id', $instructor->id);
        })->findOrFail($id);

        // Check if schedule can be deleted (no approved reschedules and future date)
        if ($schedule->reschedules()->where('status', 'approved')->exists() ||
            Carbon::parse($schedule->date)->isPast()) {
            return redirect()->back()->withErrors(['delete_error' => 'This schedule cannot be deleted.']);
        }

        $schedule->delete();

        return redirect()->route('instructor.schedules.index')->with('success', 'Schedule deleted successfully.');
    }

    public function requestReschedule(Request $request, $id)
    {
        $instructor = $this->getCurrentInstructor();

        $schedule = Schedule::whereHas('class', function($query) use ($instructor) {
            $query->where('instructor_id', $instructor->id);
        })->findOrFail($id);

        // Check if schedule can be rescheduled (future date only)
        if (Carbon::parse($schedule->date)->isPast()) {
            return redirect()->back()->withErrors(['reschedule_error' => 'Cannot reschedule past schedules.']);
        }

        // Check if there's already a pending reschedule request
        if ($schedule->reschedules()->where('status', 'pending')->exists()) {
            return redirect()->back()->withErrors(['reschedule_error' => 'There is already a pending reschedule request for this schedule.']);
        }

        $validated = $request->validate([
            'new_date' => 'required|date|after_or_equal:today',
            'new_start_time' => 'required|date_format:H:i',
            'new_end_time' => 'required|date_format:H:i|after:new_start_time',
            'reason' => 'required|string|max:500',
        ]);

        // Check for time conflicts on new date/time
        $conflictExists = Schedule::whereHas('class', function($query) use ($instructor) {
                $query->where('instructor_id', $instructor->id);
            })
            ->where('id', '!=', $id)
            ->where('date', $validated['new_date'])
            ->where(function($query) use ($validated) {
                $query->whereBetween('start_time', [$validated['new_start_time'], $validated['new_end_time']])
                      ->orWhereBetween('end_time', [$validated['new_start_time'], $validated['new_end_time']])
                      ->orWhere(function($q) use ($validated) {
                          $q->where('start_time', '<=', $validated['new_start_time'])
                            ->where('end_time', '>=', $validated['new_end_time']);
                      });
            })
            ->exists();

        if ($conflictExists) {
            return redirect()->back()->withErrors(['time_conflict' => 'You already have a schedule at the requested new time.']);
        }

        Reschedule::create([
            'schedule_id' => $schedule->id,
            'instructor_id' => $instructor->id,
            'new_date' => $validated['new_date'],
            'new_start_time' => $validated['new_start_time'],
            'new_end_time' => $validated['new_end_time'],
            'reason' => $validated['reason'],
            'status' => 'pending'
        ]);

        return redirect()->route('instructor.schedules.index')->with('success', 'Reschedule request submitted successfully. Waiting for admin approval.');
    }

    public function cancelReschedule($scheduleId)
    {
        $instructor = $this->getCurrentInstructor();

        $reschedule = Reschedule::whereHas('schedule.class', function($query) use ($instructor) {
            $query->where('instructor_id', $instructor->id);
        })
            ->where('schedule_id', $scheduleId)
            ->where('status', 'pending')
            ->firstOrFail();

        $reschedule->delete();

        return redirect()->route('instructor.schedules.index')->with('success', 'Reschedule request cancelled successfully.');
    }

    public function show($id)
    {
        $instructor = $this->getCurrentInstructor();

        $schedule = Schedule::whereHas('class', function ($query) use ($instructor) {
            $query->where('instructor_id', $instructor->id);
        })
            ->with([
                'class.category',
                'class.type',
                'reschedules' => function ($query) {
                    $query->orderBy('created_at', 'desc');
                }
            ])
            ->findOrFail($id);

        return response()->json([
            'schedule' => $schedule,
            'reschedule_history' => $schedule->reschedules
        ]);
    }
}
