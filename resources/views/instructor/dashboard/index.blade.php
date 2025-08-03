@extends('instructor.layouts.app')

@section('title', 'Dashboard')
@section('page-title', 'Dashboard')
@section('page-subtitle', 'Welcome back, ' . $instructor->name)

@section('content')
<!-- Welcome Header -->
<div class="row mb-4">
    <div class="col-12">
        <div class="card border-0 shadow-sm bg-gradient-primary text-white">
            <div class="card-body">
                <div class="row align-items-center">
                    <div class="col-md-8">
                        <h4 class="text-white mb-2">Welcome back, {{ $instructor->name }}! 👋</h4>
                        <p class="text-white-50 mb-0">
                            Here's what's happening with your classes today. You have
                            <strong>{{ $stats['today_classes'] }}</strong> class(es) scheduled.
                        </p>
                    </div>
                    <div class="col-md-4 text-end">
                        <div class="d-flex gap-2 justify-content-end">
                            <button type="button" class="btn btn-light btn-sm" onclick="refreshDashboard()">
                                <i class="fas fa-sync-alt me-1"></i>Refresh
                            </button>
                            <div class="dropdown">
                                <button class="btn btn-light btn-sm dropdown-toggle" type="button" data-bs-toggle="dropdown">
                                    <i class="fas fa-cog me-1"></i>Quick Actions
                                </button>
                                <ul class="dropdown-menu">
                                    <li><a class="dropdown-item" href="{{ route('instructor.schedules.index') }}">
                                        <i class="fas fa-calendar-plus me-2"></i>Create Schedule
                                    </a></li>
                                    <li><a class="dropdown-item" href="{{ route('instructor.chat.index') }}">
                                        <i class="fas fa-comments me-2"></i>Open Class Chat
                                    </a></li>
                                    <li><a class="dropdown-item" href="{{ route('instructor.log-hours.index') }}">
                                        <i class="fas fa-clock me-2"></i>Log Work Hours
                                    </a></li>
                                    <li><hr class="dropdown-divider"></li>
                                    <li><a class="dropdown-item" href="{{ route('instructor.classes.index') }}">
                                        <i class="fas fa-chalkboard-teacher me-2"></i>Manage Classes
                                    </a></li>
                                    <li><a class="dropdown-item" href="{{ route('instructor.students.index') }}">
                                        <i class="fas fa-users me-2"></i>View Students
                                    </a></li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Quick Stats Cards -->
<div class="row mb-4">
    <!-- Row 1: Main Stats -->
    <div class="col-xl-3 col-md-6 mb-3">
        <div class="card border-0 shadow-sm h-100 stat-card">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="flex-shrink-0">
                        <div class="stat-icon bg-primary bg-opacity-10 rounded-3 p-3">
                            <i class="fas fa-chalkboard-teacher text-primary fa-lg"></i>
                        </div>
                    </div>
                    <div class="flex-grow-1 ms-3">
                        <h6 class="text-muted mb-1">Total Classes</h6>
                        <h4 class="mb-0 stat-number">{{ $stats['total_classes'] }}</h4>
                        <small class="text-success">
                            <i class="fas fa-check-circle me-1"></i>Active & Approved
                        </small>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-md-6 mb-3">
        <div class="card border-0 shadow-sm h-100 stat-card">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="flex-shrink-0">
                        <div class="stat-icon bg-success bg-opacity-10 rounded-3 p-3">
                            <i class="fas fa-users text-success fa-lg"></i>
                        </div>
                    </div>
                    <div class="flex-grow-1 ms-3">
                        <h6 class="text-muted mb-1">Total Students</h6>
                        <h4 class="mb-0 stat-number">{{ $stats['total_students'] }}</h4>
                        <small class="text-muted">
                            <i class="fas fa-graduation-cap me-1"></i>Enrolled
                        </small>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-md-6 mb-3">
        <div class="card border-0 shadow-sm h-100 stat-card">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="flex-shrink-0">
                        <div class="stat-icon bg-info bg-opacity-10 rounded-3 p-3">
                            <i class="fas fa-clock text-info fa-lg"></i>
                        </div>
                    </div>
                    <div class="flex-grow-1 ms-3">
                        <h6 class="text-muted mb-1">This Month Hours</h6>
                        <h4 class="mb-0 stat-number">{{ $stats['this_month_hours'] }}h</h4>
                        <small class="text-info">
                            <i class="fas fa-calendar me-1"></i>{{ now()->format('M Y') }}
                        </small>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-md-6 mb-3">
        <div class="card border-0 shadow-sm h-100 stat-card">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="flex-shrink-0">
                        <div class="stat-icon bg-warning bg-opacity-10 rounded-3 p-3">
                            <i class="fas fa-dollar-sign text-warning fa-lg"></i>
                        </div>
                    </div>
                    <div class="flex-grow-1 ms-3">
                        <h6 class="text-muted mb-1">Est. Earnings</h6>
                        <h4 class="mb-0 stat-number">${{ number_format($stats['this_month_earnings'], 0) }}</h4>
                        <small class="text-warning">
                            <i class="fas fa-chart-line me-1"></i>This Month
                        </small>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Secondary Stats Row -->
<div class="row mb-4">
    <div class="col-xl-3 col-md-6 mb-3">
        <div class="card border-0 shadow-sm h-100 stat-card clickable" onclick="window.location.href='{{ route('instructor.chat.index') }}'">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="flex-shrink-0">
                        <div class="stat-icon bg-purple bg-opacity-10 rounded-3 p-3">
                            <i class="fas fa-comments text-purple fa-lg"></i>
                        </div>
                    </div>
                    <div class="flex-grow-1 ms-3">
                        <h6 class="text-muted mb-1">Unread Messages</h6>
                        <h4 class="mb-0 stat-number">{{ $stats['unread_messages'] ?? 0 }}</h4>
                        @if($stats['unread_messages'] > 0)
                            <small class="text-danger">
                                <i class="fas fa-exclamation-circle me-1"></i>Needs Attention
                            </small>
                        @else
                            <small class="text-success">
                                <i class="fas fa-check me-1"></i>All Read
                            </small>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-md-6 mb-3">
        <div class="card border-0 shadow-sm h-100 stat-card clickable" onclick="window.location.href='{{ route('instructor.schedules.index') }}'">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="flex-shrink-0">
                        <div class="stat-icon bg-teal bg-opacity-10 rounded-3 p-3">
                            <i class="fas fa-tasks text-teal fa-lg"></i>
                        </div>
                    </div>
                    <div class="flex-grow-1 ms-3">
                        <h6 class="text-muted mb-1">Activities Created</h6>
                        <h4 class="mb-0 stat-number">{{ $stats['this_month_activities'] ?? 0 }}</h4>
                        <small class="text-muted">
                            <i class="fas fa-calendar me-1"></i>This Month
                        </small>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-md-6 mb-3">
        <div class="card border-0 shadow-sm h-100 stat-card">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="flex-shrink-0">
                        <div class="stat-icon bg-orange bg-opacity-10 rounded-3 p-3">
                            <i class="fas fa-calendar-day text-orange fa-lg"></i>
                        </div>
                    </div>
                    <div class="flex-grow-1 ms-3">
                        <h6 class="text-muted mb-1">Today's Classes</h6>
                        <h4 class="mb-0 stat-number">{{ $stats['today_classes'] }}</h4>
                        <small class="text-muted">
                            <i class="fas fa-clock me-1"></i>{{ now()->format('l') }}
                        </small>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-md-6 mb-3">
        <div class="card border-0 shadow-sm h-100 stat-card">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="flex-shrink-0">
                        <div class="stat-icon bg-indigo bg-opacity-10 rounded-3 p-3">
                            <i class="fas fa-hourglass-half text-indigo fa-lg"></i>
                        </div>
                    </div>
                    <div class="flex-grow-1 ms-3">
                        <h6 class="text-muted mb-1">Upcoming Activities</h6>
                        <h4 class="mb-0 stat-number">{{ $stats['upcoming_activities'] ?? 0 }}</h4>
                        <small class="text-muted">
                            <i class="fas fa-arrow-right me-1"></i>Scheduled
                        </small>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Main Content Row -->
<div class="row">
    <!-- Left Column -->
    <div class="col-lg-8">
        <!-- Today's Schedule -->
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-transparent border-0 pb-0">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="card-title mb-1">Today's Schedule</h5>
                        <p class="text-muted mb-0 small">{{ now()->format('l, F j, Y') }}</p>
                    </div>
                    <div class="badge bg-primary-subtle text-primary">
                        {{ $todaySchedules->count() }} class(es)
                    </div>
                </div>
            </div>
            <div class="card-body pt-2">
                @forelse($todaySchedules as $schedule)
                    <div class="d-flex align-items-center p-3 mb-2 bg-light rounded-3">
                        <div class="flex-shrink-0">
                            <div class="bg-primary rounded-2 p-2 text-white text-center" style="min-width: 60px;">
                                <div class="fw-bold">{{ $schedule->start_time ? \Carbon\Carbon::parse($schedule->start_time)->format('H:i') : 'TBA' }}</div>
                                <small>{{ $schedule->end_time ? \Carbon\Carbon::parse($schedule->end_time)->format('H:i') : '' }}</small>
                            </div>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <h6 class="mb-1">{{ $schedule->class->name }}</h6>
                            <div class="d-flex gap-2 mb-1">
                                <span class="badge bg-light text-dark">{{ $schedule->class->category->name ?? 'N/A' }}</span>
                                <span class="badge bg-light text-dark">{{ $schedule->class->type->name ?? 'N/A' }}</span>
                            </div>
                            <small class="text-muted">
                                <i class="fas fa-map-marker-alt me-1"></i>{{ $schedule->location ?? 'Location TBA' }}
                            </small>
                        </div>
                        <div class="flex-shrink-0">
                            <div class="dropdown">
                                <button class="btn btn-sm btn-outline-primary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                                    <i class="fas fa-ellipsis-v"></i>
                                </button>
                                <ul class="dropdown-menu">
                                    <li><a class="dropdown-item" href="#"><i class="fas fa-eye me-2"></i>View Details</a></li>
                                    <li><a class="dropdown-item" href="#"><i class="fas fa-users me-2"></i>View Students</a></li>
                                    <li><a class="dropdown-item" href="#"><i class="fas fa-edit me-2"></i>Edit Class</a></li>
                                </ul>
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="text-center py-4">
                        <i class="fas fa-calendar-check fa-3x text-muted mb-3"></i>
                        <h6 class="text-muted">No classes scheduled for today</h6>
                        <p class="text-muted mb-3">Enjoy your free day or use this time to prepare for upcoming classes.</p>
                        <a href="{{ route('instructor.classes.index') }}" class="btn btn-outline-primary btn-sm">
                            <i class="fas fa-plus me-1"></i>Schedule New Class
                        </a>
                    </div>
                @endforelse
            </div>
        </div>

        <!-- Performance Chart -->
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-transparent border-0 pb-0">
                <div class="d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">Performance Overview</h5>
                    <div class="btn-group btn-group-sm" role="group">
                        <input type="radio" class="btn-check" name="chartType" id="hoursChart" value="hours" checked>
                        <label class="btn btn-outline-primary" for="hoursChart">Hours</label>

                        <input type="radio" class="btn-check" name="chartType" id="classesChart" value="classes">
                        <label class="btn btn-outline-primary" for="classesChart">Classes</label>

                        <input type="radio" class="btn-check" name="chartType" id="earningsChart" value="earnings">
                        <label class="btn btn-outline-primary" for="earningsChart">Earnings</label>
                    </div>
                </div>
            </div>
            <div class="card-body">
                <canvas id="performanceChart" height="100"></canvas>
            </div>
        </div>

        <!-- Weekly Overview -->
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-transparent border-0 pb-0">
                <h5 class="card-title mb-0">This Week Overview</h5>
                                <p class="text-muted mb-0 small">{{ now()->startOfWeek()->format('M j') }} - {{ now()->endOfWeek()->format('M j, Y') }}</p>
            </div>
            <div class="card-body">
                <div class="row g-2">
                    @foreach($weeklyOverview as $day)
                        <div class="col">
                            <div class="card border {{ $day['is_today'] ? 'border-primary bg-primary bg-opacity-10' : ($day['is_past'] ? 'bg-light' : '') }} text-center">
                                <div class="card-body p-2">
                                    <div class="fw-bold {{ $day['is_today'] ? 'text-primary' : '' }}">{{ $day['day_name'] }}</div>
                                    <div class="h5 mb-1 {{ $day['is_today'] ? 'text-primary' : '' }}">{{ $day['day_number'] }}</div>

                                    @if($day['logged_hours'] > 0)
                                        <div class="small text-muted">{{ $day['logged_hours'] }}h</div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>

    <!-- Right Column -->
    <div class="col-lg-4">
        <!-- Pending Items -->
        @if(count($pendingItems) > 0)
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-transparent border-0 pb-0">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-exclamation-circle text-warning me-2"></i>Needs Attention
                    </h5>
                </div>
                <div class="card-body pt-2">
                    @foreach($pendingItems as $item)
                        <div class="alert alert-{{ $item['color'] }} alert-dismissible fade show border-0" role="alert">
                            <div class="d-flex align-items-start">
                                <div class="flex-shrink-0">
                                    <i class="{{ $item['icon'] }} fa-lg"></i>
                                </div>
                                <div class="flex-grow-1 ms-3">
                                    <h6 class="alert-heading mb-1">{{ $item['title'] }}</h6>
                                    <p class="mb-2">{{ $item['description'] }}</p>
                                    <a href="{{ $item['action_url'] }}" class="btn btn-{{ $item['color'] }} btn-sm">
                                        {{ $item['action_text'] }}
                                    </a>
                                </div>
                                <span class="badge bg-{{ $item['color'] }}-subtle text-{{ $item['color'] }}">
                                    {{ $item['count'] }}
                                </span>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        <!-- Performance Metrics -->
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-transparent border-0 pb-0">
                <h5 class="card-title mb-0">Performance Metrics</h5>
            </div>
            <div class="card-body pt-2">
                <div class="row g-3">
                    <div class="col-6">
                        <div class="text-center">
                            <div class="position-relative d-inline-block">
                                <canvas id="completionChart" width="80" height="80"></canvas>
                                <div class="position-absolute top-50 start-50 translate-middle">
                                    <div class="fw-bold">{{ $performanceMetrics['completion_rate'] }}%</div>
                                </div>
                            </div>
                            <div class="mt-2">
                                <small class="text-muted">Completion Rate</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="text-center">
                            <div class="position-relative d-inline-block">
                                <canvas id="satisfactionChart" width="80" height="80"></canvas>
                                <div class="position-absolute top-50 start-50 translate-middle">
                                    <div class="fw-bold">{{ $performanceMetrics['student_satisfaction'] }}</div>
                                </div>
                            </div>
                            <div class="mt-2">
                                <small class="text-muted">Student Rating</small>
                            </div>
                        </div>
                    </div>
                </div>

                <hr class="my-3">

                <div class="row g-3">
                    <div class="col-12">
                        <div class="d-flex justify-content-between align-items-center">
                            <span class="text-muted">Avg Weekly Hours</span>
                            <span class="fw-bold">{{ $performanceMetrics['avg_weekly_hours'] }}h</span>
                        </div>
                    </div>
                    <div class="col-12">
                        <div class="d-flex justify-content-between align-items-center">
                            <span class="text-muted">Punctuality Rate</span>
                            <span class="fw-bold text-success">{{ $performanceMetrics['punctuality_rate'] }}%</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Upcoming Classes -->
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-transparent border-0 pb-0">
                <div class="d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">Upcoming Classes</h5>
                    <a href="{{ route('instructor.classes.index') }}" class="btn btn-sm btn-outline-primary">
                        View All
                    </a>
                </div>
            </div>
            <div class="card-body pt-2">
                @forelse($upcomingClasses->take(4) as $schedule)
                    <div class="d-flex align-items-center p-2 mb-2 border rounded-2">
                        <div class="flex-shrink-0">
                            <div class="bg-light rounded-2 p-2 text-center" style="min-width: 50px;">
                                <div class="fw-bold small">{{ $schedule->date->format('M') }}</div>
                                <div class="fw-bold">{{ $schedule->date->format('j') }}</div>
                            </div>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <h6 class="mb-1 small">{{ Str::limit($schedule->class->name, 20) }}</h6>
                            <div class="small text-muted">
                                <i class="fas fa-clock me-1"></i>
                                {{ $schedule->start_time ? \Carbon\Carbon::parse($schedule->start_time)->format('H:i') : 'TBA' }}
                            </div>
                        </div>
                        <div class="flex-shrink-0">
                            <span class="badge bg-primary-subtle text-primary">
                                {{ $schedule->class->category->name ?? 'N/A' }}
                            </span>
                        </div>
                    </div>
                @empty
                    <div class="text-center py-3">
                        <i class="fas fa-calendar-plus fa-2x text-muted mb-2"></i>
                        <p class="text-muted mb-0 small">No upcoming classes scheduled</p>
                    </div>
                @endforelse
            </div>
        </div>

        <!-- Recent Activities -->
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-transparent border-0 pb-0">
                <h5 class="card-title mb-0">Recent Activities</h5>
            </div>
            <div class="card-body pt-2">
                @forelse($recentActivities as $activity)
                    <div class="d-flex align-items-start mb-3">
                        <div class="flex-shrink-0">
                            <div class="bg-{{ $activity['color'] }} bg-opacity-10 rounded-circle p-2">
                                <i class="{{ $activity['icon'] }} text-{{ $activity['color'] }}"></i>
                            </div>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <h6 class="mb-1 small">{{ $activity['title'] }}</h6>
                            <p class="mb-1 small text-muted">{{ $activity['description'] }}</p>
                            <small class="text-muted">
                                {{ \Carbon\Carbon::parse($activity['date'])->diffForHumans() }}
                            </small>
                        </div>
                    </div>
                @empty
                    <div class="text-center py-3">
                        <i class="fas fa-history fa-2x text-muted mb-2"></i>
                        <p class="text-muted mb-0 small">No recent activities</p>
                    </div>
                @endforelse
            </div>
        </div>
    </div>
</div>

<!-- Floating Action Button -->
<div class="fab-container">
    <div class="fab-main" onclick="toggleFab()">
        <i class="fas fa-plus fab-icon"></i>
    </div>
    <div class="fab-menu" id="fabMenu">
        <div class="fab-item" onclick="window.location.href='{{ route('instructor.chat.index') }}'" title="Open Chat">
            <i class="fas fa-comments"></i>
        </div>
        <div class="fab-item" onclick="$('#quickClockModal').modal('show')" title="Clock In/Out">
            <i class="fas fa-clock"></i>
        </div>
        <div class="fab-item" onclick="window.location.href='{{ route('instructor.schedules.index') }}'" title="Manage Schedules">
            <i class="fas fa-calendar-plus"></i>
        </div>
        <div class="fab-item" onclick="refreshDashboard()" title="Refresh Dashboard">
            <i class="fas fa-sync-alt"></i>
        </div>
    </div>
</div>

<!-- Quick Action Modals -->
<div class="modal fade" id="quickClockModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header border-0 bg-gradient-primary text-white">
                <h5 class="modal-title text-white">Quick Clock In/Out</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="text-center">
                    <div class="mb-4">
                        <div class="clock-display bg-light rounded-3 p-4 mb-3">
                            <i class="fas fa-clock fa-3x text-primary mb-3"></i>
                            <h2 id="currentTime" class="text-primary mb-0"></h2>
                            <p class="text-muted mb-0">{{ now()->format('l, F j, Y') }}</p>
                        </div>
                    </div>
                    <div class="row g-2">
                        <div class="col-6">
                            <button type="button" class="btn btn-success btn-lg w-100" onclick="quickClockIn()">
                                <i class="fas fa-play me-2"></i>Clock In
                            </button>
                        </div>
                        <div class="col-6">
                            <button type="button" class="btn btn-danger btn-lg w-100" onclick="quickClockOut()">
                                <i class="fas fa-stop me-2"></i>Clock Out
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Toast Container -->
<div class="toast-container position-fixed top-0 end-0 p-3" style="z-index: 9999;">
    <!-- Toasts will be dynamically added here -->
</div>

@endsection

@push('styles')
<style>
.bg-gradient-primary {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
}

.card {
    transition: all 0.3s ease;
    border: 1px solid rgba(0,0,0,0.05);
}

.stat-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 8px 25px rgba(0,0,0,0.15) !important;
}

.stat-card.clickable {
    cursor: pointer;
}

.stat-card.clickable:hover {
    transform: translateY(-6px) scale(1.02);
}

.stat-icon {
    transition: transform 0.3s ease;
}

.stat-card:hover .stat-icon {
    transform: scale(1.1);
}

.stat-number {
    font-weight: 700;
    font-size: 1.75rem;
}

.bg-success-subtle {
    background-color: rgba(25, 135, 84, 0.1) !important;
}

.bg-warning-subtle {
    background-color: rgba(255, 193, 7, 0.1) !important;
}

.bg-primary-subtle {
    background-color: rgba(13, 110, 253, 0.1) !important;
}

.bg-info-subtle {
    background-color: rgba(13, 202, 240, 0.1) !important;
}

.bg-danger-subtle {
    background-color: rgba(220, 53, 69, 0.1) !important;
}

.text-success {
    color: #198754 !important;
}

.text-warning {
    color: #ffc107 !important;
}

.text-primary {
    color: #0d6efd !important;
}

.text-info {
    color: #0dcaf0 !important;
}

.text-danger {
    color: #dc3545 !important;
}

.text-purple {
    color: #6f42c1 !important;
}

.text-teal {
    color: #20c997 !important;
}

.text-orange {
    color: #fd7e14 !important;
}

.text-indigo {
    color: #6610f2 !important;
}

.bg-purple {
    background-color: #6f42c1 !important;
}

.bg-teal {
    background-color: #20c997 !important;
}

.bg-orange {
    background-color: #fd7e14 !important;
}

.bg-indigo {
    background-color: #6610f2 !important;
}

.badge {
    font-weight: 500;
    padding: 0.5em 0.75em;
}

.alert {
    border-radius: 0.75rem;
}

.btn-group-sm > .btn, .btn-sm {
    border-radius: 0.375rem;
}

.rounded-3 {
    border-radius: 0.75rem !important;
}

.shadow-sm {
    box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075) !important;
}

#performanceChart {
    max-height: 300px;
}

.position-relative canvas {
    max-width: 80px;
    max-height: 80px;
}

/* Floating Action Button */
.fab-container {
    position: fixed;
    bottom: 30px;
    right: 30px;
    z-index: 1000;
}

.fab-main {
    width: 60px;
    height: 60px;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    box-shadow: 0 4px 20px rgba(0,0,0,0.3);
    transition: all 0.3s ease;
    position: relative;
    z-index: 1001;
}

.fab-main:hover {
    transform: scale(1.1);
    box-shadow: 0 6px 25px rgba(0,0,0,0.4);
}

.fab-icon {
    color: white;
    font-size: 24px;
    transition: transform 0.3s ease;
}

.fab-container.active .fab-icon {
    transform: rotate(45deg);
}

.fab-menu {
    position: absolute;
    bottom: 70px;
    right: 0;
    display: flex;
    flex-direction: column;
    gap: 10px;
    opacity: 0;
    visibility: hidden;
    transform: translateY(20px);
    transition: all 0.3s ease;
}

.fab-container.active .fab-menu {
    opacity: 1;
    visibility: visible;
    transform: translateY(0);
}

.fab-item {
    width: 50px;
    height: 50px;
    background: white;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    box-shadow: 0 2px 15px rgba(0,0,0,0.2);
    transition: all 0.3s ease;
    color: #666;
}

.fab-item:hover {
    background: #f8f9fa;
    transform: scale(1.1);
    color: #333;
}

.clock-display {
    background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
}

/* Animation delays for fab items */
.fab-item:nth-child(1) { transition-delay: 0.1s; }
.fab-item:nth-child(2) { transition-delay: 0.2s; }
.fab-item:nth-child(3) { transition-delay: 0.3s; }
.fab-item:nth-child(4) { transition-delay: 0.4s; }

/* Mobile responsiveness */
@media (max-width: 768px) {
    .fab-container {
        bottom: 20px;
        right: 20px;
    }

    .fab-main {
        width: 50px;
        height: 50px;
    }

    .fab-icon {
        font-size: 20px;
    }

    .fab-item {
        width: 45px;
        height: 45px;
    }
}

/* Enhanced stat card animations */
@keyframes fadeInUp {
    from {
        opacity: 0;
        transform: translateY(30px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.stat-card {
    animation: fadeInUp 0.6s ease forwards;
}

.stat-card:nth-child(1) { animation-delay: 0.1s; }
.stat-card:nth-child(2) { animation-delay: 0.2s; }
.stat-card:nth-child(3) { animation-delay: 0.3s; }
.stat-card:nth-child(4) { animation-delay: 0.4s; }

/* Pulse effect for unread messages */
.stat-card .text-danger {
    animation: pulse 2s infinite;
}

@keyframes pulse {
    0% { opacity: 1; }
    50% { opacity: 0.5; }
    100% { opacity: 1; }
}
</style>
@endpush

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
$(document).ready(function() {
    // Initialize charts
    initializePerformanceChart();
    initializeMetricCharts();

    // Update current time
    updateCurrentTime();
    setInterval(updateCurrentTime, 1000);

    // Chart type switcher
    $('input[name="chartType"]').on('change', function() {
        updatePerformanceChart($(this).val());
    });
});

let performanceChart;

function initializePerformanceChart() {
    const ctx = document.getElementById('performanceChart').getContext('2d');

    performanceChart = new Chart(ctx, {
        type: 'line',
        data: {
            labels: [],
            datasets: [{
                label: 'Hours',
                data: [],
                borderColor: '#667eea',
                backgroundColor: 'rgba(102, 126, 234, 0.1)',
                borderWidth: 3,
                fill: true,
                tension: 0.4,
                pointBackgroundColor: '#667eea',
                pointBorderColor: '#fff',
                pointBorderWidth: 2,
                pointRadius: 6
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: false
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    grid: {
                        color: 'rgba(0,0,0,0.1)'
                    }
                },
                                x: {
                    grid: {
                        color: 'rgba(0,0,0,0.1)'
                    }
                }
            },
            elements: {
                point: {
                    hoverRadius: 8
                }
            }
        }
    });

    // Load initial data
    updatePerformanceChart('hours');
}

function updatePerformanceChart(type) {
    $.get('{{ route("instructor.dashboard.index") }}/chart-data', { type: type })
        .done(function(response) {
            performanceChart.data.labels = response.labels;
            performanceChart.data.datasets[0].data = response.data;
            performanceChart.data.datasets[0].label = response.title;

            // Update colors based on type
            const colors = {
                hours: { border: '#667eea', bg: 'rgba(102, 126, 234, 0.1)' },
                classes: { border: '#28a745', bg: 'rgba(40, 167, 69, 0.1)' },
                earnings: { border: '#ffc107', bg: 'rgba(255, 193, 7, 0.1)' }
            };

            if (colors[type]) {
                performanceChart.data.datasets[0].borderColor = colors[type].border;
                performanceChart.data.datasets[0].backgroundColor = colors[type].bg;
                performanceChart.data.datasets[0].pointBackgroundColor = colors[type].border;
            }

            performanceChart.update();
        })
        .fail(function() {
            console.error('Failed to load chart data');
        });
}

function initializeMetricCharts() {
    // Completion Rate Chart
    const completionCtx = document.getElementById('completionChart').getContext('2d');
    new Chart(completionCtx, {
        type: 'doughnut',
        data: {
            datasets: [{
                data: [{{ $performanceMetrics['completion_rate'] }}, {{ 100 - $performanceMetrics['completion_rate'] }}],
                backgroundColor: ['#28a745', '#e9ecef'],
                borderWidth: 0
            }]
        },
        options: {
            responsive: false,
            maintainAspectRatio: false,
            cutout: '70%',
            plugins: {
                legend: {
                    display: false
                },
                tooltip: {
                    enabled: false
                }
            }
        }
    });

    // Student Satisfaction Chart
    const satisfactionCtx = document.getElementById('satisfactionChart').getContext('2d');
    const satisfactionPercentage = ({{ $performanceMetrics['student_satisfaction'] }} / 5) * 100;
    new Chart(satisfactionCtx, {
        type: 'doughnut',
        data: {
            datasets: [{
                data: [satisfactionPercentage, 100 - satisfactionPercentage],
                backgroundColor: ['#ffc107', '#e9ecef'],
                borderWidth: 0
            }]
        },
        options: {
            responsive: false,
            maintainAspectRatio: false,
            cutout: '70%',
            plugins: {
                legend: {
                    display: false
                },
                tooltip: {
                    enabled: false
                }
            }
        }
    });
}

function updateCurrentTime() {
    const now = new Date();
    const timeString = now.toLocaleTimeString('en-US', {
        hour12: false,
        hour: '2-digit',
        minute: '2-digit',
        second: '2-digit'
    });
    $('#currentTime').text(timeString);
}

function refreshDashboard() {
    // Show loading state
    const refreshBtn = $('button[onclick="refreshDashboard()"]');
    const originalText = refreshBtn.html();
    refreshBtn.html('<i class="fas fa-spinner fa-spin me-1"></i>Refreshing...');
    refreshBtn.prop('disabled', true);

    // Reload page after short delay
    setTimeout(function() {
        location.reload();
    }, 1000);
}

function quickClockIn() {
    $.post('{{ route("instructor.log-hours.clock-in") }}', {
        _token: $('meta[name="csrf-token"]').attr('content')
    })
    .done(function(response) {
        $('#quickClockModal').modal('hide');
        showAlert('success', 'Successfully clocked in!');
        setTimeout(refreshDashboard, 1500);
    })
    .fail(function(xhr) {
        const message = xhr.responseJSON?.message || 'Failed to clock in';
        showAlert('danger', message);
    });
}

function quickClockOut() {
    $.post('{{ route("instructor.log-hours.clock-out") }}', {
        _token: $('meta[name="csrf-token"]').attr('content')
    })
    .done(function(response) {
        $('#quickClockModal').modal('hide');
        showAlert('success', 'Successfully clocked out!');
        setTimeout(refreshDashboard, 1500);
    })
    .fail(function(xhr) {
        const message = xhr.responseJSON?.message || 'Failed to clock out';
        showAlert('danger', message);
    });
}

function showAlert(type, message) {
    showToast(type, message);
}

function showToast(type, message, title = null) {
    const toastId = 'toast-' + Date.now();
    const iconClass = type === 'success' ? 'check-circle' :
                     type === 'danger' ? 'exclamation-circle' :
                     type === 'warning' ? 'exclamation-triangle' : 'info-circle';

    const bgClass = type === 'success' ? 'bg-success' :
                   type === 'danger' ? 'bg-danger' :
                   type === 'warning' ? 'bg-warning' : 'bg-info';

    const toastHtml = `
        <div id="${toastId}" class="toast align-items-center text-white ${bgClass} border-0" role="alert">
            <div class="d-flex">
                <div class="toast-body">
                    <i class="fas fa-${iconClass} me-2"></i>
                    ${title ? `<strong>${title}</strong><br>` : ''}
                    ${message}
                </div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
            </div>
        </div>
    `;

    $('.toast-container').append(toastHtml);

    const toastElement = new bootstrap.Toast(document.getElementById(toastId), {
        autohide: true,
        delay: 5000
    });

    toastElement.show();

    // Remove from DOM after hiding
    document.getElementById(toastId).addEventListener('hidden.bs.toast', function() {
        this.remove();
    });
}

// Floating Action Button functions
let fabOpen = false;

function toggleFab() {
    const fabContainer = document.querySelector('.fab-container');
    fabOpen = !fabOpen;

    if (fabOpen) {
        fabContainer.classList.add('active');
    } else {
        fabContainer.classList.remove('active');
    }
}

// Close FAB when clicking outside
document.addEventListener('click', function(e) {
    const fabContainer = document.querySelector('.fab-container');
    if (!fabContainer.contains(e.target) && fabOpen) {
        toggleFab();
    }
});

// Keyboard shortcuts
$(document).on('keydown', function(e) {
    // Ctrl/Cmd + R = Refresh dashboard
    if ((e.ctrlKey || e.metaKey) && e.key === 'r') {
        e.preventDefault();
        refreshDashboard();
    }

    // Ctrl/Cmd + T = Quick clock modal
    if ((e.ctrlKey || e.metaKey) && e.key === 't') {
        e.preventDefault();
        $('#quickClockModal').modal('show');
    }
});

// Auto-refresh dashboard every 5 minutes
setInterval(function() {
    // Only refresh if user is active (not idle)
    if (document.hasFocus()) {
        updatePerformanceChart($('input[name="chartType"]:checked').val());
    }
}, 300000); // 5 minutes

// Smooth scroll for anchor links
$('a[href^="#"]').on('click', function(e) {
    e.preventDefault();
    const target = $($(this).attr('href'));
    if (target.length) {
        $('html, body').animate({
            scrollTop: target.offset().top - 100
        }, 500);
    }
});

// Initialize tooltips
$('[data-bs-toggle="tooltip"]').tooltip();

// Card hover effects
$('.card').hover(
    function() {
        $(this).addClass('shadow-lg');
    },
    function() {
        $(this).removeClass('shadow-lg');
    }
);

// Performance metrics animation on scroll
function animateCounters() {
    $('.card-body h4').each(function() {
        const $this = $(this);
        const countTo = parseInt($this.text().replace(/[^\d]/g, ''));

        if (countTo && !$this.hasClass('animated')) {
            $this.addClass('animated');
            $({ countNum: 0 }).animate({
                countNum: countTo
            }, {
                duration: 2000,
                easing: 'swing',
                step: function() {
                    const text = $this.text();
                    const newText = text.replace(/\d+/, Math.floor(this.countNum));
                    $this.text(newText);
                },
                complete: function() {
                    const text = $this.text();
                    const finalText = text.replace(/\d+/, countTo);
                    $this.text(finalText);
                }
            });
        }
    });
}

// Trigger counter animation when page loads
$(window).on('load', function() {
    setTimeout(animateCounters, 500);
});

// Real-time clock update in quick clock modal
$('#quickClockModal').on('shown.bs.modal', function() {
    updateCurrentTime();
});

console.log('Dashboard initialized successfully');
</script>
@endpush

