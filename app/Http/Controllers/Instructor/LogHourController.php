<?php

namespace App\Http\Controllers\Instructor;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Instructor;
use App\Models\InstructorLogHour;
use App\Models\Schedule;
use App\Models\Classes;
use App\Services\ScheduleDisruptionService;
use Carbon\Carbon;
use App\Helpers\InstructorHelper;

class LogHourController extends Controller
{
    private function getCurrentInstructor()
    {
        return InstructorHelper::getCurrentInstructorRecord();
    }

    public function index(Request $request)
    {
        $instructor = $this->getCurrentInstructor();

        // Query untuk log hours instructor dengan filter
        $query = InstructorLogHour::byInstructor($instructor->id);

        // Filter by date range
        if ($request->filled('date_from')) {
            $query->where('date', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->where('date', '<=', $request->date_to);
        }

        // Filter by status
        if ($request->filled('status')) {
            $query->byStatus($request->status);
        }

        // Filter by activity type
        if ($request->filled('activity_type')) {
            $query->byActivityType($request->activity_type);
        }

        // Filter by approval status
        if ($request->filled('approval_status')) {
            $query->byApprovalStatus($request->approval_status);
        }

        $logHours = $query->with(['schedule.class'])
            ->orderBy('date', 'desc')
            ->orderBy('clock_in', 'desc')
            ->get();

        // Calculate statistics
        $stats = $this->calculateStats($instructor);

        // Check if currently working
        $currentlyWorking = InstructorLogHour::byInstructor($instructor->id)
            ->whereNotNull('clock_in')
            ->whereNull('clock_out')
            ->whereDate('date', today())
            ->first();

        // Get today's schedules only for this instructor
        $todaySchedules = Schedule::whereHas('class', function ($query) use ($instructor) {
            $query->where('instructor_id', $instructor->id);
        })
            ->whereDate('date', today()) // Hanya hari ini
            ->with(['class' => function ($query) {
                $query->select('id', 'name', 'class_type_id');
            }])
            ->orderBy('start_time')
            ->get();

        // Get all schedules for calendar (past 30 days to future 30 days)
        $availableSchedules = Schedule::whereHas('class', function ($query) use ($instructor) {
            $query->where('instructor_id', $instructor->id);
        })
            ->whereDate('date', '>=', today()->subDays(30))
            ->whereDate('date', '<=', today()->addDays(30))
            ->with(['class' => function ($query) {
                $query->select('id', 'name', 'class_type_id');
            }])
            ->orderBy('date', 'asc')
            ->orderBy('start_time')
            ->get();

        // Get schedule IDs that are already logged (not soft deleted)
        $usedScheduleIds = InstructorLogHour::byInstructor($instructor->id)
            ->whereNotNull('schedule_id')
            ->whereNull('deleted_at')
            ->pluck('schedule_id')
            ->toArray();

        // Prepare schedule data for JavaScript
        $scheduleDataForJs = $availableSchedules->map(function($schedule) use ($usedScheduleIds) {
            return [
                'id' => $schedule->id,
                'date' => $schedule->date->format('Y-m-d'),
                'title' => $schedule->title,
                'class_name' => $schedule->class->name,
                'start_time' => $schedule->start_time->format('H:i'),
                'end_time' => $schedule->end_time->format('H:i'),
                'is_used' => in_array($schedule->id, $usedScheduleIds),
            ];
        });

        return view('instructor.my-log-hours', compact(
            'logHours',
            'stats',
            'instructor',
            'currentlyWorking',
            'todaySchedules',      // Untuk clock in (hari ini saja)
            'availableSchedules',  // Untuk edit form (30 hari terakhir)
            'scheduleDataForJs'    // Data sederhana untuk JavaScript
        ));
    }

    // ... method lainnya tetap sama
    private function calculateStats($instructor)
    {
        $baseQuery = InstructorLogHour::byInstructor($instructor->id)
            ->whereNotNull('clock_in')
            ->whereNotNull('clock_out');

        // Today's hours
        $todayHours = (clone $baseQuery)
            ->whereDate('date', today())
            ->get()
            ->sum(function ($log) {
                return $this->calculateDuration($log->clock_in, $log->clock_out);
            });

        // This week's hours
        $thisWeekHours = (clone $baseQuery)
            ->whereBetween('date', [now()->startOfWeek(), now()->endOfWeek()])
            ->get()
            ->sum(function ($log) {
                return $this->calculateDuration($log->clock_in, $log->clock_out);
            });

        // This month's hours
        $thisMonthHours = (clone $baseQuery)
            ->whereBetween('date', [now()->startOfMonth(), now()->endOfMonth()])
            ->get()
            ->sum(function ($log) {
                return $this->calculateDuration($log->clock_in, $log->clock_out);
            });

        // Average daily hours (last 30 days)
        $last30DaysHours = (clone $baseQuery)
            ->where('date', '>=', now()->subDays(30))
            ->get()
            ->sum(function ($log) {
                return $this->calculateDuration($log->clock_in, $log->clock_out);
            });

        $workingDays = (clone $baseQuery)
            ->where('date', '>=', now()->subDays(30))
            ->distinct('date')
            ->count('date');

        $averageDailyHours = $workingDays > 0 ? round($last30DaysHours / $workingDays, 2) : 0;

        $workingDaysThisMonth = (clone $baseQuery)
            ->whereBetween('date', [now()->startOfMonth(), now()->endOfMonth()])
            ->distinct('date')
            ->count('date');

        return [
            'today' => round($todayHours, 2),
            'this_week' => round($thisWeekHours, 2),
            'this_month' => round($thisMonthHours, 2),
            'average_daily' => $averageDailyHours,
            'working_days_this_month' => $workingDaysThisMonth
        ];
    }

    private function calculateDuration($clockIn, $clockOut)
    {
        if (!$clockIn || !$clockOut) return 0;
        return Carbon::parse($clockOut)->diffInHours(Carbon::parse($clockIn), true);
    }

    public function store(Request $request)
    {
        $instructor = $this->getCurrentInstructor();

        $validated = $request->validate([
            'date' => 'required|date|before_or_equal:today',
            'clock_in' => 'required|date_format:H:i',
            'clock_out' => 'nullable|date_format:H:i|after:clock_in',
            'schedule_id' => 'nullable|exists:schedules,id',
            'activity_type' => 'required|in:teaching,admin,overtime,time_off,sick',
            'clock_in_notes' => 'nullable|string|max:1000',
            'clock_out_notes' => 'nullable|string|max:1000',
        ]);

        // Check for duplicate entries on the same date
        $existingEntry = InstructorLogHour::byInstructor($instructor->id)
            ->where('date', $validated['date'])
            ->first();

        if ($existingEntry) {
            return redirect()->route('instructor.log-hours.index')
                ->with('error', 'You already have a log entry for this date. Please edit the existing entry.');
        }

        // Check if schedule_id is already used and not soft deleted
        if (!empty($validated['schedule_id'])) {
            $existingScheduleLog = InstructorLogHour::byInstructor($instructor->id)
                ->where('schedule_id', $validated['schedule_id'])
                ->whereNull('deleted_at') // Only check non-deleted entries
                ->first();

            if ($existingScheduleLog) {
                return redirect()->route('instructor.log-hours.index')
                    ->with('error', 'This schedule has already been logged. Please use the existing log entry or delete it first if you need to create a new one.');
            }
        }

        // Convert times to full datetime
        $clockIn = Carbon::parse($validated['date'] . ' ' . $validated['clock_in']);
        $clockOut = null;

        if (!empty($validated['clock_out'])) {
            $clockOut = Carbon::parse($validated['date'] . ' ' . $validated['clock_out']);
        }

        // Check if this will cause a schedule disruption
        $causesDisruption = in_array($validated['activity_type'], ['sick', 'time_off']) && !empty($validated['schedule_id']);

        // Check deadline for sick/time_off requests
        if ($causesDisruption) {
            $schedule = Schedule::find($validated['schedule_id']);
            if ($schedule) {
                $scheduleDateTime = Carbon::parse($schedule->date->format('Y-m-d') . ' ' . $schedule->start_time->format('H:i:s'));
                $hoursUntilSchedule = now()->diffInHours($scheduleDateTime, false);
                
                // If less than 24 hours, add warning to session
                if ($hoursUntilSchedule < 24 && $hoursUntilSchedule > 0) {
                    session()->flash('warning', 
                        "Warning: This schedule is in less than {$hoursUntilSchedule} hours. " .
                        "Late requests may require immediate replacement or result in class cancellation."
                    );
                } elseif ($hoursUntilSchedule <= 0) {
                    return redirect()->route('instructor.log-hours.index')
                        ->with('error', 'Cannot request time off for schedules that have already started or passed.');
                }
            }
        }

        $logHour = InstructorLogHour::create([
            'instructor_id' => $instructor->id,
            'schedule_id' => $validated['schedule_id'] ?? null,
            'date' => $validated['date'],
            'clock_in' => $clockIn,
            'clock_out' => $clockOut,
            'activity_type' => $validated['activity_type'],
            'clock_in_notes' => $validated['clock_in_notes'] ?? null,
            'clock_out_notes' => $validated['clock_out_notes'] ?? null,
            'approval_status' => 'approved', // Auto approved untuk manual entry
            'causes_disruption' => $causesDisruption,
            'disruption_status' => $causesDisruption ? 'pending' : 'none',
        ]);

        // Check if this is a sick/time_off activity that affects a schedule
        if ($causesDisruption) {
            $disruptionService = new ScheduleDisruptionService();
            $disruption = $disruptionService->createDisruptionFromLogHour($logHour);
            
            if ($disruption) {
                // Update disruption status
                $logHour->update(['disruption_status' => 'pending']);
                
                return redirect()->route('instructor.log-hours.index')
                    ->with('success', 'Time entry added successfully. Students have been notified about the schedule change and will vote on the options.');
            }
        }

        return redirect()->route('instructor.log-hours.index')
            ->with('success', 'Time entry has been successfully added.');
    }

    public function update(Request $request, $id)
    {
        $instructor = $this->getCurrentInstructor();

        $logHour = InstructorLogHour::byInstructor($instructor->id)->findOrFail($id);

        $validated = $request->validate([
            'date' => 'required|date|before_or_equal:today',
            'clock_in' => 'required|date_format:H:i',
            'clock_out' => 'nullable|date_format:H:i|after:clock_in',
            'schedule_id' => 'nullable|exists:schedules,id',
            'activity_type' => 'required|in:teaching,admin,overtime,time_off,sick',
            'clock_in_notes' => 'nullable|string|max:1000',
            'clock_out_notes' => 'nullable|string|max:1000',
        ]);

        // Check for duplicate entries on the same date (excluding current entry)
        $existingEntry = InstructorLogHour::byInstructor($instructor->id)
            ->where('date', $validated['date'])
            ->where('id', '!=', $id)
            ->first();

        if ($existingEntry) {
            return redirect()->route('instructor.log-hours.index')
                ->with('error', 'You already have a log entry for this date.');
        }

        // Check if schedule_id is already used and not soft deleted (excluding current entry)
        if (!empty($validated['schedule_id'])) {
            $existingScheduleLog = InstructorLogHour::byInstructor($instructor->id)
                ->where('schedule_id', $validated['schedule_id'])
                ->where('id', '!=', $id) // Exclude current entry
                ->whereNull('deleted_at') // Only check non-deleted entries
                ->first();

            if ($existingScheduleLog) {
                return redirect()->route('instructor.log-hours.index')
                    ->with('error', 'This schedule has already been logged by another entry. Please use the existing log entry or delete it first.');
            }
        }

        // Convert times to full datetime
        $clockIn = Carbon::parse($validated['date'] . ' ' . $validated['clock_in']);
        $clockOut = null;

        if (!empty($validated['clock_out'])) {
            $clockOut = Carbon::parse($validated['date'] . ' ' . $validated['clock_out']);
        }

        // Store old values for comparison
        $oldActivityType = $logHour->activity_type;
        $oldScheduleId = $logHour->schedule_id;

        $logHour->update([
            'date' => $validated['date'],
            'clock_in' => $clockIn,
            'clock_out' => $clockOut,
            'schedule_id' => $validated['schedule_id'] ?? null,
            'activity_type' => $validated['activity_type'],
            'clock_in_notes' => $validated['clock_in_notes'] ?? null,
            'clock_out_notes' => $validated['clock_out_notes'] ?? null,
        ]);

        // Check if activity type changed to sick/time_off and affects a schedule
        if (in_array($logHour->activity_type, ['sick', 'time_off']) && 
            $logHour->schedule_id && 
            ($oldActivityType !== $logHour->activity_type || $oldScheduleId !== $logHour->schedule_id)) {
            
            $disruptionService = new ScheduleDisruptionService();
            $disruption = $disruptionService->createDisruptionFromLogHour($logHour);
            
            if ($disruption) {
                return redirect()->route('instructor.log-hours.index')
                    ->with('success', 'Time entry updated successfully. Students have been notified about the schedule change and will vote on the options.');
            }
        }

        return redirect()->route('instructor.log-hours.index')
            ->with('success', 'Time entry has been successfully updated.');
    }

    public function destroy($id)
    {
        $instructor = $this->getCurrentInstructor();

        $logHour = InstructorLogHour::byInstructor($instructor->id)->findOrFail($id);
        $logHour->delete();

        return redirect()->route('instructor.log-hours.index')
            ->with('success', 'Time entry has been successfully deleted.');
    }

    public function clockIn(Request $request)
    {
        $instructor = $this->getCurrentInstructor();

        $validated = $request->validate([
            'schedule_id' => 'nullable|exists:schedules,id',
            'activity_type' => 'required|in:teaching,admin,overtime,time_off,sick',
            'clock_in_notes' => 'nullable|string|max:1000',
        ]);

        // Check if already clocked in today with same activity type
        $existingLog = InstructorLogHour::byInstructor($instructor->id)
            ->whereDate('date', today())
            ->where('activity_type', $validated['activity_type'])
            ->whereNotNull('clock_in')
            ->whereNull('clock_out')
            ->first();

        if ($existingLog) {
            return redirect()->route('instructor.log-hours.index')
                ->with('error', 'You are already clocked in for ' . $validated['activity_type'] . ' today.');
        }

        // Check if schedule_id is already used and not soft deleted
        if (!empty($validated['schedule_id'])) {
            $existingScheduleLog = InstructorLogHour::byInstructor($instructor->id)
                ->where('schedule_id', $validated['schedule_id'])
                ->whereNull('deleted_at') // Only check non-deleted entries
                ->first();

            if ($existingScheduleLog) {
                return redirect()->route('instructor.log-hours.index')
                    ->with('error', 'This schedule has already been logged. Please use the existing log entry or delete it first if you need to create a new one.');
            }
        }

        // Check deadline for sick/time_off requests
        if (in_array($validated['activity_type'], ['sick', 'time_off']) && !empty($validated['schedule_id'])) {
            $schedule = Schedule::find($validated['schedule_id']);
            if ($schedule) {
                $scheduleDateTime = Carbon::parse($schedule->date->format('Y-m-d') . ' ' . $schedule->start_time->format('H:i:s'));
                $hoursUntilSchedule = now()->diffInHours($scheduleDateTime, false);
                
                // If less than 24 hours, add warning to session
                if ($hoursUntilSchedule < 24 && $hoursUntilSchedule > 0) {
                    session()->flash('warning', 
                        "Warning: This schedule is in less than {$hoursUntilSchedule} hours. " .
                        "Late requests may require immediate replacement or result in class cancellation."
                    );
                } elseif ($hoursUntilSchedule <= 0) {
                    return redirect()->route('instructor.log-hours.index')
                        ->with('error', 'Cannot request time off for schedules that have already started or passed.');
                }
            }
        }

        $logHour = InstructorLogHour::create([
            'instructor_id' => $instructor->id,
            'schedule_id' => $validated['schedule_id'] ?? null,
            'date' => today(),
            'clock_in' => now(),
            'activity_type' => $validated['activity_type'],
            'clock_in_notes' => $validated['clock_in_notes'] ?? null,
            'approval_status' => 'approved', // Auto approved for clock-in
        ]);

        // Check if this is a sick/time_off activity that affects a schedule
        if (in_array($logHour->activity_type, ['sick', 'time_off']) && $logHour->schedule_id) {
            $disruptionService = new ScheduleDisruptionService();
            $disruption = $disruptionService->createDisruptionFromLogHour($logHour);
            
            if ($disruption) {
                return redirect()->route('instructor.log-hours.index')
                    ->with('success', 'Successfully logged ' . $validated['activity_type'] . '. Students have been notified about the schedule change and will vote on the options.');
            }
        }

        return redirect()->route('instructor.log-hours.index')
            ->with('success', 'Successfully clocked in for ' . $validated['activity_type'] . '. Have a productive day!');
    }

    public function clockOut(Request $request)
    {
        $instructor = $this->getCurrentInstructor();

        $validated = $request->validate([
            'log_hour_id' => 'required|exists:instructor_log_hours,id',
            'clock_out_notes' => 'nullable|string|max:1000',
        ]);

        // Find the specific log hour record
        $logHour = InstructorLogHour::byInstructor($instructor->id)
            ->where('id', $validated['log_hour_id'])
            ->whereNotNull('clock_in')
            ->whereNull('clock_out')
            ->first();

        if (!$logHour) {
            return redirect()->route('instructor.log-hours.index')
                ->with('error', 'No active clock-in session found for this entry.');
        }

        $logHour->update([
            'clock_out' => now(),
            'clock_out_notes' => $validated['clock_out_notes'] ?? null,
        ]);

        $duration = $this->calculateDuration($logHour->clock_in, now());

        return redirect()->route('instructor.log-hours.index')
            ->with('success', "Successfully clocked out from " . $logHour->getActivityTypeLabel() . ". You worked for " . round($duration, 2) . " hours.");
    }
}
