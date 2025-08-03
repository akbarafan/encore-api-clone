@extends('instructor.layouts.app')

@section('title', 'Time Tracking')
@section('page-title', 'Time Tracking')
@section('page-subtitle', 'Track your working hours and productivity')

@section('content')
    <!-- Statistics Cards -->
    <div class="row mb-4">
        <div class="col-xl-3 col-md-6">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0">
                            <div class="bg-primary bg-opacity-10 rounded-3 p-3">
                                <i class="fas fa-clock text-primary fa-lg"></i>
                            </div>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <h6 class="text-muted mb-1">Today's Hours</h6>
                            <h4 class="mb-0">{{ $stats['today'] }}h</h4>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0">
                            <div class="bg-success bg-opacity-10 rounded-3 p-3">
                                <i class="fas fa-calendar-week text-success fa-lg"></i>
                            </div>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <h6 class="text-muted mb-1">This Week</h6>
                            <h4 class="mb-0">{{ $stats['this_week'] }}h</h4>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0">
                            <div class="bg-warning bg-opacity-10 rounded-3 p-3">
                                <i class="fas fa-calendar-alt text-warning fa-lg"></i>
                            </div>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <h6 class="text-muted mb-1">This Month</h6>
                            <h4 class="mb-0">{{ $stats['this_month'] }}h</h4>
                            <small class="text-muted">{{ $stats['working_days_this_month'] }} working days</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0">
                            <div class="bg-info bg-opacity-10 rounded-3 p-3">
                                <i class="fas fa-chart-line text-info fa-lg"></i>
                            </div>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <h6 class="text-muted mb-1">Daily Average</h6>
                            <h4 class="mb-0">{{ $stats['average_daily'] }}h</h4>
                            <small class="text-muted">Last 30 days</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Quick Actions & Status -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h4 class="mb-0">Time Management</h4>
                    <p class="text-muted mb-0">Track and manage your working hours efficiently</p>
                </div>
                <div class="d-flex gap-2">
                    @if ($currentlyWorking)
                        <div class="alert alert-success border-0 shadow-sm mb-0 me-3">
                            <i class="fas fa-play-circle me-2"></i>
                            <strong>Currently Working - {{ $currentlyWorking->getActivityTypeLabel() }}</strong><br>
                            <small>Started at {{ Carbon\Carbon::parse($currentlyWorking->clock_in)->format('H:i') }}</small>
                            @if ($currentlyWorking->schedule_id)
                                <br><small class="text-muted">Schedule ID: {{ $currentlyWorking->schedule_id }}</small>
                            @endif
                        </div>
                        <button type="button" class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#clockOutModal"
                            data-log-id="{{ $currentlyWorking->id }}">
                            <i class="fas fa-stop-circle me-2"></i>Clock Out
                        </button>
                    @else
                        <button type="button" class="btn btn-success" data-bs-toggle="modal"
                            data-bs-target="#clockInModal">
                            <i class="fas fa-play-circle me-2"></i>Clock In
                        </button>
                    @endif
                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#logHourModal">
                        <i class="fas fa-plus me-2"></i>Add Manual Entry
                    </button>
                </div>
            </div>
        </div>
    </div>

    @if (session('success'))
        <div class="alert alert-success alert-dismissible fade show border-0 shadow-sm" role="alert">
            <i class="fas fa-check-circle me-2"></i>{{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if (session('error'))
        <div class="alert alert-danger alert-dismissible fade show border-0 shadow-sm" role="alert">
            <i class="fas fa-exclamation-circle me-2"></i>{{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if (session('warning'))
        <div class="alert alert-warning alert-dismissible fade show border-0 shadow-sm" role="alert">
            <i class="fas fa-exclamation-triangle me-2"></i>{{ session('warning') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if ($errors->any())
        <div class="alert alert-danger alert-dismissible fade show border-0 shadow-sm" role="alert">
            <i class="fas fa-exclamation-circle me-2"></i>
            <ul class="mb-0">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <!-- Filters -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body">
            <form method="GET" action="{{ route('instructor.log-hours.index') }}">
                <div class="row g-3">
                    <div class="col-md-2">
                        <label class="form-label text-muted">Date From</label>
                        <input type="date" class="form-control" name="date_from" value="{{ request('date_from') }}">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label text-muted">Date To</label>
                        <input type="date" class="form-control" name="date_to" value="{{ request('date_to') }}">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label text-muted">Status</label>
                        <select class="form-select" name="status">
                            <option value="">All Status</option>
                            <option value="completed" {{ request('status') == 'completed' ? 'selected' : '' }}>Completed
                            </option>
                            <option value="clocked_in" {{ request('status') == 'clocked_in' ? 'selected' : '' }}>Currently
                                Working</option>
                            <option value="draft" {{ request('status') == 'draft' ? 'selected' : '' }}>Draft</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label text-muted">Activity Type</label>
                        <select class="form-select" name="activity_type">
                            <option value="">All Types</option>
                            <option value="teaching" {{ request('activity_type') == 'teaching' ? 'selected' : '' }}>
                                Teaching</option>
                            <option value="admin" {{ request('activity_type') == 'admin' ? 'selected' : '' }}>Admin Work
                            </option>
                            <option value="time_off" {{ request('activity_type') == 'time_off' ? 'selected' : '' }}>Time
                                Off</option>
                            <option value="sick" {{ request('activity_type') == 'sick' ? 'selected' : '' }}>Sick Leave
                            </option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label text-muted">Approval Status</label>
                        <select class="form-select" name="approval_status">
                            <option value="">All Status</option>
                            <option value="approved" {{ request('approval_status') == 'approved' ? 'selected' : '' }}>
                                Approved</option>
                            <option value="pending" {{ request('approval_status') == 'pending' ? 'selected' : '' }}>
                                Pending</option>
                            <option value="rejected" {{ request('approval_status') == 'rejected' ? 'selected' : '' }}>
                                Rejected</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label text-muted">&nbsp;</label>
                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-outline-primary">
                                <i class="fas fa-filter"></i>
                            </button>
                            <a href="{{ route('instructor.log-hours.index') }}" class="btn btn-outline-secondary">
                                <i class="fas fa-times"></i>
                            </a>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Time Log Table with DataTables -->
    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <div class="table-responsive">
                <table id="logHoursTable" class="table table-hover">
                    <thead class="bg-light">
                        <tr>
                            <th>Date</th>
                            <th>Day</th>
                            <th>Activity</th>
                            <th>Schedule</th>
                            <th>Clock In</th>
                            <th>Clock Out</th>
                            <th>Duration</th>
                            <th>Status</th>
                            <th>Approval</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($logHours as $logHour)
                            <tr>
                                <td>
                                    <div>
                                        <strong>{{ Carbon\Carbon::parse($logHour->date)->format('M d, Y') }}</strong>
                                    </div>
                                </td>
                                <td>
                                    <span class="badge bg-light text-dark">
                                        {{ Carbon\Carbon::parse($logHour->date)->format('l') }}
                                    </span>
                                </td>
                                <td>
                                    @php
                                        $activityColor = match ($logHour->activity_type) {
                                            'teaching' => 'bg-primary',
                                            'admin' => 'bg-secondary',
                                            'time_off' => 'bg-info',
                                            'sick' => 'bg-danger',
                                            default => 'bg-light',
                                        };
                                    @endphp
                                    <span class="badge {{ $activityColor }}">
                                        {{ $logHour->getActivityTypeLabel() }}
                                    </span>
                                </td>
                                <td>
                                    @if ($logHour->schedule_id && $logHour->schedule)
                                        <div class="small">
                                            <div class="fw-semibold">{{ $logHour->schedule->title }}</div>
                                            <div class="text-muted">{{ $logHour->schedule->class->name }}</div>
                                            <div class="text-muted">
                                                {{ Carbon\Carbon::parse($logHour->schedule->date)->format('M d') }}
                                                {{ Carbon\Carbon::parse($logHour->schedule->start_time)->format('H:i') }}
                                            </div>
                                        </div>
                                    @elseif($logHour->schedule_id)
                                        <small class="text-muted">#{{ $logHour->schedule_id }}</small>
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                </td>
                                <td>
                                    @if ($logHour->clock_in)
                                        <div class="small">
                                            <div><i
                                                    class="fas fa-sign-in-alt text-success me-1"></i>{{ Carbon\Carbon::parse($logHour->clock_in)->format('H:i') }}
                                            </div>
                                            @if ($logHour->clock_in_notes)
                                                <div class="text-muted" title="{{ $logHour->clock_in_notes }}">
                                                    <i
                                                        class="fas fa-comment text-info me-1"></i>{{ Str::limit($logHour->clock_in_notes, 20) }}
                                                </div>
                                            @endif
                                        </div>
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                </td>
                                <td>
                                    @if ($logHour->clock_out)
                                        <div class="small">
                                            <div><i
                                                    class="fas fa-sign-out-alt text-danger me-1"></i>{{ Carbon\Carbon::parse($logHour->clock_out)->format('H:i') }}
                                            </div>
                                            @if ($logHour->clock_out_notes)
                                                <div class="text-muted" title="{{ $logHour->clock_out_notes }}">
                                                    <i
                                                        class="fas fa-comment text-info me-1"></i>{{ Str::limit($logHour->clock_out_notes, 20) }}
                                                </div>
                                            @endif
                                        </div>
                                    @else
                                        @if ($logHour->clock_in)
                                            <span class="text-warning small">
                                                <i class="fas fa-clock me-1"></i>Working...
                                            </span>
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    @endif
                                </td>
                                <td>
                                    @if ($logHour->clock_in && $logHour->clock_out)
                                        @php
                                            $duration = Carbon\Carbon::parse($logHour->clock_out)->diffInHours(
                                                Carbon\Carbon::parse($logHour->clock_in),
                                                true,
                                            );
                                            $hours = floor($duration);
                                            $minutes = round(($duration - $hours) * 60);
                                        @endphp
                                        <span class="fw-semibold">{{ $hours }}h {{ $minutes }}m</span>
                                    @elseif($logHour->clock_in)
                                        @php
                                            $duration = now()->diffInHours(
                                                Carbon\Carbon::parse($logHour->clock_in),
                                                true,
                                            );
                                            $hours = floor($duration);
                                            $minutes = round(($duration - $hours) * 60);
                                        @endphp
                                        <span class="text-info">{{ $hours }}h {{ $minutes }}m</span>
                                        <small class="text-muted d-block">ongoing</small>
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                </td>
                                <td>
                                    @if ($logHour->clock_in && $logHour->clock_out)
                                        <span class="badge bg-success-subtle text-success">
                                            <i class="fas fa-check me-1"></i>Completed
                                        </span>
                                    @elseif($logHour->clock_in)
                                        <span class="badge bg-warning-subtle text-warning">
                                            <i class="fas fa-play me-1"></i>Working
                                        </span>
                                    @else
                                        <span class="badge bg-secondary-subtle text-secondary">
                                            <i class="fas fa-edit me-1"></i>Draft
                                        </span>
                                    @endif
                                </td>
                                <td>
                                    @php
                                        $approvalColor = match ($logHour->approval_status) {
                                            'approved' => 'bg-success-subtle text-success',
                                            'pending' => 'bg-warning-subtle text-warning',
                                            'rejected' => 'bg-danger-subtle text-danger',
                                            default => 'bg-secondary-subtle text-secondary',
                                        };
                                    @endphp
                                    <span class="badge {{ $approvalColor }}">
                                        {{ $logHour->getApprovalStatusLabel() }}
                                    </span>
                                </td>
                                <td>
                                    <div class="btn-group" role="group">
                                        <button type="button" class="btn btn-sm btn-outline-primary edit-log-btn"
                                            data-log-id="{{ $logHour->id }}" data-bs-toggle="modal"
                                            data-bs-target="#logHourModal" title="Edit Entry">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button type="button" class="btn btn-sm btn-outline-danger delete-log-btn"
                                            data-log-id="{{ $logHour->id }}" title="Delete Entry">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Modal for Clock In -->
    <div class="modal fade" id="clockInModal" tabindex="-1" aria-labelledby="clockInModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content border-0 shadow">
                <form method="POST" action="{{ route('instructor.log-hours.clock-in') }}">
                    @csrf
                    <div class="modal-header border-0 pb-0">
                        <div>
                            <h5 class="modal-title" id="clockInModalLabel">Clock In</h5>
                            <p class="text-muted mb-0 small">Start tracking your work session</p>
                        </div>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="activity_type" class="form-label">Activity Type <span
                                    class="text-danger">*</span></label>
                            <select class="form-select" id="activity_type" name="activity_type" required>
                                <option value="">Select Activity</option>
                                <option value="teaching">Teaching</option>
                                <option value="admin">Admin Work</option>
                                <option value="time_off">Time Off</option>
                                <option value="sick">Sick Leave</option>
                            </select>
                            <div id="sick_time_off_alert" class="alert alert-warning mt-2" style="display: none;">
                                <i class="fas fa-exclamation-triangle me-2"></i>
                                <strong>Important:</strong> If you select a schedule below, students will be notified and
                                asked to choose whether to reschedule, cancel, or find a replacement instructor for this
                                class.
                            </div>
                            <div id="teaching_info_alert" class="alert alert-info mt-2" style="display: none;">
                                <i class="fas fa-info-circle me-2"></i>
                                <strong>Teaching Activity:</strong> Select a schedule below to link your work session to a specific class. This helps track your teaching hours accurately.
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="schedule_date_picker" class="form-label">Select Schedule Date</label>
                            <div class="calendar-picker-container">
                                <input type="hidden" id="selected_schedule_id" name="schedule_id">
                                <input type="hidden" id="selected_date" name="selected_date">

                                <!-- Calendar Widget -->
                                <div class="calendar-widget border rounded-3 p-3 bg-light">
                                    <div class="calendar-header d-flex justify-content-between align-items-center mb-3">
                                        <button type="button" class="btn btn-sm btn-outline-secondary" id="prevMonth">
                                            <i class="fas fa-chevron-left"></i>
                                        </button>
                                        <h6 class="mb-0" id="currentMonth"></h6>
                                        <button type="button" class="btn btn-sm btn-outline-secondary" id="nextMonth">
                                            <i class="fas fa-chevron-right"></i>
                                        </button>
                                    </div>
                                    <div class="calendar-grid" id="calendarGrid"></div>
                                </div>

                                <!-- Selected Schedule Info -->
                                <div class="selected-schedule-info mt-3 p-3 border rounded-3 bg-white"
                                    id="selectedScheduleInfo" style="display: none;">
                                    <h6 class="mb-2 text-primary">
                                        <i class="fas fa-calendar-check me-2"></i>Selected Schedule
                                    </h6>
                                    <div id="scheduleDetails"></div>
                                </div>

                                <!-- Available Schedules for Selected Date -->
                                <div class="available-schedules mt-3" id="availableSchedules" style="display: none;">
                                    <h6 class="mb-2">Available Schedules for <span id="selectedDateDisplay"></span>:
                                    </h6>
                                    <div class="row g-2" id="scheduleOptions"></div>
                                </div>
                            </div>

                            <div class="form-text text-muted mt-2">
                                <i class="fas fa-info-circle me-1"></i>
                                Click on a date to see available schedules. Dates with schedules are highlighted in blue.
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="clock_in_notes" class="form-label">Notes</label>
                            <textarea class="form-control" id="clock_in_notes" name="clock_in_notes" rows="3"
                                placeholder="What are you working on?"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer border-0 pt-0">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-success" id="clockInSubmitBtn">
                            <i class="fas fa-play-circle me-2"></i>Clock In
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal for Add/Edit Log Hour -->
    <div class="modal fade" id="logHourModal" tabindex="-1" aria-labelledby="logHourModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content border-0 shadow">
                <form id="logHourForm" method="POST" action="{{ route('instructor.log-hours.store') }}">
                    @csrf
                    <input type="hidden" name="_method" value="POST" id="methodField">

                    <div class="modal-header border-0 pb-0">
                        <div>
                            <h5 class="modal-title" id="logHourModalLabel">Add Time Entry</h5>
                            <p class="text-muted mb-0 small">Track your working hours manually</p>
                        </div>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="date" class="form-label">Date <span class="text-danger">*</span></label>
                                <input type="date" class="form-control" id="date" name="date" required
                                    max="{{ date('Y-m-d') }}">
                            </div>
                            <div class="col-md-6">
                                <label for="activity_type_manual" class="form-label">Activity Type <span
                                        class="text-danger">*</span></label>
                                <select class="form-select" id="activity_type_manual" name="activity_type" required>
                                    <option value="">Select Activity</option>
                                    <option value="teaching">Teaching</option>
                                    <option value="admin">Admin Work</option>
                                    <option value="time_off">Time Off</option>
                                    <option value="sick">Sick Leave</option>
                                </select>
                                <div id="sick_time_off_alert_manual" class="alert alert-warning mt-2"
                                    style="display: none;">
                                    <i class="fas fa-exclamation-triangle me-2"></i>
                                    <strong>Important:</strong> If you select a schedule below, students will be notified
                                    and asked to choose whether to reschedule, cancel, or find a replacement instructor for
                                    this class.
                                </div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="schedule_id_manual" class="form-label">Select Schedule (Optional)</label>
                            <div class="calendar-picker-container">
                                <input type="hidden" id="selected_schedule_id_manual" name="schedule_id">
                                <input type="hidden" id="selected_date_manual" name="selected_date_manual">

                                <!-- Calendar Widget for Manual Entry -->
                                <div class="calendar-widget border rounded-3 p-3 bg-light">
                                    <div class="calendar-header d-flex justify-content-between align-items-center mb-3">
                                        <button type="button" class="btn btn-sm btn-outline-secondary"
                                            id="prevMonthManual">
                                            <i class="fas fa-chevron-left"></i>
                                        </button>
                                        <h6 class="mb-0" id="currentMonthManual"></h6>
                                        <button type="button" class="btn btn-sm btn-outline-secondary"
                                            id="nextMonthManual">
                                            <i class="fas fa-chevron-right"></i>
                                        </button>
                                    </div>
                                    <div class="calendar-grid" id="calendarGridManual"></div>
                                </div>

                                <!-- Selected Schedule Info for Manual -->
                                <div class="selected-schedule-info mt-3 p-3 border rounded-3 bg-white"
                                    id="selectedScheduleInfoManual" style="display: none;">
                                    <h6 class="mb-2 text-primary">
                                        <i class="fas fa-calendar-check me-2"></i>Selected Schedule
                                    </h6>
                                    <div id="scheduleDetailsManual"></div>
                                </div>

                                <!-- Available Schedules for Selected Date Manual -->
                                <div class="available-schedules mt-3" id="availableSchedulesManual"
                                    style="display: none;">
                                    <h6 class="mb-2">Available Schedules for <span
                                            id="selectedDateDisplayManual"></span>:</h6>
                                    <div class="row g-2" id="scheduleOptionsManual"></div>
                                </div>
                            </div>

                            <div class="form-text text-muted mt-2">
                                <i class="fas fa-info-circle me-1"></i>
                                Shows schedules from past 30 days to future 30 days. Click on a highlighted date to see
                                schedules.
                            </div>
                        </div>

                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="clock_in" class="form-label">Clock In <span
                                        class="text-danger">*</span></label>
                                <input type="time" class="form-control" id="clock_in" name="clock_in" required>
                            </div>
                            <div class="col-md-6">
                                <label for="clock_out" class="form-label">Clock Out</label>
                                <input type="time" class="form-control" id="clock_out" name="clock_out">
                            </div>
                        </div>

                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="clock_in_notes" class="form-label">Clock In Notes</label>
                                <textarea class="form-control" id="clock_in_notes_manual" name="clock_in_notes" rows="3"
                                    placeholder="What are you working on?"></textarea>
                            </div>
                            <div class="col-md-6">
                                <label for="clock_out_notes" class="form-label">Clock Out Notes</label>
                                <textarea class="form-control" id="clock_out_notes_manual" name="clock_out_notes" rows="3"
                                    placeholder="What did you accomplish?"></textarea>
                            </div>
                        </div>

                        <div class="alert alert-info mt-3">
                            <i class="fas fa-info-circle me-2"></i>
                            <strong>Tips:</strong>
                            <ul class="mb-0 mt-2">
                                <li>For today's entries, select from "Today's Schedules" if available</li>
                                <li>Leave Clock Out empty if you want to continue tracking this session</li>
                                <li>You can have multiple entries per day for different activities</li>
                                <li>Clock Out time must be after Clock In time</li>
                            </ul>
                        </div>
                    </div>

                    <div class="modal-footer border-0 pt-0">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-2"></i>Save Entry
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>


    <!-- Modal for Clock Out -->
    <div class="modal fade" id="clockOutModal" tabindex="-1" aria-labelledby="clockOutModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content border-0 shadow">
                <form method="POST" action="{{ route('instructor.log-hours.clock-out') }}">
                    @csrf
                    <input type="hidden" name="log_hour_id" id="clockOutLogHourId">
                    <div class="modal-header border-0 pb-0">
                        <div>
                            <h5 class="modal-title" id="clockOutModalLabel">Clock Out</h5>
                            <p class="text-muted mb-0 small">Complete your work session</p>
                        </div>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="clock_out_notes" class="form-label">Work Summary</label>
                            <textarea class="form-control" id="clock_out_notes" name="clock_out_notes" rows="3"
                                placeholder="What did you accomplish?"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer border-0 pt-0">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-danger">
                            <i class="fas fa-stop-circle me-2"></i>Clock Out
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>



    <!-- Delete Confirmation Modal -->
    <div class="modal fade" id="deleteModal" tabindex="-1" aria-labelledby="deleteModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content border-0 shadow">
                <div class="modal-header border-0 pb-0">
                    <div>
                        <h5 class="modal-title text-danger" id="deleteModalLabel">
                            <i class="fas fa-exclamation-triangle me-2"></i>Delete Time Entry
                        </h5>
                        <p class="text-muted mb-0 small">This action cannot be undone</p>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0">
                            <div class="bg-danger bg-opacity-10 rounded-3 p-3">
                                <i class="fas fa-trash text-danger fa-lg"></i>
                            </div>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <p class="mb-0">Are you sure you want to delete this time entry? This will permanently remove
                                the record from your timesheet.</p>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <form id="deleteForm" method="POST" style="display: inline;">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-danger">
                            <i class="fas fa-trash me-2"></i>Delete Entry
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('styles')
    <link href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/responsive/2.5.0/css/responsive.bootstrap5.min.css" rel="stylesheet">
    <style>
        .bg-success-subtle {
            background-color: rgba(25, 135, 84, 0.1) !important;
        }

        .bg-warning-subtle {
            background-color: rgba(255, 193, 7, 0.1) !important;
        }

        .bg-danger-subtle {
            background-color: rgba(220, 53, 69, 0.1) !important;
        }

        .bg-secondary-subtle {
            background-color: rgba(108, 117, 125, 0.1) !important;
        }

        .text-success {
            color: #198754 !important;
        }

        .text-warning {
            color: #ffc107 !important;
        }

        .text-secondary {
            color: #6c757d !important;
        }

        .card {
            transition: all 0.3s ease;
        }

        .table-hover tbody tr:hover {
            background-color: rgba(0, 0, 0, 0.02);
        }

        .btn-group .btn {
            border-radius: 0.375rem !important;
            margin-right: 0.25rem;
        }

        .btn-group .btn:last-child {
            margin-right: 0;
        }

        .modal-content {
            border-radius: 1rem;
        }

        .form-control:focus,
        .form-select:focus {
            border-color: #0d6efd;
            box-shadow: 0 0 0 0.2rem rgba(13, 110, 253, 0.25);
        }

        .badge {
            font-weight: 500;
            padding: 0.5em 0.75em;
        }

        .alert {
            border-radius: 0.75rem;
        }

        .dataTables_wrapper .dataTables_length select,
        .dataTables_wrapper .dataTables_filter input {
            border-radius: 0.375rem;
        }

        .dataTables_wrapper .dataTables_paginate .paginate_button.current {
            background: #0d6efd !important;
            border-color: #0d6efd !important;
        }

        /* Calendar Picker Styles */
        .calendar-widget {
            max-width: 100%;
            background: #f8f9fa;
        }

        .calendar-grid {
            display: grid;
            grid-template-columns: repeat(7, 1fr);
            gap: 2px;
            text-align: center;
        }

        .calendar-header-day {
            font-weight: 600;
            font-size: 0.8rem;
            color: #6c757d;
            padding: 8px 4px;
            background: #e9ecef;
            border-radius: 4px;
        }

        .calendar-day {
            aspect-ratio: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 6px;
            cursor: pointer;
            transition: all 0.2s ease;
            font-size: 0.9rem;
            position: relative;
            min-height: 35px;
            background: white;
            border: 1px solid #e9ecef;
        }

        .calendar-day:hover {
            background: #e3f2fd;
            transform: scale(1.05);
        }

        .calendar-day.today {
            background: #007bff;
            color: white;
            font-weight: bold;
        }

        .calendar-day.has-schedule {
            background: #28a745;
            color: white;
            font-weight: 600;
        }

        .calendar-day.has-schedule:hover {
            background: #218838;
        }

        .calendar-day.selected {
            background: #ffc107;
            color: #000;
            font-weight: bold;
            box-shadow: 0 0 0 2px #007bff;
        }

        .calendar-day.other-month {
            color: #ced4da;
            background: #f8f9fa;
        }

        .calendar-day.past-date {
            opacity: 0.6;
        }

        .calendar-day.disabled {
            background: #f8f9fa;
            color: #ced4da;
            cursor: not-allowed;
        }

        .calendar-day.disabled:hover {
            background: #f8f9fa;
            transform: none;
        }

        .schedule-indicator {
            position: absolute;
            bottom: 2px;
            right: 2px;
            width: 6px;
            height: 6px;
            background: #007bff;
            border-radius: 50%;
        }

        .calendar-day.has-schedule .schedule-indicator {
            background: #fff;
        }

        .schedule-option-card {
            cursor: pointer;
            transition: all 0.2s ease;
            border: 2px solid transparent;
        }

        .schedule-option-card:hover {
            border-color: #007bff;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }

        .schedule-option-card.selected {
            border-color: #007bff;
            background: #e3f2fd;
        }

        .schedule-option-card.used-schedule {
            opacity: 0.6;
            cursor: not-allowed;
            background: #f8f9fa;
            border-color: #dee2e6;
        }

        .schedule-option-card.used-schedule:hover {
            border-color: #dee2e6;
            transform: none;
            box-shadow: none;
        }

        .schedule-option-card.used-schedule .card-body {
            background: linear-gradient(45deg, #f8f9fa, #e9ecef);
        }

        .schedule-badge {
            font-size: 0.7rem;
            padding: 0.25rem 0.5rem;
        }

        .deadline-warning {
            background: linear-gradient(45deg, #fff3cd, #f8d7da);
            border-left: 4px solid #dc3545;
        }

        /* Mobile Responsiveness */
        @media (max-width: 768px) {
            /* Statistics Cards */
            .col-xl-3.col-md-6 {
                margin-bottom: 1rem;
            }

            /* Quick Actions */
            .d-flex.gap-2 {
                flex-direction: column;
                gap: 0.5rem !important;
            }

            .d-flex.gap-2 .btn {
                width: 100%;
                font-size: 0.9rem;
            }

            /* Filters */
            .row.g-3 .col-md-2 {
                margin-bottom: 1rem;
            }

            /* Calendar */
            .calendar-grid {
                gap: 1px;
            }

            .calendar-day {
                min-height: 30px;
                font-size: 0.8rem;
            }

            .schedule-indicator {
                width: 4px;
                height: 4px;
            }

            /* Modal */
            .modal-dialog {
                margin: 0.5rem;
            }

            .modal-dialog.modal-lg {
                max-width: calc(100vw - 1rem);
            }

            /* Form responsive */
            .row.g-3 .col-md-6 {
                margin-bottom: 1rem;
            }

            /* Table responsive */
            .table-responsive {
                font-size: 0.85rem;
            }

            .btn-group .btn {
                padding: 0.25rem 0.5rem;
                font-size: 0.8rem;
            }

            /* Schedule options responsive */
            .schedule-option-card .card-body {
                padding: 0.75rem;
            }

            .schedule-option-card h6 {
                font-size: 0.9rem;
            }

            /* Alert responsive */
            .alert {
                font-size: 0.9rem;
                padding: 0.75rem;
            }

            /* Badge responsive */
            .badge {
                font-size: 0.75rem;
                padding: 0.35em 0.65em;
            }

            /* Currently working indicator */
            .alert.mb-0.me-3 {
                margin-bottom: 1rem !important;
                margin-right: 0 !important;
            }
        }

        @media (max-width: 576px) {
            /* Extra small devices */
            .card-body {
                padding: 1rem;
            }

            .h4 {
                font-size: 1.1rem;
            }

            .modal-header h5 {
                font-size: 1rem;
            }

            .table td, .table th {
                padding: 0.5rem 0.25rem;
                font-size: 0.8rem;
            }

            .calendar-header-day {
                font-size: 0.7rem;
                padding: 4px 2px;
            }

            .calendar-day {
                min-height: 25px;
                font-size: 0.7rem;
            }

            /* Hide some less important columns on very small screens */
            .table th:nth-child(2),
            .table td:nth-child(2),
            .table th:nth-child(6),
            .table td:nth-child(6) {
                display: none;
            }
        }
    </style>
@endpush

@push('scripts')
    <script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>
    <script src="https://cdn.datatables.net/responsive/2.5.0/js/dataTables.responsive.min.js"></script>
    <script src="https://cdn.datatables.net/responsive/2.5.0/js/responsive.bootstrap5.min.js"></script>

    <script>
        $(document).ready(function() {
            // Initialize DataTables
            const table = $('#logHoursTable').DataTable({
                responsive: true,
                pageLength: 15,
                lengthMenu: [
                    [15, 25, 50, -1],
                    [15, 25, 50, "All"]
                ],
                order: [
                    [0, 'desc']
                ],
                columnDefs: [{
                        targets: -1,
                        orderable: false,
                        searchable: false
                    },
                    {
                        targets: [0],
                        type: 'date'
                    }
                ],
                language: {
                    search: "Search time entries:",
                    lengthMenu: "Show _MENU_ entries per page",
                    info: "Showing _START_ to _END_ of _TOTAL_ time entries",
                    infoEmpty: "No time entries available",
                    infoFiltered: "(filtered from _MAX_ total entries)",
                    emptyTable: "No time entries found. Start tracking your hours!",
                    zeroRecords: "No matching time entries found"
                }
            });

            // Store log hours data for JavaScript access (similar to classes)
            const logHoursData = {!! json_encode(
                $logHours->map(function ($log) {
                    return [
                        'id' => $log->id,
                        'date' => $log->date ? $log->date->format('Y-m-d') : '',
                        'clock_in' => $log->clock_in ? $log->clock_in->format('H:i') : '',
                        'clock_out' => $log->clock_out ? $log->clock_out->format('H:i') : '',
                        'schedule_id' => $log->schedule_id,
                        'activity_type' => $log->activity_type,
                        'clock_in_notes' => $log->clock_in_notes,
                        'clock_out_notes' => $log->clock_out_notes,
                        'approval_status' => $log->approval_status,
                    ];
                }),
            ) !!};

            let editingLogHourId = null;

            // Note: Activity type change handlers are defined later in the code

            // Clock Out Modal Handler
            $('#clockOutModal').on('show.bs.modal', function(event) {
                var button = $(event.relatedTarget);
                var logId = button.data('log-id');
                $('#clockOutLogHourId').val(logId);
            });

            // Edit log hour button click handler
            $(document).on('click', '.edit-log-btn', function() {
                const logId = $(this).data('log-id');
                editLogHour(logId);
            });

            // Delete log hour button click handler
            $(document).on('click', '.delete-log-btn', function() {
                const logId = $(this).data('log-id');
                deleteLogHour(logId);
            });

            // Edit log hour function (simplified like classes)
            function editLogHour(logId) {
                editingLogHourId = logId;
                const logItem = logHoursData.find(l => l.id == logId);

                if (!logItem) {
                    console.error('Log hour not found');
                    alert('Log hour data not found. Please refresh the page and try again.');
                    return;
                }

                // Update modal title
                $('#logHourModalLabel').text('Edit Time Entry');

                // Update form action and method
                const form = $('#logHourForm');
                form.attr('action', `{{ url('instructor/log-hours') }}/${logId}`);
                $('#methodField').val('PUT');

                // Fill form with current data
                $('#date').val(logItem.date || '');
                $('#clock_in').val(logItem.clock_in || '');
                $('#clock_out').val(logItem.clock_out || '');
                $('#activity_type_manual').val(logItem.activity_type || '');
                $('#schedule_id_manual').val(logItem.schedule_id || '');
                $('#clock_in_notes_manual').val(logItem.clock_in_notes || '');
                $('#clock_out_notes_manual').val(logItem.clock_out_notes || '');
            }

            // Delete log hour function
            function deleteLogHour(logId) {
                $('#deleteForm').attr('action', `{{ url('instructor/log-hours') }}/${logId}`);
                $('#deleteModal').modal('show');
            }

            // Reset modal when closed
            $('#logHourModal').on('hidden.bs.modal', function() {
                editingLogHourId = null;
                $('#logHourModalLabel').text('Add Time Entry');

                const form = $('#logHourForm');
                form.attr('action', '{{ route('instructor.log-hours.store') }}');
                form[0].reset();

                $('#methodField').val('POST');
                $('.is-invalid').removeClass('is-invalid');
                $('.invalid-feedback').remove();
            });

            // Form validation and submission
            $('#logHourForm').on('submit', function(e) {
                e.preventDefault();

                $('.is-invalid').removeClass('is-invalid');
                $('.invalid-feedback').remove();

                const date = $('#date').val();
                const clockIn = $('#clock_in').val();
                const clockOut = $('#clock_out').val();
                const activityType = $('#activity_type_manual').val();

                let isValid = true;

                if (!date) {
                    $('#date').addClass('is-invalid');
                    $('#date').after('<div class="invalid-feedback">Date is required.</div>');
                    isValid = false;
                }

                if (!clockIn) {
                    $('#clock_in').addClass('is-invalid');
                    $('#clock_in').after('<div class="invalid-feedback">Clock in time is required.</div>');
                    isValid = false;
                }

                if (!activityType) {
                    $('#activity_type_manual').addClass('is-invalid');
                    $('#activity_type_manual').after(
                        '<div class="invalid-feedback">Activity type is required.</div>');
                    isValid = false;
                }

                if (clockIn && clockOut && clockOut <= clockIn) {
                    $('#clock_out').addClass('is-invalid');
                    $('#clock_out').after(
                        '<div class="invalid-feedback">Clock out time must be after clock in time.</div>'
                    );
                    isValid = false;
                }

                if (!isValid) {
                    $('.is-invalid').first().focus();
                    return;
                }

                const submitBtn = $(this).find('button[type="submit"]');
                const originalText = submitBtn.html();
                submitBtn.html('<i class="fas fa-spinner fa-spin me-2"></i>Saving...').prop('disabled',
                    true);

                this.submit();
            });

            // Remove validation classes on input
            $('.form-control, .form-select').on('input change', function() {
                $(this).removeClass('is-invalid');
                $(this).next('.invalid-feedback').remove();
            });

            // Auto-dismiss alerts after 5 seconds
            $('.alert').each(function() {
                const alert = $(this);
                setTimeout(function() {
                    alert.fadeOut('slow');
                }, 5000);
            });

            // Clock In form validation
            $('form[action="{{ route("instructor.log-hours.clock-in") }}"]').on('submit', function(e) {
                const activityType = $('#activity_type').val();
                const scheduleId = $('#selected_schedule_id').val();
                
                // For sick/time_off, schedule_id is mandatory
                if ((activityType === 'sick' || activityType === 'time_off') && !scheduleId) {
                    e.preventDefault();
                    alert('Please select a schedule when requesting time off or sick leave.');
                    return false;
                }
                
                // For teaching, schedule_id is optional but recommended
                if (activityType === 'teaching' && scheduleId) {
                    // Allow teaching with schedule_id
                    console.log('Teaching activity with schedule ID:', scheduleId);
                }
                
                return true;
            });

            // Activity type change handler for Clock In modal
            $('#activity_type').on('change', function() {
                const activityType = $(this).val();
                const scheduleContainer = $('.calendar-picker-container').parent();
                
                if (activityType === 'sick' || activityType === 'time_off') {
                    $('#sick_time_off_alert').show();
                    $('#teaching_info_alert').hide();
                    scheduleContainer.show();
                } else if (activityType === 'teaching') {
                    $('#sick_time_off_alert').hide();
                    $('#teaching_info_alert').show();
                    scheduleContainer.show();
                } else {
                    $('#sick_time_off_alert').hide();
                    $('#teaching_info_alert').hide();
                    scheduleContainer.hide();
                    clearScheduleSelection();
                }
            });

            // Activity type change handler for Manual Entry modal
            $('#activity_type_manual').on('change', function() {
                const activityType = $(this).val();
                const scheduleContainerManual = $('#schedule_id_manual').parent();
                
                if (activityType === 'sick' || activityType === 'time_off') {
                    $('#sick_time_off_alert_manual').show();
                    scheduleContainerManual.show();
                } else if (activityType === 'teaching') {
                    $('#sick_time_off_alert_manual').hide();
                    scheduleContainerManual.show();
                } else {
                    $('#sick_time_off_alert_manual').hide();
                    scheduleContainerManual.hide();
                    $('#selected_schedule_id_manual').val('');
                }
            });

            // Calendar functionality
            initializeCalendar();
            initializeCalendarManual();

            // Handle delete form submission with confirmation
            $('#deleteForm').on('submit', function(e) {
                e.preventDefault();

                const submitBtn = $(this).find('button[type="submit"]');
                const originalText = submitBtn.html();
                submitBtn.html('<i class="fas fa-spinner fa-spin me-2"></i>Deleting...').prop('disabled',
                    true);

                // Submit after short delay to show loading state
                setTimeout(() => {
                    this.submit();
                }, 500);
            });

            // Calendar initialization for Clock In modal
            function initializeCalendar() {
                const currentDate = new Date();
                let currentMonth = currentDate.getMonth();
                let currentYear = currentDate.getFullYear();

                const monthNames = ["January", "February", "March", "April", "May", "June",
                    "July", "August", "September", "October", "November", "December"
                ];

                const dayNames = ["Sun", "Mon", "Tue", "Wed", "Thu", "Fri", "Sat"];

                // Schedule data from backend
                const scheduleData = @json($scheduleDataForJs);

                function renderCalendar() {
                    const firstDay = new Date(currentYear, currentMonth, 1).getDay();
                    const daysInMonth = new Date(currentYear, currentMonth + 1, 0).getDate();
                    const today = new Date();

                    $('#currentMonth').text(`${monthNames[currentMonth]} ${currentYear}`);

                    let calendarHTML = '';

                    // Day headers
                    dayNames.forEach(day => {
                        calendarHTML += `<div class="calendar-header-day">${day}</div>`;
                    });

                    // Empty cells for previous month
                    for (let i = 0; i < firstDay; i++) {
                        calendarHTML += '<div class="calendar-day other-month"></div>';
                    }

                    // Days of current month
                    for (let day = 1; day <= daysInMonth; day++) {
                        const date = new Date(currentYear, currentMonth, day);
                        const dateStr =
                            `${currentYear}-${String(currentMonth + 1).padStart(2, '0')}-${String(day).padStart(2, '0')}`;

                        let classes = ['calendar-day'];
                        let hasSchedule = false;

                        // Check if date has schedule
                        const daySchedules = scheduleData.filter(s => s.date === dateStr);
                        if (daySchedules.length > 0) {
                            classes.push('has-schedule');
                            hasSchedule = true;
                        }

                        // Mark today
                        if (date.toDateString() === today.toDateString()) {
                            classes.push('today');
                        }

                        // Mark past dates
                        if (date < today && !classes.includes('today')) {
                            classes.push('past-date');
                        }

                        // Disable dates more than 30 days in future
                        const maxDate = new Date();
                        maxDate.setDate(maxDate.getDate() + 30);
                        if (date > maxDate) {
                            classes.push('disabled');
                        }

                        calendarHTML += `
                    <div class="${classes.join(' ')}" data-date="${dateStr}" ${hasSchedule ? 'data-has-schedule="true"' : ''}>
                        ${day}
                        ${hasSchedule ? '<span class="schedule-indicator"></span>' : ''}
                    </div>
                `;
                    }

                    $('#calendarGrid').html(calendarHTML);
                }

                function showSchedulesForDate(dateStr) {
                    const daySchedules = scheduleData.filter(s => s.date === dateStr);
                    const formattedDate = new Date(dateStr).toLocaleDateString('en-US', {
                        weekday: 'long',
                        year: 'numeric',
                        month: 'long',
                        day: 'numeric'
                    });

                    $('#selectedDateDisplay').text(formattedDate);

                    if (daySchedules.length > 0) {
                        let optionsHTML = '';
                        daySchedules.forEach(schedule => {
                            const deadlineHours = getDeadlineHours(dateStr);
                            const isUrgent = deadlineHours <= 24;
                            const isUsed = schedule.is_used;
                            const isDisabled = isUsed ? 'disabled' : '';
                            const cardClass = isUsed ? 'schedule-option-card used-schedule' : 'schedule-option-card';

                            let badgeHTML = '';
                            if (isUsed) {
                                badgeHTML = '<span class="badge bg-secondary schedule-badge">Already Logged</span>';
                            } else if (isUrgent) {
                                badgeHTML = '<span class="badge bg-danger schedule-badge">Urgent</span>';
                            } else {
                                badgeHTML = '<span class="badge bg-success schedule-badge">Available</span>';
                            }

                            optionsHTML += `
                        <div class="col-md-6">
                            <div class="card ${cardClass} ${isDisabled}" data-schedule-id="${schedule.id}" ${isUsed ? 'title="This schedule has already been logged"' : ''}>
                                <div class="card-body p-3">
                                    <div class="d-flex justify-content-between align-items-start mb-2">
                                        <h6 class="mb-0 ${isUsed ? 'text-muted' : ''}">${schedule.title}</h6>
                                        ${badgeHTML}
                                    </div>
                                    <p class="text-muted mb-2 small">${schedule.class_name}</p>
                                    <div class="d-flex justify-content-between align-items-center">
                                        <small class="text-muted">
                                            <i class="fas fa-clock me-1"></i>${schedule.start_time} - ${schedule.end_time}
                                        </small>
                                        ${!isUsed && isUrgent ? '<small class="text-danger">⚠️ ' + deadlineHours + 'h left</small>' : ''}
                                        ${isUsed ? '<small class="text-muted"><i class="fas fa-ban me-1"></i>Used</small>' : ''}
                                    </div>
                                </div>
                            </div>
                        </div>
                    `;
                        });

                        $('#scheduleOptions').html(optionsHTML);
                        $('#availableSchedules').show();

                        // Add deadline warning if any schedule is urgent
                        const hasUrgentSchedule = daySchedules.some(schedule => getDeadlineHours(schedule.date) <=
                            24);
                        if (hasUrgentSchedule) {
                            showDeadlineWarning(dateStr);
                        }
                    } else {
                        $('#availableSchedules').hide();
                    }
                }

                function getDeadlineHours(scheduleDate) {
                    const now = new Date();
                    const scheduleDateTime = new Date(scheduleDate);
                    const diffInHours = (scheduleDateTime.getTime() - now.getTime()) / (1000 * 60 * 60);
                    return Math.max(0, Math.floor(diffInHours));
                }

                function showDeadlineWarning(dateStr) {
                    const deadlineHours = getDeadlineHours(dateStr);
                    if (deadlineHours <= 24) {
                        const warningHTML = `
                    <div class="alert alert-warning deadline-warning mt-3">
                        <div class="d-flex align-items-center">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            <div>
                                <strong>Deadline Warning!</strong><br>
                                <small>You have less than ${deadlineHours} hours before this schedule.
                                Time off or sick leave requests close to the schedule time may require
                                immediate replacement or cancellation.</small>
                            </div>
                        </div>
                    </div>
                `;
                        $('#availableSchedules').after(warningHTML);
                    }
                }

                // Event handlers
                $('#prevMonth').click(function() {
                    currentMonth--;
                    if (currentMonth < 0) {
                        currentMonth = 11;
                        currentYear--;
                    }
                    renderCalendar();
                });

                $('#nextMonth').click(function() {
                    currentMonth++;
                    if (currentMonth > 11) {
                        currentMonth = 0;
                        currentYear++;
                    }
                    renderCalendar();
                });

                $(document).on('click', '.calendar-day:not(.disabled)', function() {
                    const dateStr = $(this).data('date');
                    if (!dateStr) return;

                    $('.calendar-day').removeClass('selected');
                    $(this).addClass('selected');
                    $('#selected_date').val(dateStr);

                    if ($(this).data('has-schedule')) {
                        showSchedulesForDate(dateStr);
                    } else {
                        $('#availableSchedules').hide();
                        $('#selectedScheduleInfo').hide();
                        $('#selected_schedule_id').val('');
                    }

                    // Remove previous deadline warnings
                    $('.deadline-warning').remove();
                });

                $(document).on('click', '.schedule-option-card:not(.used-schedule)', function() {
                    const scheduleId = $(this).data('schedule-id');
                    const schedule = scheduleData.find(s => s.id == scheduleId);

                    // Prevent selection if schedule is already used
                    if (schedule.is_used) {
                        alert('This schedule has already been logged. Please delete the existing entry first if you need to create a new one.');
                        return;
                    }

                    $('.schedule-option-card').removeClass('selected');
                    $(this).addClass('selected');

                    $('#selected_schedule_id').val(scheduleId);

                    // Show selected schedule info
                    const scheduleHTML = `
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <h6 class="mb-1">${schedule.title}</h6>
                        <p class="text-muted mb-1">${schedule.class_name}</p>
                        <small class="text-muted">
                            <i class="fas fa-clock me-1"></i>${schedule.start_time} - ${schedule.end_time}
                        </small>
                    </div>
                    <button type="button" class="btn btn-sm btn-outline-danger" onclick="clearScheduleSelection()">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            `;

                    $('#scheduleDetails').html(scheduleHTML);
                    $('#selectedScheduleInfo').show();
                });

                // Initial render
                renderCalendar();
            }

            function clearScheduleSelection() {
                $('#selected_schedule_id').val('');
                $('#selectedScheduleInfo').hide();
                $('.schedule-option-card').removeClass('selected');
                $('.calendar-day').removeClass('selected');
            }

            window.clearScheduleSelectionManual = function() {
                $('#selected_schedule_id_manual').val('');
                $('#selectedScheduleInfoManual').hide();
                $('#scheduleOptionsManual .schedule-option-card').removeClass('selected');
                $('#calendarGridManual .calendar-day').removeClass('selected');
            }

            // Similar calendar for manual entry (with different date range)
            function initializeCalendarManual() {
                const currentDate = new Date();
                let currentMonth = currentDate.getMonth();
                let currentYear = currentDate.getFullYear();

                const monthNames = ["January", "February", "March", "April", "May", "June",
                    "July", "August", "September", "October", "November", "December"
                ];

                const dayNames = ["Sun", "Mon", "Tue", "Wed", "Thu", "Fri", "Sat"];

                // Extended schedule data (60 days range)
                const scheduleData = @json($scheduleDataForJs);

                function renderCalendarManual() {
                    const firstDay = new Date(currentYear, currentMonth, 1).getDay();
                    const daysInMonth = new Date(currentYear, currentMonth + 1, 0).getDate();
                    const today = new Date();

                    $('#currentMonthManual').text(`${monthNames[currentMonth]} ${currentYear}`);

                    let calendarHTML = '';

                    // Day headers
                    dayNames.forEach(day => {
                        calendarHTML += `<div class="calendar-header-day">${day}</div>`;
                    });

                    // Empty cells for previous month
                    for (let i = 0; i < firstDay; i++) {
                        calendarHTML += '<div class="calendar-day other-month"></div>';
                    }

                    // Days of current month
                    for (let day = 1; day <= daysInMonth; day++) {
                        const date = new Date(currentYear, currentMonth, day);
                        const dateStr =
                            `${currentYear}-${String(currentMonth + 1).padStart(2, '0')}-${String(day).padStart(2, '0')}`;

                        let classes = ['calendar-day'];
                        let hasSchedule = false;

                        // Check if date has schedule
                        const daySchedules = scheduleData.filter(s => s.date === dateStr);
                        if (daySchedules.length > 0) {
                            classes.push('has-schedule');
                            hasSchedule = true;
                        }

                        // Mark today
                        if (date.toDateString() === today.toDateString()) {
                            classes.push('today');
                        }

                        calendarHTML += `
                    <div class="${classes.join(' ')}" data-date="${dateStr}" ${hasSchedule ? 'data-has-schedule="true"' : ''}>
                        ${day}
                        ${hasSchedule ? '<span class="schedule-indicator"></span>' : ''}
                    </div>
                `;
                    }

                    $('#calendarGridManual').html(calendarHTML);
                }

                // Event handlers for manual calendar
                $('#prevMonthManual').click(function() {
                    currentMonth--;
                    if (currentMonth < 0) {
                        currentMonth = 11;
                        currentYear--;
                    }
                    renderCalendarManual();
                });

                $('#nextMonthManual').click(function() {
                    currentMonth++;
                    if (currentMonth > 11) {
                        currentMonth = 0;
                        currentYear++;
                    }
                    renderCalendarManual();
                });

                // Calendar day click handler for manual
                $(document).on('click', '#calendarGridManual .calendar-day:not(.disabled)', function() {
                    const dateStr = $(this).data('date');
                    if (!dateStr) return;

                    $('#calendarGridManual .calendar-day').removeClass('selected');
                    $(this).addClass('selected');
                    $('#selected_date_manual').val(dateStr);

                    if ($(this).data('has-schedule')) {
                        showSchedulesForDateManual(dateStr);
                    } else {
                        $('#availableSchedulesManual').hide();
                        $('#selectedScheduleInfoManual').hide();
                        $('#selected_schedule_id_manual').val('');
                    }
                });

                // Schedule selection for manual
                $(document).on('click', '#scheduleOptionsManual .schedule-option-card:not(.used-schedule)', function() {
                    const scheduleId = $(this).data('schedule-id');
                    const schedule = scheduleData.find(s => s.id == scheduleId);

                    // Prevent selection if schedule is already used
                    if (schedule.is_used) {
                        alert('This schedule has already been logged. Please delete the existing entry first if you need to create a new one.');
                        return;
                    }

                    $('#scheduleOptionsManual .schedule-option-card').removeClass('selected');
                    $(this).addClass('selected');

                    $('#selected_schedule_id_manual').val(scheduleId);

                    // Show selected schedule info
                    const scheduleHTML = `
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <h6 class="mb-1">${schedule.title}</h6>
                                <p class="text-muted mb-1">${schedule.class_name}</p>
                                <small class="text-muted">
                                    <i class="fas fa-clock me-1"></i>${schedule.start_time} - ${schedule.end_time}
                                </small>
                            </div>
                            <button type="button" class="btn btn-sm btn-outline-danger" onclick="clearScheduleSelectionManual()">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
                    `;

                    $('#scheduleDetailsManual').html(scheduleHTML);
                    $('#selectedScheduleInfoManual').show();
                });

                function showSchedulesForDateManual(dateStr) {
                    const daySchedules = scheduleData.filter(s => s.date === dateStr);
                    const formattedDate = new Date(dateStr).toLocaleDateString('en-US', {
                        weekday: 'long',
                        year: 'numeric',
                        month: 'long',
                        day: 'numeric'
                    });

                    $('#selectedDateDisplayManual').text(formattedDate);

                    if (daySchedules.length > 0) {
                        let optionsHTML = '';
                        daySchedules.forEach(schedule => {
                            const isUsed = schedule.is_used;
                            const isDisabled = isUsed ? 'disabled' : '';
                            const cardClass = isUsed ? 'schedule-option-card used-schedule' : 'schedule-option-card';

                            let badgeHTML = '';
                            if (isUsed) {
                                badgeHTML = '<span class="badge bg-secondary schedule-badge">Already Logged</span>';
                            } else {
                                badgeHTML = '<span class="badge bg-primary schedule-badge">Available</span>';
                            }

                            optionsHTML += `
                                <div class="col-md-6">
                                    <div class="card ${cardClass} ${isDisabled}" data-schedule-id="${schedule.id}" ${isUsed ? 'title="This schedule has already been logged"' : ''}>
                                        <div class="card-body p-3">
                                            <div class="d-flex justify-content-between align-items-start mb-2">
                                                <h6 class="mb-0 ${isUsed ? 'text-muted' : ''}">${schedule.title}</h6>
                                                ${badgeHTML}
                                            </div>
                                            <p class="text-muted mb-2 small">${schedule.class_name}</p>
                                            <div class="d-flex justify-content-between align-items-center">
                                                <small class="text-muted">
                                                    <i class="fas fa-clock me-1"></i>${schedule.start_time} - ${schedule.end_time}
                                                </small>
                                                ${isUsed ? '<small class="text-muted"><i class="fas fa-ban me-1"></i>Used</small>' : ''}
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            `;
                        });

                        $('#scheduleOptionsManual').html(optionsHTML);
                        $('#availableSchedulesManual').show();
                    } else {
                        $('#availableSchedulesManual').hide();
                    }
                }

                // Initial render
                renderCalendarManual();
            }
        });
    </script>
@endpush
