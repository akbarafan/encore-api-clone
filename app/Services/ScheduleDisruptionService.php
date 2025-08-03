<?php

namespace App\Services;

use App\Models\InstructorLogHour;
use App\Models\ScheduleDisruption;
use App\Models\Schedule;
use App\Models\AlternativeInstructor;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class ScheduleDisruptionService
{
    /**
     * Create schedule disruption when instructor logs sick/time_off
     */
    public function createDisruptionFromLogHour(InstructorLogHour $logHour)
    {
        // Only create disruptions for sick or time_off activities that have schedules
        if (!in_array($logHour->activity_type, ['sick', 'time_off']) || !$logHour->schedule_id) {
            return null;
        }

        $schedule = $logHour->schedule;
        
        // Check if disruption already exists for this log hour
        $existingDisruption = ScheduleDisruption::where('instructor_log_hour_id', $logHour->id)->first();
        if ($existingDisruption) {
            return $existingDisruption;
        }

        // Get total students for voting
        $totalStudents = $schedule->class->classStudents()->where('status', 'active')->count();

        // Create schedule disruption
        $disruption = ScheduleDisruption::create([
            'schedule_id' => $schedule->id,
            'instructor_log_hour_id' => $logHour->id,
            'reason' => $this->generateDisruptionReason($logHour),
            'status' => 'pending',
            'notes' => $this->generateDisruptionNotes($logHour),
            'response_deadline' => $this->calculateResponseDeadline($schedule),
            'total_students' => $totalStudents,
            'responses_count' => 0,
            'vote_distribution' => [],
        ]);

        // Send notifications to students
        $this->notifyStudents($disruption);

        return $disruption;
    }

    /**
     * Generate simple reason for the disruption
     */
    private function generateDisruptionReason(InstructorLogHour $logHour): string
    {
        $activityLabel = $logHour->getActivityTypeLabel();
        return "Instructor is unable to attend due to {$activityLabel}";
    }

    /**
     * Generate notes for the disruption
     */
    private function generateDisruptionNotes(InstructorLogHour $logHour): string
    {
        $activityLabel = $logHour->getActivityTypeLabel();
        $notes = "Instructor is unable to attend due to {$activityLabel}.";
        
        if ($logHour->clock_in_notes) {
            $notes .= " Additional notes: " . $logHour->clock_in_notes;
        }

        return $notes;
    }

    /**
     * Calculate response deadline (24 hours before class or 48 hours from now, whichever is sooner)
     */
    private function calculateResponseDeadline(Schedule $schedule): Carbon
    {
        // Handle if start_time is already a full datetime or just time
        if (strlen($schedule->start_time) > 8) {
            // It's a full datetime
            $classDateTime = Carbon::parse($schedule->start_time);
        } else {
            // It's just time, combine with date
            $classDateTime = Carbon::parse($schedule->date . ' ' . $schedule->start_time);
        }
        
        $deadline24HoursBefore = $classDateTime->copy()->subHours(24);
        $deadline48HoursFromNow = now()->addHours(48);

        return $deadline24HoursBefore->min($deadline48HoursFromNow);
    }

    /**
     * Send notifications to students about the disruption
     */
    private function notifyStudents(ScheduleDisruption $disruption)
    {
        // Get all students enrolled in this class
        $students = $disruption->schedule->class->classStudents()
            ->where('status', 'active')
            ->with('student.family.user')
            ->get();

        foreach ($students as $classStudent) {
            // Create notification for each student
            // You can implement email/SMS notifications here
            // For now, just log it
            Log::info("Schedule disruption notification sent to student: " . $classStudent->student_id);
        }
    }

    /**
     * Update vote distribution when a student responds
     */
    public function updateVoteDistribution(ScheduleDisruption $disruption)
    {
        $responses = $disruption->studentResponses()->get();
        $voteDistribution = $responses->groupBy('choice')->map->count()->toArray();
        
        $disruption->update([
            'responses_count' => $responses->count(),
            'vote_distribution' => $voteDistribution
        ]);

        // Check if we should process responses automatically
        if ($this->shouldProcessResponses($disruption)) {
            $this->processResponses($disruption);
        }

        return $disruption;
    }

    /**
     * Check if responses should be processed
     */
    private function shouldProcessResponses(ScheduleDisruption $disruption): bool
    {
        return $disruption->hasAllStudentsResponded() || now() >= $disruption->response_deadline;
    }

    /**
     * Process student responses and determine final action
     */
    public function processResponses(ScheduleDisruption $disruption)
    {
        // Check if all students have responded or deadline passed
        if (!$this->shouldProcessResponses($disruption)) {
            return false; // Not ready to process yet
        }

        // Calculate majority choice from student responses
        $responses = $disruption->studentResponses;
        $choiceCounts = $responses->groupBy('choice')->map->count();
        
        // Find the majority choice
        $majorityChoice = $choiceCounts->sortDesc()->keys()->first();
        
        // Execute the majority decision
        $this->executeMajorityDecision($disruption, $majorityChoice, $responses);
        
        $disruption->update(['status' => 'executed']);
        return true;
    }

    /**
     * Execute the majority decision from students
     */
    private function executeMajorityDecision(ScheduleDisruption $disruption, string $choice, $responses)
    {
        // Store the final decision
        $disruption->final_decision = $choice;
        
        switch ($choice) {
            case 'reschedule':
                $this->executeReschedule($disruption, $responses);
                break;
            case 'replace_instructor':
                $this->executeInstructorReplacement($disruption, $responses);
                break;
            case 'cancel':
                $this->executeCancellation($disruption);
                break;
        }
        
        $disruption->save();
    }

    /**
     * Execute schedule reschedule based on student preferences
     */
    private function executeReschedule(ScheduleDisruption $disruption, $responses)
    {
        // Get the most common preferred date/time from reschedule responses
        $rescheduleResponses = $responses->where('choice', 'reschedule');
        
        // Find the most preferred date
        $preferredDate = $rescheduleResponses->where('preferred_date', '!=', null)
                                           ->groupBy('preferred_date')
                                           ->map->count()
                                           ->sortDesc()
                                           ->keys()
                                           ->first() ?? now()->addWeek()->toDateString();
                                           
        // Find the most preferred time
        $preferredStartTime = $rescheduleResponses->where('preferred_start_time', '!=', null)
                                                 ->groupBy('preferred_start_time')
                                                 ->map->count()
                                                 ->sortDesc()
                                                 ->keys()
                                                 ->first() ?? $disruption->schedule->start_time;
                                                 
        $preferredEndTime = $rescheduleResponses->where('preferred_end_time', '!=', null)
                                               ->groupBy('preferred_end_time')
                                               ->map->count()
                                               ->sortDesc()
                                               ->keys()
                                               ->first() ?? $disruption->schedule->end_time;
        
        // Update schedule
        $schedule = $disruption->schedule;
        $schedule->update([
            'date' => $preferredDate,
            'start_time' => $preferredStartTime,
            'end_time' => $preferredEndTime,
        ]);

        // Store final decision details
        $disruption->final_new_date = $preferredDate;
        $disruption->final_new_start_time = $preferredStartTime;
        $disruption->final_new_end_time = $preferredEndTime;

        // Update the instructor log hour date as well
        $disruption->instructorLogHour->update([
            'date' => $preferredDate
        ]);
    }

    /**
     * Execute instructor replacement based on student preferences
     */
    private function executeInstructorReplacement(ScheduleDisruption $disruption, $responses)
    {
        // Get the most preferred replacement instructor
        $replacementResponses = $responses->where('choice', 'replace_instructor');
        
        $preferredInstructorId = $replacementResponses->where('preferred_replacement_instructor_id', '!=', null)
                                                      ->groupBy('preferred_replacement_instructor_id')
                                                      ->map->count()
                                                      ->sortDesc()
                                                      ->keys()
                                                      ->first();
        
        // If no preference, find available alternative instructor
        if (!$preferredInstructorId) {
            $preferredInstructorId = $this->findAvailableAlternativeInstructor($disruption->schedule);
        }
        
        if ($preferredInstructorId) {
            $schedule = $disruption->schedule;
            $class = $schedule->class;
            
            // Update class instructor
            $class->update([
                'instructor_id' => $preferredInstructorId
            ]);

            // Store final decision
            $disruption->final_replacement_instructor_id = $preferredInstructorId;

            // Create new log hour for replacement instructor
            InstructorLogHour::create([
                'instructor_id' => $preferredInstructorId,
                'schedule_id' => $schedule->id,
                'date' => $schedule->date,
                'activity_type' => 'teaching',
                'approval_status' => 'approved'
            ]);
        }
    }
    
    /**
     * Find available alternative instructor
     */
    private function findAvailableAlternativeInstructor(Schedule $schedule): ?int
    {
        $classCategory = $schedule->class->class_category_id;
        
        $replacement = AlternativeInstructor::where('class_category_id', $classCategory)
            ->where('is_active', true)
            ->where('availability_start_date', '<=', $schedule->date)
            ->where('availability_end_date', '>=', $schedule->date)
            ->whereHas('instructor', function($query) use ($schedule) {
                // Make sure replacement instructor doesn't have conflicts
                $query->whereDoesntHave('instructorLogHours', function($subQuery) use ($schedule) {
                    $subQuery->where('date', $schedule->date)
                             ->whereIn('activity_type', ['sick', 'time_off']);
                });
            })
            ->first();

        return $replacement?->instructor_id;
    }

    /**
     * Execute class cancellation
     */
    private function executeCancellation(ScheduleDisruption $disruption)
    {
        $schedule = $disruption->schedule;
        $schedule->delete(); // Soft delete the schedule
    }
}
