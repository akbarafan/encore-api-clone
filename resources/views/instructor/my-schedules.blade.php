@extends('instructor.layouts.app')

@section('title', 'My Schedules')
@section('page-title', 'My Schedules')
@section('page-subtitle', 'Manage your teaching schedules')

@section('content')
<!-- Statistics Cards -->
<div class="row mb-4">
    <div class="col-xl-3 col-md-6">
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="flex-shrink-0">
                        <div class="bg-primary bg-opacity-10 rounded-3 p-3">
                            <i class="fas fa-calendar-alt text-primary fa-lg"></i>
                        </div>
                    </div>
                    <div class="flex-grow-1 ms-3">
                        <h6 class="text-muted mb-1">Total Schedules</h6>
                        <h4 class="mb-0">{{ $totalSchedules }}</h4>
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
                            <i class="fas fa-clock text-warning fa-lg"></i>
                        </div>
                    </div>
                    <div class="flex-grow-1 ms-3">
                        <h6 class="text-muted mb-1">Today</h6>
                        <h4 class="mb-0">{{ $todaySchedules }}</h4>
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
                            <i class="fas fa-calendar-check text-success fa-lg"></i>
                        </div>
                    </div>
                    <div class="flex-grow-1 ms-3">
                        <h6 class="text-muted mb-1">Upcoming</h6>
                        <h4 class="mb-0">{{ $upcomingSchedules }}</h4>
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
                            <i class="fas fa-check-circle text-info fa-lg"></i>
                        </div>
                    </div>
                    <div class="flex-grow-1 ms-3">
                        <h6 class="text-muted mb-1">Completed</h6>
                        <h4 class="mb-0">{{ $totalSchedules - $todaySchedules - $upcomingSchedules }}</h4>
                        <small class="text-muted">Past schedules</small>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Header with Add Button -->
<div class="row mb-4">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h4 class="mb-0">Schedule Management</h4>
                <p class="text-muted mb-0">Manage and organize your teaching schedules</p>
            </div>
            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#scheduleModal">
                <i class="fas fa-plus me-2"></i>Add New Schedule
            </button>
        </div>
    </div>
</div>

@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show border-0 shadow-sm" role="alert">
        <i class="fas fa-check-circle me-2"></i>{{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif

@if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show border-0 shadow-sm" role="alert">
        <i class="fas fa-exclamation-circle me-2"></i>{{ session('error') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif

@if($errors->any())
    <div class="alert alert-danger alert-dismissible fade show border-0 shadow-sm" role="alert">
        <i class="fas fa-exclamation-circle me-2"></i>
        <ul class="mb-0">
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif

<!-- Filters -->
<div class="card border-0 shadow-sm mb-4">
    <div class="card-body">
        <form method="GET" action="{{ route('instructor.schedules.index') }}">
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
                    <label class="form-label text-muted">Class</label>
                    <select class="form-select" name="class_id">
                        <option value="">All Classes</option>
                        @foreach($instructorClasses as $class)
                            <option value="{{ $class->id }}" {{ request('class_id') == $class->id ? 'selected' : '' }}>
                                {{ $class->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label text-muted">Status</label>
                    <select class="form-select" name="status">
                        <option value="">All Status</option>
                        @foreach($availableStatuses as $key => $label)
                            <option value="{{ $key }}" {{ request('status') == $key ? 'selected' : '' }}>
                                {{ $label }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label text-muted">&nbsp;</label>
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-outline-primary">
                            <i class="fas fa-filter"></i>
                        </button>
                        <a href="{{ route('instructor.schedules.index') }}" class="btn btn-outline-secondary">
                            <i class="fas fa-times"></i>
                        </a>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Schedules Table with DataTables -->
<div class="card border-0 shadow-sm">
    <div class="card-body">
        <div class="table-responsive">
            <table id="schedulesTable" class="table table-hover">
                <thead class="bg-light">
                    <tr>
                        <th>Schedule Details</th>
                        <th>Class</th>
                        <th>Date & Time</th>
                        <th>Status</th>

                        <th>Notes</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($schedules as $schedule)
                    <tr>
                        <td>
                            <div>
                                <h6 class="mb-1">{{ $schedule->title }}</h6>
                                <div class="small text-muted">
                                    {{ $schedule->class->category->name ?? 'General' }} • {{ $schedule->class->type->name ?? 'Standard' }}
                                </div>
                            </div>
                        </td>
                        <td>
                            <div>
                                <div class="fw-semibold">{{ $schedule->class->name }}</div>
                                <div class="small text-muted">
                                    <i class="fas fa-users me-1"></i>{{ $schedule->class->enrollments_count ?? $schedule->class->students()->count() ?? 0 }} students enrolled
                                </div>
                            </div>
                        </td>
                        <td>
                            <div class="small">
                                <div class="fw-semibold">
                                    <i class="fas fa-calendar me-1 text-primary"></i>{{ Carbon\Carbon::parse($schedule->date)->format('M d, Y') }}
                                </div>
                                <div class="text-muted mt-1">
                                    <i class="fas fa-clock me-1"></i>{{ Carbon\Carbon::parse($schedule->start_time)->format('H:i') }} - {{ Carbon\Carbon::parse($schedule->end_time)->format('H:i') }}
                                </div>
                                <div class="text-muted mt-1">
                                    <i class="fas fa-hourglass-half me-1"></i>{{ Carbon\Carbon::parse($schedule->start_time)->diffInMinutes(Carbon\Carbon::parse($schedule->end_time)) }} minutes
                                </div>
                            </div>
                        </td>
                        <td>
                            <span class="badge {{ $schedule->status_badge_class }}">
                                @if($schedule->status == 'today')
                                    <i class="fas fa-clock me-1"></i>Today
                                @elseif($schedule->status == 'upcoming')
                                    <i class="fas fa-calendar-plus me-1"></i>Upcoming
                                @else
                                    <i class="fas fa-check me-1"></i>Completed
                                @endif
                            </span>
                        </td>

                        <td>
                            @if($schedule->notes)
                                <span class="text-muted" title="{{ $schedule->notes }}">
                                    {{ Str::limit($schedule->notes, 30) }}
                                </span>
                            @else
                                <span class="text-muted">-</span>
                            @endif
                        </td>
                        <td>
                            <div class="btn-group" role="group">
                                <button type="button" class="btn btn-sm btn-outline-info view-materials-btn"
                                    data-schedule-id="{{ $schedule->id }}"
                                    data-schedule-title="{{ $schedule->title }}"
                                    data-bs-toggle="modal"
                                    data-bs-target="#materialsModal"
                                    title="Manage Materials">
                                    <i class="fas fa-tasks"></i>
                                </button>

                                @if($schedule->can_edit)
                                    <button type="button" class="btn btn-sm btn-outline-primary edit-schedule-btn"
                                        data-schedule-id="{{ $schedule->id }}"
                                        data-bs-toggle="modal"
                                        data-bs-target="#scheduleModal"
                                        title="Edit Schedule">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                @endif

                                @if($schedule->can_edit)
                                    <button type="button" class="btn btn-sm btn-outline-danger delete-schedule-btn"
                                        data-schedule-id="{{ $schedule->id }}"
                                        title="Delete Schedule">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <div class="d-flex justify-content-center mt-4">
            {{ $schedules->appends(request()->query())->links() }}
        </div>
    </div>
</div>

<!-- Modal for Add/Edit Schedule -->
<div class="modal fade" id="scheduleModal" tabindex="-1" aria-labelledby="scheduleModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content border-0 shadow">
            <form id="scheduleForm" method="POST" action="{{ route('instructor.schedules.store') }}">
                @csrf
                <input type="hidden" name="_method" value="POST" id="methodField">

                <div class="modal-header border-0 pb-0">
                    <div>
                        <h5 class="modal-title" id="scheduleModalLabel">Add New Schedule</h5>
                        <p class="text-muted mb-0 small">Fill in the details to create a new schedule</p>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="class_id" class="form-label">Class <span class="text-danger">*</span></label>
                            <select class="form-select" id="class_id" name="class_id" required>
                                <option value="">Select Class</option>
                                @foreach($instructorClasses as $class)
                                    <option value="{{ $class->id }}">{{ $class->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label for="title" class="form-label">Schedule Title <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="title" name="title" required placeholder="Enter schedule title">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="schedule_date_picker" class="form-label">Select Schedule Date <span class="text-danger">*</span></label>
                        <div class="calendar-picker-container">
                            <input type="hidden" id="selected_date_schedule" name="date" required>

                            <!-- Calendar Widget -->
                            <div class="calendar-widget border rounded-3 p-3 bg-light">
                                <div class="calendar-header d-flex justify-content-between align-items-center mb-3">
                                    <button type="button" class="btn btn-sm btn-outline-secondary" id="prevMonthSchedule">
                                        <i class="fas fa-chevron-left"></i>
                                    </button>
                                    <h6 class="mb-0" id="currentMonthSchedule"></h6>
                                    <button type="button" class="btn btn-sm btn-outline-secondary" id="nextMonthSchedule">
                                        <i class="fas fa-chevron-right"></i>
                                    </button>
                                </div>
                                <div class="calendar-grid" id="calendarGridSchedule"></div>
                            </div>

                            <!-- Selected Date Info -->
                            <div class="selected-date-info mt-3 p-3 border rounded-3 bg-white" id="selectedDateInfoSchedule" style="display: none;">
                                <h6 class="mb-2 text-primary">
                                    <i class="fas fa-calendar-check me-2"></i>Selected Date
                                </h6>
                                <div id="dateDetailsSchedule"></div>
                            </div>
                        </div>
                    </div>

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="start_time" class="form-label">Start Time <span class="text-danger">*</span></label>
                            <input type="time" class="form-control" id="start_time" name="start_time" required>
                        </div>
                        <div class="col-md-6">
                            <label for="end_time" class="form-label">End Time <span class="text-danger">*</span></label>
                            <input type="time" class="form-control" id="end_time" name="end_time" required>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="notes" class="form-label">Notes</label>
                        <textarea class="form-control" id="notes" name="notes" rows="3"
                                  placeholder="Any additional notes or instructions for this schedule..."></textarea>
                    </div>
                </div>

                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save me-2"></i>Save Schedule
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
                        <i class="fas fa-exclamation-triangle me-2"></i>Delete Schedule
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
                        <p class="mb-0">Are you sure you want to delete this schedule? This will permanently remove the schedule and cannot be recovered.</p>
                    </div>
                </div>
            </div>
            <div class="modal-footer border-0 pt-0">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                <form id="deleteForm" method="POST" style="display: inline;">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-danger">
                        <i class="fas fa-trash me-2"></i>Delete Schedule
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Cancel Reschedule Confirmation Modal -->
<div class="modal fade" id="cancelRescheduleModal" tabindex="-1" aria-labelledby="cancelRescheduleModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content border-0 shadow">
            <div class="modal-header border-0 pb-0">
                <div>
                    <h5 class="modal-title text-warning" id="cancelRescheduleModalLabel">
                        <i class="fas fa-times-circle me-2"></i>Cancel Reschedule Request
                    </h5>
                    <p class="text-muted mb-0 small">This will withdraw your reschedule request</p>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="d-flex align-items-center">
                    <div class="flex-shrink-0">
                        <div class="bg-warning bg-opacity-10 rounded-3 p-3">
                            <i class="fas fa-times text-warning fa-lg"></i>
                        </div>
                    </div>
                    <div class="flex-grow-1 ms-3">
                        <p class="mb-0">Are you sure you want to cancel your reschedule request? The original schedule time will remain active.</p>
                    </div>
                </div>
            </div>
            <div class="modal-footer border-0 pt-0">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Keep Request</button>
                <form id="cancelRescheduleForm" method="POST" style="display: inline;">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-warning">
                        <i class="fas fa-times me-2"></i>Cancel Request
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Materials Management Modal -->
<div class="modal fade" id="materialsModal" tabindex="-1" aria-labelledby="materialsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content border-0 shadow">
            <div class="modal-header border-0 pb-0">
                <div>
                    <h5 class="modal-title" id="materialsModalLabel">
                        <i class="fas fa-tasks me-2"></i>Schedule Materials
                    </h5>
                    <p class="text-muted mb-0 small" id="materialsScheduleTitle">Manage pre-class and post-class materials</p>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <!-- Navigation Tabs -->
                <ul class="nav nav-tabs mb-4" id="materialsTabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" id="pre-class-tab" data-bs-toggle="tab" data-bs-target="#pre-class" type="button" role="tab">
                            <i class="fas fa-upload me-2"></i>Pre-Class Materials
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="post-class-tab" data-bs-toggle="tab" data-bs-target="#post-class" type="button" role="tab">
                            <i class="fas fa-download me-2"></i>Post-Class Materials
                        </button>
                    </li>
                </ul>

                <!-- Tab Content -->
                <div class="tab-content" id="materialsTabContent">
                    <!-- Pre-Class Materials Tab -->
                    <div class="tab-pane fade show active" id="pre-class" role="tabpanel">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h6 class="mb-0">Pre-Class Materials</h6>
                            <button type="button" class="btn btn-sm btn-primary" id="addPreClassBtn">
                                <i class="fas fa-plus me-1"></i>Add Material
                            </button>
                        </div>
                        <div id="preClassMaterials">
                            <!-- Pre-class materials will be loaded here -->
                            <div class="text-center py-4 text-muted">
                                <i class="fas fa-upload fa-2x mb-2"></i>
                                <p>Loading materials...</p>
                            </div>
                        </div>
                    </div>

                    <!-- Post-Class Materials Tab -->
                    <div class="tab-pane fade" id="post-class" role="tabpanel">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h6 class="mb-0">Post-Class Materials</h6>
                            <button type="button" class="btn btn-sm btn-primary" id="addPostClassBtn">
                                <i class="fas fa-plus me-1"></i>Add Material
                            </button>
                        </div>
                        <div id="postClassMaterials">
                            <!-- Post-class materials will be loaded here -->
                            <div class="text-center py-4 text-muted">
                                <i class="fas fa-download fa-2x mb-2"></i>
                                <p>Loading materials...</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer border-0 pt-0">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<!-- Add/Edit Material Modal -->
<div class="modal fade" id="materialFormModal" tabindex="-1" aria-labelledby="materialFormModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content border-0 shadow">
            <form id="materialForm" method="POST" action="{{ route('instructor.materials.store') }}" enctype="multipart/form-data">
                @csrf
                <input type="hidden" name="_method" value="POST" id="materialMethodField">
                <input type="hidden" name="schedule_id" id="materialScheduleId">
                <input type="hidden" name="material_type" id="materialType">

                <div class="modal-header border-0 pb-0">
                    <div>
                        <h5 class="modal-title" id="materialFormModalLabel">Add Material</h5>
                        <p class="text-muted mb-0 small" id="materialFormSubtitle">Create new material for this schedule</p>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-8">
                            <label for="activityTitle" class="form-label">Title <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="activityTitle" name="title" required
                                   placeholder="Enter activity title">
                        </div>
                        <div class="col-md-4">
                            <label for="activityMandatory" class="form-label">Type</label>
                            <select class="form-select" id="activityMandatory" name="is_mandatory">
                                <option value="0">Optional</option>
                                <option value="1">Mandatory</option>
                            </select>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="activityDescription" class="form-label">Description</label>
                        <textarea class="form-control" id="activityDescription" name="description" rows="3"
                                  placeholder="Describe the activity or material..."></textarea>
                    </div>

                    <div class="mb-3">
                        <label for="activityInstructions" class="form-label">Instructions</label>
                        <textarea class="form-control" id="activityInstructions" name="instructions" rows="3"
                                  placeholder="Instructions for students..."></textarea>
                    </div>

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="activityAvailableFrom" class="form-label">Available From</label>
                            <input type="datetime-local" class="form-control" id="activityAvailableFrom" name="available_from">
                        </div>
                        <div class="col-md-6">
                            <label for="activityDueDate" class="form-label">Due Date</label>
                            <input type="datetime-local" class="form-control" id="activityDueDate" name="due_date">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="activityFile" class="form-label">Upload File (Optional)</label>
                        <input type="file" class="form-control" id="activityFile" name="file"
                               accept=".pdf,.doc,.docx,.ppt,.pptx,.xls,.xlsx,.jpg,.jpeg,.png,.mp4,.mp3">
                        <div class="form-text">Supported: PDF, DOC, PPT, XLS, Images, Videos, Audio (Max: 10MB)</div>
                    </div>

                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="activityActive" name="is_active" value="1" checked>
                        <label class="form-check-label" for="activityActive">
                            Active (visible to students)
                        </label>
                    </div>
                </div>

                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save me-2"></i>Save Activity
                    </button>
                </div>
            </form>
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
.bg-primary-subtle {
    background-color: rgba(13, 110, 253, 0.1) !important;
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
.dataTables_wrapper .dataTables_length select,
.dataTables_wrapper .dataTables_filter input {
    border-radius: 0.375rem;
}
.dataTables_wrapper .dataTables_paginate .paginate_button.current {
    background: #0d6efd !important;
    border-color: #0d6efd !important;
}

/* Calendar Styles */
.calendar-grid {
    display: grid;
    grid-template-columns: repeat(7, 1fr);
    gap: 2px;
}

.calendar-header-day {
    padding: 8px;
    text-align: center;
    font-weight: 600;
    color: #6c757d;
    font-size: 0.875rem;
    background-color: #f8f9fa;
    border-radius: 0.375rem;
}

.calendar-day {
    padding: 10px;
    text-align: center;
    cursor: pointer;
    border-radius: 0.375rem;
    transition: all 0.2s ease;
    position: relative;
    min-height: 40px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 500;
}

.calendar-day:hover {
    background-color: #e9ecef;
}

.calendar-day.other-month {
    color: #dee2e6;
    cursor: not-allowed;
}

.calendar-day.today {
    background-color: #0d6efd;
    color: white;
    font-weight: 600;
}

.calendar-day.selected {
    background-color: #198754;
    color: white;
    font-weight: 600;
}

.calendar-day.disabled {
    color: #dee2e6;
    cursor: not-allowed;
    background-color: #f8f9fa;
}

.calendar-day.past-date {
    color: #adb5bd;
}

/* Mobile Responsive Styles */
@media (max-width: 768px) {
    .row.mb-4 .col-xl-3 {
        margin-bottom: 1rem;
    }
    
    .card-body {
        padding: 1rem;
    }
    
    .modal-dialog {
        margin: 0.5rem;
    }
    
    .modal-dialog.modal-lg,
    .modal-dialog.modal-xl {
        max-width: calc(100vw - 1rem);
    }
    
    .table-responsive {
        font-size: 0.875rem;
    }
    
    .btn-group .btn {
        padding: 0.25rem 0.5rem;
        font-size: 0.75rem;
    }
    
    .calendar-day {
        min-height: 35px;
        padding: 5px;
        font-size: 0.875rem;
    }
    
    .calendar-header-day {
        padding: 5px;
        font-size: 0.75rem;
    }
    
    .d-flex.justify-content-between.align-items-center {
        flex-direction: column;
        align-items: flex-start !important;
        gap: 1rem;
    }
    
    .d-flex.gap-2 {
        width: 100%;
        justify-content: center;
    }
}

@media (max-width: 576px) {
    .btn-group {
        flex-direction: column;
        width: 100%;
    }
    
    .btn-group .btn {
        margin-right: 0;
        margin-bottom: 0.25rem;
        border-radius: 0.375rem !important;
    }
    
    .calendar-grid {
        gap: 1px;
    }
    
    .calendar-day {
        min-height: 30px;
        font-size: 0.75rem;
    }
    
    .modal-body .row.g-3 {
        --bs-gutter-x: 0.5rem;
    }
    
    .table-responsive {
        font-size: 0.75rem;
    }
    
    .card .card-body h6 {
        font-size: 0.875rem;
    }
    
    .card .card-body h4 {
        font-size: 1.25rem;
    }
    
    .col-xl-3.col-md-6 .card-body .d-flex {
        flex-direction: column;
        text-align: center;
    }
    
    .col-xl-3.col-md-6 .card-body .flex-shrink-0 {
        margin-bottom: 0.5rem;
    }
    
    .col-xl-3.col-md-6 .card-body .flex-grow-1 {
        margin-left: 0 !important;
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
    const table = $('#schedulesTable').DataTable({
        responsive: true,
        pageLength: 10,
        lengthMenu: [[10, 25, 50, -1], [10, 25, 50, "All"]],
        order: [[2, 'asc']],
        columnDefs: [
            {
                targets: -1, // Actions column
                orderable: false,
                searchable: false
            }
        ],
        language: {
            search: "Search schedules:",
            lengthMenu: "Show _MENU_ schedules per page",
            info: "Showing _START_ to _END_ of _TOTAL_ schedules",
            infoEmpty: "No schedules available",
            infoFiltered: "(filtered from _MAX_ total schedules)",
            emptyTable: "No schedules found. Add your first schedule!",
            zeroRecords: "No matching schedules found"
        },
        dom: '<"row"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6"f>>' +
             '<"row"<"col-sm-12"tr>>' +
             '<"row"<"col-sm-12 col-md-5"i><"col-sm-12 col-md-7"p>>',
    });

    // Store schedules data for JavaScript access
    const schedulesData = {!! json_encode($schedules->map(function($schedule) {
        return [
            'id' => $schedule->id,
            'class_id' => $schedule->class_id,
            'title' => $schedule->title,
            'date' => $schedule->date ? $schedule->date->format('Y-m-d') : '',
            'start_time' => $schedule->start_time ? Carbon\Carbon::parse($schedule->start_time)->format('H:i') : '',
            'end_time' => $schedule->end_time ? Carbon\Carbon::parse($schedule->end_time)->format('H:i') : '',
            'notes' => $schedule->notes,
        ];
    })) !!};
    let editingScheduleId = null;
    let currentScheduleId = null;
    let currentMaterialId = null;

    // Materials button click handler
    $(document).on('click', '.view-materials-btn', function() {
        const scheduleId = $(this).data('schedule-id');
        const scheduleTitle = $(this).data('schedule-title');
        viewMaterials(scheduleId, scheduleTitle);
    });

    // Edit schedule button click handler
    $(document).on('click', '.edit-schedule-btn', function() {
        const scheduleId = $(this).data('schedule-id');
        editSchedule(scheduleId);
    });

    // Delete schedule button click handler
    $(document).on('click', '.delete-schedule-btn', function() {
        const scheduleId = $(this).data('schedule-id');
        deleteSchedule(scheduleId);
    });



    // Edit schedule function
    function editSchedule(scheduleId) {
        editingScheduleId = scheduleId;
        const scheduleItem = schedulesData.find(s => s.id == scheduleId);

        if (!scheduleItem) {
            console.error('Schedule not found');
            return;
        }

        // Update modal title
        $('#scheduleModalLabel').text('Edit Schedule');

        // Update form action and method
        const form = $('#scheduleForm');
        form.attr('action', `{{ url('instructor/schedules') }}/${scheduleId}`);
        $('#methodField').val('PUT');

        // Fill form with current data
        $('#class_id').val(scheduleItem.class_id || '');
        $('#title').val(scheduleItem.title || '');
        $('#selected_date_schedule').val(scheduleItem.date || '');
        $('#start_time').val(scheduleItem.start_time || '');
        $('#end_time').val(scheduleItem.end_time || '');
        $('#notes').val(scheduleItem.notes || '');
        
        // Update calendar display if date is selected
        if (scheduleItem.date) {
            updateCalendarDisplaySchedule(scheduleItem.date);
        }
    }

    // Delete schedule function
    function deleteSchedule(scheduleId) {
        $('#deleteForm').attr('action', `{{ url('instructor/schedules') }}/${scheduleId}`);
        $('#deleteModal').modal('show');
    }



    // View materials function
    function viewMaterials(scheduleId, scheduleTitle) {
        currentScheduleId = scheduleId;
        $('#materialsScheduleTitle').text(`${scheduleTitle} - Materials`);
        loadMaterials(scheduleId);
    }

    // Load materials for a schedule
    function loadMaterials(scheduleId) {
        // Show loading state
        $('#preClassMaterials').html(`
            <div class="text-center py-4 text-muted">
                <i class="fas fa-spinner fa-spin fa-2x mb-2"></i>
                <p>Loading materials...</p>
            </div>
        `);
        $('#postClassMaterials').html(`
            <div class="text-center py-4 text-muted">
                <i class="fas fa-spinner fa-spin fa-2x mb-2"></i>
                <p>Loading materials...</p>
            </div>
        `);

        // Fetch materials from server
        fetch(`{{ url('instructor/schedules') }}/${scheduleId}/materials`)
            .then(response => response.json())
            .then(data => {
                console.log('Materials API Response:', data);
                if (data.success) {
                    console.log('Materials data:', data.materials);
                    renderMaterials(data.materials);
                } else {
                    console.error('API returned error:', data);
                    showErrorMessage('Failed to load materials');
                }
            })
            .catch(error => {
                console.error('Error loading materials:', error);
                showErrorMessage('Failed to load materials');
            });
    }

    // Render materials in tabs
    function renderMaterials(materials) {
        const preClassMaterials = materials.filter(m => m.material_type === 'pre_class');
        const postClassMaterials = materials.filter(m => m.material_type === 'post_class');

        renderMaterialList(preClassMaterials, '#preClassMaterials', 'pre_class');
        renderMaterialList(postClassMaterials, '#postClassMaterials', 'post_class');
    }

    // Render material list
    function renderMaterialList(materials, containerId, materialType) {
        const container = $(containerId);

        if (materials.length === 0) {
            container.html(`
                <div class="text-center py-4 text-muted">
                    <i class="fas fa-${materialType === 'pre_class' ? 'upload' : 'download'} fa-2x mb-2"></i>
                    <p>No ${materialType === 'pre_class' ? 'pre-class materials' : 'post-class materials'} yet</p>
                </div>
            `);
            return;
        }

        let html = '';
        materials.forEach(material => {
            const badgeClass = material.is_mandatory ? 'bg-danger' : 'bg-secondary';
            const badgeText = material.is_mandatory ? 'Mandatory' : 'Optional';
            const statusClass = material.is_active ? 'text-success' : 'text-muted';
            const statusText = material.is_active ? 'Active' : 'Inactive';

            html += `
                <div class="card mb-3 border-start border-${material.is_mandatory ? 'danger' : 'secondary'} border-3">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start">
                            <div class="flex-grow-1">
                                <h6 class="card-title mb-1">
                                    ${material.title}
                                    <span class="badge ${badgeClass} ms-2">${badgeText}</span>
                                    <span class="badge bg-light text-dark ms-1">${statusText}</span>
                                </h6>
                                ${material.description ? `<p class="card-text text-muted small mb-2">${material.description}</p>` : ''}
                                ${material.instructions ? `<p class="card-text small mb-2"><strong>Instructions:</strong> ${material.instructions}</p>` : ''}
                                <div class="small text-muted">
                                    ${material.available_from ? `<span><i class="fas fa-calendar-alt me-1"></i>Available from: ${new Date(material.available_from).toLocaleString()}</span><br>` : ''}
                                    ${material.due_date ? `<span><i class="fas fa-clock me-1"></i>Due: ${new Date(material.due_date).toLocaleString()}</span><br>` : ''}
                                    ${material.file ? `<span><i class="fas fa-file me-1"></i>File attached</span>` : ''}
                                </div>
                            </div>
                            <div class="btn-group ms-3">
                                <button type="button" class="btn btn-sm btn-outline-primary edit-material-btn"
                                        data-material-id="${material.id}"
                                        data-material-type="${material.material_type}"
                                        title="Edit Material">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <button type="button" class="btn btn-sm btn-outline-danger delete-material-btn"
                                        data-material-id="${material.id}"
                                        title="Delete Material">
                                    <i class="fas fa-trash"></i>
                                </button>
                                ${material.file ? `
                                    <a href="{{ url('instructor/files') }}/${material.file.id}/download"
                                       class="btn btn-sm btn-outline-success"
                                       title="Download File">
                                        <i class="fas fa-download"></i>
                                    </a>
                                ` : ''}
                            </div>
                        </div>
                    </div>
                </div>
            `;
        });

        container.html(html);
    }

    // Add pre-class material button
    $(document).on('click', '#addPreClassBtn', function() {
        openMaterialForm('pre_class', 'Add Pre-Class Material');
    });

    // Add post-class material button
    $(document).on('click', '#addPostClassBtn', function() {
        openMaterialForm('post_class', 'Add Post-Class Material');
    });

    // Edit material button
    $(document).on('click', '.edit-material-btn', function() {
        const materialId = $(this).data('material-id');
        const materialType = $(this).data('material-type');
        editMaterial(materialId, materialType);
    });

    // Delete material button
    $(document).on('click', '.delete-material-btn', function() {
        const materialId = $(this).data('material-id');
        deleteMaterial(materialId);
    });

    // Open material form
    function openMaterialForm(materialType, title) {
        currentMaterialId = null;
        $('#materialFormModalLabel').text(title);
        $('#materialFormSubtitle').text(`Create new ${materialType.replace('_', '-')} for this schedule`);
        $('#materialType').val(materialType);
        $('#materialScheduleId').val(currentScheduleId);
        $('#materialForm').attr('action', '{{ route("instructor.materials.store") }}');
        $('#materialMethodField').val('POST');
        $('#materialFormModal').modal('show');
    }

    // Edit material function (placeholder - would need to fetch material data)
    function editMaterial(materialId, materialType) {
        // This would typically fetch the material data and populate the form
        currentMaterialId = materialId;
        $('#materialFormModalLabel').text('Edit Material');
        $('#materialFormSubtitle').text('Update material details');
        $('#materialForm').attr('action', `{{ url('instructor/materials') }}/${materialId}`);
        $('#materialMethodField').val('PUT');
        $('#materialFormModal').modal('show');
    }

    // Delete material function (placeholder)
    function deleteMaterial(materialId) {
        if (confirm('Are you sure you want to delete this material?')) {
            // This would typically send a DELETE request
            fetch(`{{ url('instructor/materials') }}/${materialId}`, {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
                    'Content-Type': 'application/json',
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    loadMaterials(currentScheduleId);
                    showSuccessMessage('Material deleted successfully');
                } else {
                    showErrorMessage('Failed to delete material');
                }
            })
            .catch(error => {
                console.error('Error deleting material:', error);
                showErrorMessage('Failed to delete material');
            });
        }
    }

    // Helper functions for messages
    function showSuccessMessage(message) {
        // You can implement a toast or alert system here
        console.log('Success:', message);
    }

    function showErrorMessage(message) {
        // You can implement a toast or alert system here
        console.error('Error:', message);
    }

    // Reset modal when closed
    $('#scheduleModal').on('hidden.bs.modal', function () {
        editingScheduleId = null;
        $('#scheduleModalLabel').text('Add New Schedule');

        const form = $('#scheduleForm');
        form.attr('action', '{{ route("instructor.schedules.store") }}');
        form[0].reset();

        // Reset method to POST
        $('#methodField').val('POST');

        // Reset calendar
        clearScheduleDateSelection();

        // Remove validation classes
        $('.is-invalid').removeClass('is-invalid');
        $('.invalid-feedback').remove();
        $('.calendar-widget').removeClass('border-danger');
    });



    // Reset material form modal when closed
    $('#materialFormModal').on('hidden.bs.modal', function () {
        $('#materialForm')[0].reset();
        $('#materialMethodField').val('POST');
        $('#materialFormModalLabel').text('Add Material');
        $('.is-invalid').removeClass('is-invalid');
        $('.invalid-feedback').remove();
        currentMaterialId = null;
    });

    // Material form submission
    $('#materialForm').on('submit', function(e) {
        e.preventDefault();

        const formData = new FormData(this);
        const submitBtn = $(this).find('button[type="submit"]');
        const originalText = submitBtn.html();

        // Show loading state
        submitBtn.html('<i class="fas fa-spinner fa-spin me-2"></i>Saving...').prop('disabled', true);

        // Submit form via fetch
        fetch($(this).attr('action'), {
            method: 'POST',
            body: formData,
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                $('#materialFormModal').modal('hide');
                loadMaterials(currentScheduleId);
                showSuccessMessage('Material saved successfully');
            } else {
                showErrorMessage(data.message || 'Failed to save material');
            }
        })
        .catch(error => {
            console.error('Error saving material:', error);
            showErrorMessage('Failed to save material');
        })
        .finally(() => {
            // Restore button state
            submitBtn.html(originalText).prop('disabled', false);
        });
    });

    // Form validation and submission
    $('#scheduleForm').on('submit', function(e) {
        e.preventDefault();

        let isValid = true;

        // Remove previous validation classes
        $('.is-invalid').removeClass('is-invalid');
        $('.invalid-feedback').remove();

        // Validate required fields individually
        const classId = $('#class_id').val();
        const title = $('#title').val();
        const selectedDate = $('#selected_date_schedule').val();
        const startTime = $('#start_time').val();
        const endTime = $('#end_time').val();

        if (!classId || classId.trim() === '') {
            $('#class_id').addClass('is-invalid');
            $('#class_id').after('<div class="invalid-feedback">Please select a class.</div>');
            isValid = false;
        }

        if (!title || title.trim() === '') {
            $('#title').addClass('is-invalid');
            $('#title').after('<div class="invalid-feedback">Please enter a schedule title.</div>');
            isValid = false;
        }

        if (!selectedDate || selectedDate.trim() === '') {
            $('.calendar-widget').addClass('border-danger');
            $('.calendar-widget').after('<div class="invalid-feedback d-block">Please select a date from the calendar.</div>');
            isValid = false;
        } else {
            $('.calendar-widget').removeClass('border-danger');
        }

        if (!startTime || startTime.trim() === '') {
            $('#start_time').addClass('is-invalid');
            $('#start_time').after('<div class="invalid-feedback">Please enter start time.</div>');
            isValid = false;
        }

        if (!endTime || endTime.trim() === '') {
            $('#end_time').addClass('is-invalid');
            $('#end_time').after('<div class="invalid-feedback">Please enter end time.</div>');
            isValid = false;
        }

        // Validate time logic
        if (startTime && endTime && endTime <= startTime) {
            $('#end_time').addClass('is-invalid');
            $('#end_time').after('<div class="invalid-feedback">End time must be after start time.</div>');
            isValid = false;
        }

        if (!isValid) {
            // Focus on first invalid field
            $('.is-invalid').first().focus();
            return;
        }

        // Debug: Log form data before submission
        console.log('Form validation passed. Form data:', {
            class_id: classId,
            title: title,
            date: selectedDate,
            start_time: startTime,
            end_time: endTime,
            notes: $('#notes').val()
        });

        // Show loading state
        const submitBtn = $(this).find('button[type="submit"]');
        const originalText = submitBtn.html();
        submitBtn.html('<i class="fas fa-spinner fa-spin me-2"></i>Saving...').prop('disabled', true);

        // Submit form
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

    // Handle delete form submission with confirmation
    $('#deleteForm').on('submit', function(e) {
        e.preventDefault();

        const submitBtn = $(this).find('button[type="submit"]');
        const originalText = submitBtn.html();
        submitBtn.html('<i class="fas fa-spinner fa-spin me-2"></i>Deleting...').prop('disabled', true);

        // Submit after short delay to show loading state
        setTimeout(() => {
            this.submit();
        }, 500);
    });

    // Initialize Schedule Calendar
    initializeScheduleCalendar();

    function initializeScheduleCalendar() {
        const currentDate = new Date();
        let currentMonth = currentDate.getMonth();
        let currentYear = currentDate.getFullYear();

        const monthNames = ["January", "February", "March", "April", "May", "June",
            "July", "August", "September", "October", "November", "December"
        ];

        const dayNames = ["Sun", "Mon", "Tue", "Wed", "Thu", "Fri", "Sat"];

        function renderScheduleCalendar() {
            const firstDay = new Date(currentYear, currentMonth, 1).getDay();
            const daysInMonth = new Date(currentYear, currentMonth + 1, 0).getDate();
            const today = new Date();

            $('#currentMonthSchedule').text(`${monthNames[currentMonth]} ${currentYear}`);

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
                const dateStr = `${currentYear}-${String(currentMonth + 1).padStart(2, '0')}-${String(day).padStart(2, '0')}`;

                let classes = ['calendar-day'];

                // Mark today
                if (date.toDateString() === today.toDateString()) {
                    classes.push('today');
                }

                // Mark past dates (but allow them for editing)
                if (date < today && !classes.includes('today')) {
                    classes.push('past-date');
                }

                calendarHTML += `<div class="${classes.join(' ')}" data-date="${dateStr}">${day}</div>`;
            }

            $('#calendarGridSchedule').html(calendarHTML);
        }

        // Event handlers
        $('#prevMonthSchedule').click(function() {
            currentMonth--;
            if (currentMonth < 0) {
                currentMonth = 11;
                currentYear--;
            }
            renderScheduleCalendar();
        });

        $('#nextMonthSchedule').click(function() {
            currentMonth++;
            if (currentMonth > 11) {
                currentMonth = 0;
                currentYear++;
            }
            renderScheduleCalendar();
        });

        $(document).on('click', '#calendarGridSchedule .calendar-day:not(.other-month)', function() {
            const dateStr = $(this).data('date');
            if (!dateStr) return;

            console.log('Calendar date selected:', dateStr); // Debug log

            $('#calendarGridSchedule .calendar-day').removeClass('selected');
            $(this).addClass('selected');
            $('#selected_date_schedule').val(dateStr);

            console.log('Hidden input value set to:', $('#selected_date_schedule').val()); // Debug log

            // Show selected date info
            const formattedDate = new Date(dateStr).toLocaleDateString('en-US', {
                weekday: 'long',
                year: 'numeric',
                month: 'long',
                day: 'numeric'
            });

            $('#dateDetailsSchedule').html(`
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <strong>${formattedDate}</strong>
                    </div>
                    <button type="button" class="btn btn-sm btn-outline-danger" onclick="clearScheduleDateSelection()">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            `);
            $('#selectedDateInfoSchedule').show();
        });

        // Initial render
        renderScheduleCalendar();
    }

    window.clearScheduleDateSelection = function() {
        $('#selected_date_schedule').val('');
        $('#selectedDateInfoSchedule').hide();
        $('#calendarGridSchedule .calendar-day').removeClass('selected');
    }

});
</script>
@endpush
