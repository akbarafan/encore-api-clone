<?php

namespace App\Http\Controllers\Instructor;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Instructor;
use App\Models\Timesheet;
use App\Models\InstructorLogHour;
use Carbon\Carbon;
use App\Helpers\InstructorHelper;

class TimesheetController extends Controller
{
    private function getCurrentInstructor()
    {
        // Use consistent dummy instructor (no authentication)
        return InstructorHelper::getCurrentInstructorRecord();
    }

    public function index(Request $request)
    {
        $instructor = $this->getCurrentInstructor();

        // Query untuk timesheets instructor dengan filter - force fresh data
        $query = Timesheet::where('instructor_id', $instructor->id);

        // Filter by month/year
        if ($request->filled('month')) {
            $query->where('month', $request->month);
        }

        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Filter by year
        if ($request->filled('year')) {
            $query->where('month', 'like', $request->year . '%');
        }

        // Force fresh data from database with timestamp
        $timesheets = $query->orderBy('updated_at', 'desc')->orderBy('month', 'desc')->get();

        // Calculate statistics
        $stats = $this->calculateTimesheetStats($instructor);

        // Get available months and years for filters
        $availableMonths = $this->getAvailableMonths($instructor);
        $availableYears = $this->getAvailableYears($instructor);

        // Get monthly breakdown for current year
        $monthlyBreakdown = $this->getMonthlyBreakdown($instructor);

        return view('instructor.my-timesheets', compact(
            'timesheets',
            'stats',
            'availableMonths',
            'availableYears',
            'instructor',
            'monthlyBreakdown'
        ));
    }

    private function calculateTimesheetStats($instructor)
    {
        $currentYear = date('Y');

        // Total timesheets
        $totalTimesheets = Timesheet::where('instructor_id', $instructor->id)->count();

        // Pending timesheets
        $pendingTimesheets = Timesheet::where('instructor_id', $instructor->id)
            ->where('status', 'pending')
            ->count();

        // Approved timesheets
        $approvedTimesheets = Timesheet::where('instructor_id', $instructor->id)
            ->where('status', 'approved')
            ->count();

        // Total hours this year
        $totalHoursThisYear = Timesheet::where('instructor_id', $instructor->id)
            ->where('month', 'like', $currentYear . '%')
            ->where('status', 'approved')
            ->sum('total_hours');

        // Average monthly hours
        $approvedMonths = Timesheet::where('instructor_id', $instructor->id)
            ->where('month', 'like', $currentYear . '%')
            ->where('status', 'approved')
            ->count();

        $averageMonthlyHours = $approvedMonths > 0 ? round($totalHoursThisYear / $approvedMonths, 2) : 0;

        // Estimated earnings (assuming payrate per hour)
        $payrate = $instructor->payrate ?? 0;
        $estimatedEarnings = $totalHoursThisYear * ($payrate / 100); // Assuming payrate is in cents

        // Completion rate
        $completionRate = $totalTimesheets > 0 ? round(($approvedTimesheets / $totalTimesheets) * 100, 1) : 0;

        return [
            'total' => $totalTimesheets,
            'pending' => $pendingTimesheets,
            'approved' => $approvedTimesheets,
            'rejected' => Timesheet::where('instructor_id', $instructor->id)->where('status', 'rejected')->count(),
            'total_hours_year' => round($totalHoursThisYear, 2),
            'average_monthly' => $averageMonthlyHours,
            'estimated_earnings' => round($estimatedEarnings, 2),
            'completion_rate' => $completionRate
        ];
    }

    private function getAvailableMonths($instructor)
    {
        $months = [];

        // Get last 24 months
        for ($i = 23; $i >= 0; $i--) {
            $date = Carbon::now()->subMonths($i);
            $monthKey = $date->format('Y-m');

            // Check if there are log hours for this month
            $hasLogHours = InstructorLogHour::where('instructor_id', $instructor->id)
                ->whereYear('date', $date->year)
                ->whereMonth('date', $date->month)
                ->exists();

            $months[] = [
                'value' => $monthKey,
                'label' => $date->format('F Y'),
                'has_data' => $hasLogHours
            ];
        }

        return $months;
    }

    private function getAvailableYears($instructor)
    {
        $years = [];
        $currentYear = date('Y');

        for ($year = $currentYear; $year >= $currentYear - 5; $year--) {
            $hasData = Timesheet::where('instructor_id', $instructor->id)
                ->where('month', 'like', $year . '%')
                ->exists();

            $years[] = [
                'value' => $year,
                'label' => $year,
                'has_data' => $hasData
            ];
        }

        return $years;
    }

    private function getMonthlyBreakdown($instructor)
    {
        $currentYear = date('Y');
        $breakdown = [];

        for ($month = 1; $month <= 12; $month++) {
            $monthKey = sprintf('%s-%02d', $currentYear, $month);
            $monthName = Carbon::createFromFormat('Y-m', $monthKey)->format('M');

            // Get timesheet for this month
            $timesheet = Timesheet::where('instructor_id', $instructor->id)
                ->where('month', $monthKey)
                ->first();

            // Calculate hours from log hours
            $logHours = $this->calculateLogHoursForMonth($instructor->id, $monthKey);

            // Check if timesheet can be generated or updated
            $canGenerate = $logHours > 0;
            if ($timesheet && in_array($timesheet->status, ['approved'])) {
                $canGenerate = false; // Cannot update approved timesheets
            }

            $breakdown[] = [
                'month' => $monthName,
                'month_key' => $monthKey,
                'timesheet_hours' => $timesheet ? $timesheet->total_hours : 0,
                'log_hours' => $logHours,
                'status' => $timesheet ? $timesheet->status : 'not_submitted',
                'has_timesheet' => (bool) $timesheet,
                'can_generate' => $canGenerate,
                'can_update' => $timesheet && in_array($timesheet->status, ['pending', 'rejected']) && $logHours > 0,
                'hours_different' => $timesheet && abs($timesheet->total_hours - $logHours) > 0.1
            ];
        }

        return $breakdown;
    }

    private function calculateLogHoursForMonth($instructorId, $month)
    {
        $startDate = Carbon::createFromFormat('Y-m', $month)->startOfMonth();
        $endDate = Carbon::createFromFormat('Y-m', $month)->endOfMonth();

        $logHours = InstructorLogHour::where('instructor_id', $instructorId)
            ->whereBetween('date', [$startDate, $endDate])
            ->whereNotNull('clock_in')
            ->whereNotNull('clock_out')
            ->get();

        return $logHours->sum(function ($log) {
            return Carbon::parse($log->clock_out)->diffInHours(Carbon::parse($log->clock_in), true);
        });
    }





    public function destroy($id)
    {
        $instructor = $this->getCurrentInstructor();

        $timesheet = Timesheet::where('instructor_id', $instructor->id)->findOrFail($id);

        // Only allow delete if status is pending or rejected
        if (!in_array($timesheet->status, ['pending', 'rejected'])) {
            return redirect()->route('instructor.timesheets.index')
                ->with('error', 'Approved timesheets cannot be deleted.');
        }

        $timesheet->delete();

        return redirect()->route('instructor.timesheets.index')
            ->with('success', 'Timesheet has been successfully deleted.');
    }

    public function generateFromLogHours(Request $request)
    {
        $instructor = $this->getCurrentInstructor();

        $validated = $request->validate([
            'month' => 'required|date_format:Y-m',
        ]);

        // Check if timesheet for this month already exists
        $existingTimesheet = Timesheet::where('instructor_id', $instructor->id)
            ->where('month', $validated['month'])
            ->first();

        // Calculate total hours from log hours for the specified month
        $totalHours = $this->calculateLogHoursForMonth($instructor->id, $validated['month']);

        if ($totalHours == 0) {
            return redirect()->route('instructor.timesheets.index')
                ->with('error', 'No log hours found for the selected month.');
        }

        if ($existingTimesheet) {
            // Only allow update if status is pending or rejected
            if (!in_array($existingTimesheet->status, ['pending', 'rejected'])) {
                return redirect()->route('instructor.timesheets.index')
                    ->with('error', 'Cannot update approved timesheet. Please contact administrator if changes are needed.');
            }

            // Update existing timesheet with new log hours
            $oldHours = $existingTimesheet->total_hours;
            $existingTimesheet->update([
                'total_hours' => round($totalHours, 2),
                'status' => 'pending', // Reset to pending when updated
                'updated_at' => now(), // Force timestamp update
            ]);

            // Force refresh by clearing any potential caching
            $existingTimesheet->refresh();

            return redirect()->route('instructor.timesheets.index')
                ->with('success', "Timesheet updated successfully! Hours changed from {$oldHours}h to " . round($totalHours, 2) . "h based on latest log hours.");
        }

        // Create new timesheet if none exists
        Timesheet::create([
            'instructor_id' => $instructor->id,
            'month' => $validated['month'],
            'total_hours' => round($totalHours, 2),
            'status' => 'pending',
        ]);

        return redirect()->route('instructor.timesheets.index')
            ->with('success', "Timesheet generated successfully from log hours. Total: " . round($totalHours, 2) . " hours.");
    }

    public function quickGenerate($month)
    {
        $instructor = $this->getCurrentInstructor();

        // Validate month format
        if (!preg_match('/^\d{4}-\d{2}$/', $month)) {
            return redirect()->route('instructor.timesheets.index')
                ->with('error', 'Invalid month format.');
        }

        // Check if timesheet already exists
        $existingTimesheet = Timesheet::where('instructor_id', $instructor->id)
            ->where('month', $month)
            ->first();

        // Calculate hours
        $totalHours = $this->calculateLogHoursForMonth($instructor->id, $month);

        if ($totalHours == 0) {
            return redirect()->route('instructor.timesheets.index')
                ->with('error', 'No log hours found for this month.');
        }

        $monthName = Carbon::createFromFormat('Y-m', $month)->format('F Y');

        if ($existingTimesheet) {
            // Only allow update if status is pending or rejected
            if (!in_array($existingTimesheet->status, ['pending', 'rejected'])) {
                return redirect()->route('instructor.timesheets.index')
                    ->with('error', 'Cannot update approved timesheet for ' . $monthName . '. Please contact administrator if changes are needed.');
            }

            // Update existing timesheet with new log hours
            $oldHours = $existingTimesheet->total_hours;
            $existingTimesheet->update([
                'total_hours' => round($totalHours, 2),
                'status' => 'pending', // Reset to pending when updated
                'updated_at' => now(), // Force timestamp update
            ]);

            // Force refresh by clearing any potential caching
            $existingTimesheet->refresh();

            return redirect()->route('instructor.timesheets.index')
                ->with('success', "Timesheet for {$monthName} updated successfully! Hours changed from {$oldHours}h to " . round($totalHours, 2) . "h.");
        }

        // Create new timesheet if none exists
        Timesheet::create([
            'instructor_id' => $instructor->id,
            'month' => $month,
            'total_hours' => round($totalHours, 2),
            'status' => 'pending',
        ]);

        return redirect()->route('instructor.timesheets.index')
            ->with('success', "Timesheet for {$monthName} generated successfully. Total: " . round($totalHours, 2) . " hours.");
    }
}
