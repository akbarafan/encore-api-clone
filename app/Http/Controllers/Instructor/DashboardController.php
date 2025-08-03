<?php

namespace App\Http\Controllers\Instructor;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Instructor;
use App\Models\Classes;
use App\Models\Student;
use App\Models\Schedule;
use App\Models\InstructorLogHour;

use App\Models\MessageClass;
use App\Models\Material;
use App\Helpers\InstructorHelper;
use Carbon\Carbon;

class DashboardController extends Controller
{
    private function getCurrentInstructor()
    {
        // Use consistent dummy instructor (no authentication)
        return InstructorHelper::getCurrentInstructorRecord();
    }

    public function index()
    {
        $instructor = $this->getCurrentInstructor();

        // === QUICK STATS ===
        $stats = $this->getQuickStats($instructor);

        // === TODAY'S SCHEDULE ===
        $todaySchedules = $this->getTodaySchedules($instructor);

        // === UPCOMING CLASSES ===
        $upcomingClasses = $this->getUpcomingClasses($instructor);

        // === RECENT ACTIVITIES ===
        $recentActivities = $this->getRecentActivities($instructor);

        // === PERFORMANCE METRICS ===
        $performanceMetrics = $this->getPerformanceMetrics($instructor);

        // === PENDING ITEMS ===
        $pendingItems = $this->getPendingItems($instructor);

        // === WEEKLY OVERVIEW ===
        $weeklyOverview = $this->getWeeklyOverview($instructor);

        return view('instructor.dashboard.index', compact(
            'instructor',
            'stats',
            'todaySchedules',
            'upcomingClasses',
            'recentActivities',
            'performanceMetrics',
            'pendingItems',
            'weeklyOverview'
        ));
    }

    private function getQuickStats($instructor)
    {
        // Total classes (approved only)
        $totalClasses = Classes::where('instructor_id', $instructor->id)
            ->where('is_approved', true)
            ->count();

        // Total students across all classes
        $totalStudents = Student::whereHas('classes', function($query) use ($instructor) {
            $query->where('instructor_id', $instructor->id)
                  ->where('is_approved', true);
        })->count();

        // Today's classes
        $todayClasses = Classes::where('instructor_id', $instructor->id)
            ->where('is_approved', true)
            ->whereHas('schedules', function($query) {
                $query->whereDate('date', today());
            })
            ->count();

        // This month's hours logged
        $thisMonthHours = InstructorLogHour::where('instructor_id', $instructor->id)
            ->whereMonth('date', now()->month)
            ->whereYear('date', now()->year)
            ->whereNotNull('clock_in')
            ->whereNotNull('clock_out')
            ->get()
            ->sum(function($log) {
                return Carbon::parse($log->clock_out)->diffInHours(Carbon::parse($log->clock_in));
            });

        // This month's earnings (estimated)
        $thisMonthEarnings = $thisMonthHours * ($instructor->payrate ?? 50);

        // Pending classes (waiting approval)
        $pendingClasses = Classes::where('instructor_id', $instructor->id)
            ->where('is_approved', false)
            ->count();

        // Upcoming sick/time off days
        $upcomingSickDays = InstructorLogHour::where('instructor_id', $instructor->id)
            ->whereIn('activity_type', ['sick', 'time_off'])
            ->where('date', '>', today())
            ->count();

        // This week's classes
        $thisWeekClasses = Classes::where('instructor_id', $instructor->id)
            ->where('is_approved', true)
            ->whereHas('schedules', function($query) {
                $query->whereBetween('date', [now()->startOfWeek(), now()->endOfWeek()]);
            })
            ->count();

        // Unread messages count
        $unreadMessages = MessageClass::whereHas('class', function($query) use ($instructor) {
            $query->where('instructor_id', $instructor->id);
        })
        ->where('user_id', '!=', $instructor->user_id)
        ->where('is_read', false)
        ->count();

        // Total activities created this month
        $thisMonthActivities = Material::where('instructor_id', $instructor->id)
            ->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->count();

        // Pending activities (upcoming)
        $upcomingActivities = Material::where('instructor_id', $instructor->id)
            ->where('available_from', '>', now())
            ->where('is_active', true)
            ->count();

        return [
            'total_classes' => $totalClasses,
            'total_students' => $totalStudents,
            'today_classes' => $todayClasses,
            'this_month_hours' => $thisMonthHours,
            'this_month_earnings' => $thisMonthEarnings,
            'pending_classes' => $pendingClasses,
            'upcoming_sick_days' => $upcomingSickDays,
            'this_week_classes' => $thisWeekClasses,
            'unread_messages' => $unreadMessages,
            'this_month_activities' => $thisMonthActivities,
            'upcoming_activities' => $upcomingActivities,
        ];
    }

    private function getTodaySchedules($instructor)
    {
        return Schedule::whereHas('class', function($query) use ($instructor) {
                $query->where('instructor_id', $instructor->id)
                      ->where('is_approved', true);
            })
            ->whereDate('date', today())
            ->with(['class.category', 'class.type'])
            ->orderBy('start_time', 'asc')
            ->get();
    }

    private function getUpcomingClasses($instructor)
    {
        return Schedule::whereHas('class', function($query) use ($instructor) {
                $query->where('instructor_id', $instructor->id)
                      ->where('is_approved', true);
            })
            ->where('date', '>', today())
            ->with(['class.category', 'class.type'])
            ->orderBy('date', 'asc')
            ->orderBy('start_time', 'asc')
            ->take(5)
            ->get();
    }

    private function getRecentActivities($instructor)
    {
        $activities = collect();

        // Recent log hours
        $recentLogs = InstructorLogHour::where('instructor_id', $instructor->id)
            ->whereNotNull('clock_out')
            ->orderBy('date', 'desc')
            ->take(3)
            ->get()
            ->map(function($log) {
                return [
                    'type' => 'log_hour',
                    'title' => 'Logged Hours',
                    'description' => 'Worked ' . Carbon::parse($log->clock_out)->diffInHours(Carbon::parse($log->clock_in)) . ' hours',
                    'date' => $log->date,
                    'icon' => 'fas fa-clock',
                    'color' => 'success'
                ];
            });

        // Recent sick/time off logs
        $recentSickLogs = InstructorLogHour::where('instructor_id', $instructor->id)
            ->whereIn('activity_type', ['sick', 'time_off'])
            ->orderBy('created_at', 'desc')
            ->take(2)
            ->get()
            ->map(function($logHour) {
                return [
                    'type' => 'sick_time_off',
                    'title' => ucfirst($logHour->activity_type) . ' Request',
                    'description' => $logHour->getActivityTypeLabel() . ' on ' . $logHour->date->format('M d'),
                    'date' => $logHour->created_at,
                    'icon' => 'fas fa-calendar-times',
                    'color' => 'warning'
                ];
            });

        // Recent classes created
        $recentClasses = Classes::where('instructor_id', $instructor->id)
            ->orderBy('created_at', 'desc')
            ->take(2)
            ->get()
            ->map(function($class) {
                return [
                    'type' => 'class',
                    'title' => 'New Class Created',
                    'description' => $class->name . ' (' . ($class->is_approved ? 'Approved' : 'Pending') . ')',
                    'date' => $class->created_at,
                    'icon' => 'fas fa-chalkboard-teacher',
                    'color' => $class->is_approved ? 'primary' : 'secondary'
                ];
            });

        return $activities->merge($recentLogs)
            ->merge($recentSickLogs)
            ->merge($recentClasses)
            ->sortByDesc('date')
            ->take(6)
            ->values();
    }

    private function getPerformanceMetrics($instructor)
    {
        // Classes completion rate (dummy calculation)
        $totalScheduled = Schedule::whereHas('class', function($query) use ($instructor) {
            $query->where('instructor_id', $instructor->id);
        })->where('date', '<', today())->count();

        $completedClasses = $totalScheduled; // Assume all completed for now
        $completionRate = $totalScheduled > 0 ? ($completedClasses / $totalScheduled) * 100 : 0;

        // Average hours per week
        $weeklyHours = InstructorLogHour::where('instructor_id', $instructor->id)
            ->whereBetween('date', [now()->subWeeks(4), now()])
            ->whereNotNull('clock_in')
            ->whereNotNull('clock_out')
            ->get()
            ->sum(function($log) {
                return Carbon::parse($log->clock_out)->diffInHours(Carbon::parse($log->clock_in));
            });

        $avgWeeklyHours = $weeklyHours / 4;

        // Student satisfaction (dummy - would come from feedback system)
        $studentSatisfaction = 4.5; // out of 5

        // Punctuality rate (dummy calculation)
        $punctualityRate = 95; // percentage

        return [
            'completion_rate' => round($completionRate, 1),
            'avg_weekly_hours' => round($avgWeeklyHours, 1),
            'student_satisfaction' => $studentSatisfaction,
            'punctuality_rate' => $punctualityRate,
        ];
    }

    private function getPendingItems($instructor)
    {
        $pending = [];

        // Pending class approvals
        $pendingClasses = Classes::where('instructor_id', $instructor->id)
            ->where('is_approved', false)
            ->count();

        if ($pendingClasses > 0) {
            $pending[] = [
                'type' => 'class_approval',
                'title' => 'Classes Awaiting Approval',
                'count' => $pendingClasses,
                'description' => $pendingClasses . ' class(es) pending admin approval',
                'action_url' => route('instructor.classes.index'),
                'action_text' => 'View Classes',
                'icon' => 'fas fa-clock',
                'color' => 'warning'
            ];
        }

        // Incomplete time logs (clocked in but not out)
        $incompleteLog = InstructorLogHour::where('instructor_id', $instructor->id)
            ->whereNotNull('clock_in')
            ->whereNull('clock_out')
            ->whereDate('date', '<', today())
            ->count();

        if ($incompleteLog > 0) {
            $pending[] = [
                'type' => 'incomplete_log',
                'title' => 'Incomplete Time Logs',
                'count' => $incompleteLog,
                'description' => 'You have incomplete clock-out entries',
                'action_url' => route('instructor.log-hours.index'),
                'action_text' => 'Complete Logs',
                'icon' => 'fas fa-exclamation-triangle',
                'color' => 'danger'
            ];
        }

        // Upcoming sick/time off requiring attention (that affect schedules)
        $upcomingSickDays = InstructorLogHour::where('instructor_id', $instructor->id)
            ->whereIn('activity_type', ['sick', 'time_off'])
            ->where('date', '>', today())
            ->where('date', '<=', today()->addDays(7))
            ->whereHas('scheduleDisruptions', function($query) {
                $query->where('status', 'pending');
            })
            ->count();

        if ($upcomingSickDays > 0) {
            $pending[] = [
                'type' => 'absence_replacement',
                'title' => 'Absences Need Replacement',
                'count' => $upcomingSickDays,
                'description' => 'Upcoming absences requiring replacement instructor',
                'action_url' => route('instructor.absences.index'),
                'action_text' => 'View Absences',
                'icon' => 'fas fa-user-times',
                'color' => 'info'
            ];
        }

        return $pending;
    }

    private function getWeeklyOverview($instructor)
    {
        $weekDays = [];
        $startOfWeek = now()->startOfWeek();

        for ($i = 0; $i < 7; $i++) {
            $date = $startOfWeek->copy()->addDays($i);

            $classCount = Schedule::whereHas('class', function($query) use ($instructor) {
                $query->where('instructor_id', $instructor->id)
                      ->where('is_approved', true);
            })
            ->whereDate('date', $date)
            ->count();

            $hasSickTimeOff = InstructorLogHour::where('instructor_id', $instructor->id)
                ->whereIn('activity_type', ['sick', 'time_off'])
                ->whereDate('date', $date)
                ->exists();

            $loggedHours = InstructorLogHour::where('instructor_id', $instructor->id)
                ->whereDate('date', $date)
                ->whereNotNull('clock_in')
                ->whereNotNull('clock_out')
                ->get()
                ->sum(function($log) {
                    return Carbon::parse($log->clock_out)->diffInHours(Carbon::parse($log->clock_in));
                });

            $weekDays[] = [
                'date' => $date,
                'day_name' => $date->format('D'),
                'day_number' => $date->format('j'),
                'is_today' => $date->isToday(),
                'is_past' => $date->isPast(),
                'class_count' => $classCount,
                'has_sick_time_off' => $hasSickTimeOff,
                'logged_hours' => $loggedHours,
            ];
        }

        return $weekDays;
    }

    public function getChartData(Request $request)
    {
        $instructor = $this->getCurrentInstructor();
        $type = $request->get('type', 'hours');

        switch ($type) {
            case 'hours':
                return $this->getHoursChartData($instructor);
            case 'classes':
                return $this->getClassesChartData($instructor);
            case 'earnings':
                return $this->getEarningsChartData($instructor);
            default:
                return response()->json(['error' => 'Invalid chart type'], 400);
        }
    }

    private function getHoursChartData($instructor)
    {
        $data = [];
        $labels = [];

        for ($i = 6; $i >= 0; $i--) {
            $date = now()->subDays($i);
            $labels[] = $date->format('M j');

            $hours = InstructorLogHour::where('instructor_id', $instructor->id)
                ->whereDate('date', $date)
                ->whereNotNull('clock_in')
                ->whereNotNull('clock_out')
                ->get()
                ->sum(function($log) {
                    return Carbon::parse($log->clock_out)->diffInHours(Carbon::parse($log->clock_in));
                });

            $data[] = $hours;
        }

        return response()->json([
            'labels' => $labels,
            'data' => $data,
            'title' => 'Hours Logged (Last 7 Days)'
        ]);
    }

    private function getClassesChartData($instructor)
    {
        $data = [];
        $labels = [];

        for ($i = 6; $i >= 0; $i--) {
            $date = now()->subDays($i);
            $labels[] = $date->format('M j');

            $classes = Schedule::whereHas('class', function ($query) use ($instructor) {
                $query->where('instructor_id', $instructor->id)
                    ->where('is_approved', true);
            })
                ->whereDate('date', $date)
                ->count();

            $data[] = $classes;
        }

        return response()->json([
            'labels' => $labels,
            'data' => $data,
            'title' => 'Classes Taught (Last 7 Days)'
        ]);
    }

    private function getEarningsChartData($instructor)
    {
        $data = [];
        $labels = [];
        $payrate = $instructor->payrate ?? 50;

        for ($i = 6; $i >= 0; $i--) {
            $date = now()->subDays($i);
            $labels[] = $date->format('M j');

            $hours = InstructorLogHour::where('instructor_id', $instructor->id)
                ->whereDate('date', $date)
                ->whereNotNull('clock_in')
                ->whereNotNull('clock_out')
                ->get()
                ->sum(function ($log) {
                    return Carbon::parse($log->clock_out)->diffInHours(Carbon::parse($log->clock_in));
                });

            $earnings = $hours * $payrate;
            $data[] = $earnings;
        }

        return response()->json([
            'labels' => $labels,
            'data' => $data,
            'title' => 'Daily Earnings (Last 7 Days)'
        ]);
    }
}
