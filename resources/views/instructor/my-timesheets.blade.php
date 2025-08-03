@extends('instructor.layouts.app')

@section('title', 'Timesheet Management')
@section('page-title', 'Timesheet Management')
@section('page-subtitle', 'Manage your monthly timesheets and track your working hours')

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
                        <h6 class="text-muted mb-1">Total Timesheets</h6>
                        <h4 class="mb-0">{{ $stats['total'] }}</h4>
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
                            <i class="fas fa-check-circle text-success fa-lg"></i>
                        </div>
                    </div>
                    <div class="flex-grow-1 ms-3">
                        <h6 class="text-muted mb-1">Approved</h6>
                        <h4 class="mb-0">{{ $stats['approved'] }}</h4>
                        <small class="text-success">{{ $stats['completion_rate'] }}% completion rate</small>
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
                        <h6 class="text-muted mb-1">Total Hours ({{ date('Y') }})</h6>
                        <h4 class="mb-0">{{ $stats['total_hours_year'] }}h</h4>
                        <small class="text-muted">Avg: {{ $stats['average_monthly'] }}h/month</small>
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
                            <i class="fas fa-dollar-sign text-info fa-lg"></i>
                        </div>
                    </div>
                    <div class="flex-grow-1 ms-3">
                        <h6 class="text-muted mb-1">Estimated Earnings</h6>
                        <h4 class="mb-0">${{ number_format($stats['estimated_earnings'], 2) }}</h4>
                        <small class="text-muted">{{ $stats['pending'] }} pending</small>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Header with Actions -->
<div class="row mb-4">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h4 class="mb-0">Timesheet Management</h4>
                <p class="text-muted mb-0">Generate timesheets automatically from your log hours</p>
            </div>
            <div class="d-flex gap-2">
                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#generateModal">
                    <i class="fas fa-magic me-2"></i>Generate/Update from Log Hours
                </button>
            </div>
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

<!-- Quick Actions for Monthly Breakdown -->
<div class="card border-0 shadow-sm mb-4">
    <div class="card-header bg-light border-0">
        <h6 class="mb-0"><i class="fas fa-calendar-check me-2"></i>{{ date('Y') }} Monthly Overview</h6>
    </div>
    <div class="card-body">
        <div class="row g-2">
            @foreach($monthlyBreakdown as $month)
                <div class="col-md-2">
                    <div class="card border {{ $month['has_timesheet'] ? ($month['can_update'] && $month['hours_different'] ? 'border-info' : 'border-success') : ($month['can_generate'] ? 'border-warning' : 'border-light') }}">
                        <div class="card-body p-2 text-center">
                            <h6 class="mb-1">{{ $month['month'] }}</h6>
                            @if($month['has_timesheet'])
                                <span class="badge bg-success-subtle text-success mb-1">
                                    {{ ucfirst($month['status']) }}
                                </span>
                                <div class="small text-muted">{{ $month['timesheet_hours'] }}h</div>
                                @if($month['can_update'] && $month['hours_different'])
                                    <a href="{{ route('instructor.timesheets.quick-generate', $month['month_key']) }}"
                                       class="btn btn-sm btn-outline-info mb-1"
                                       onclick="return confirm('Update timesheet for {{ $month['month'] }}? New hours: {{ round($month['log_hours'], 1) }}h')"
                                       title="Log hours updated: {{ round($month['log_hours'], 1) }}h">
                                        <i class="fas fa-sync"></i>
                                    </a>
                                @endif
                            @elseif($month['can_generate'])
                                <a href="{{ route('instructor.timesheets.quick-generate', $month['month_key']) }}"
                                   class="btn btn-sm btn-outline-warning mb-1"
                                   onclick="return confirm('Generate timesheet for {{ $month['month'] }}?')">
                                    <i class="fas fa-plus"></i>
                                </a>
                                <div class="small text-muted">{{ round($month['log_hours'], 1) }}h available</div>
                            @else
                                <span class="badge bg-light text-muted mb-1">No Data</span>
                                <div class="small text-muted">-</div>
                            @endif
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</div>

<!-- Filters -->
<div class="card border-0 shadow-sm mb-4">
    <div class="card-body">
        <form method="GET" action="{{ route('instructor.timesheets.index') }}">
            <div class="row g-3">
                <div class="col-md-3">
                    <label class="form-label text-muted">Search</label>
                    <div class="input-group">
                        <span class="input-group-text bg-light border-end-0">
                            <i class="fas fa-search text-muted"></i>
                        </span>
                        <input type="text" class="form-control border-start-0" name="search"
                               value="{{ request('search') }}" placeholder="Search timesheets...">
                    </div>
                </div>
                <div class="col-md-3">
                    <label class="form-label text-muted">Month</label>
                    <select class="form-select" name="month">
                        <option value="">All Months</option>
                        @foreach($availableMonths as $month)
                            <option value="{{ $month['value'] }}" {{ request('month') == $month['value'] ? 'selected' : '' }}>
                                {{ $month['label'] }}
                                @if($month['has_data']) <span class="text-success">●</span> @endif
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label text-muted">Year</label>
                    <select class="form-select" name="year">
                        <option value="">All Years</option>
                        @foreach($availableYears as $year)
                            <option value="{{ $year['value'] }}" {{ request('year') == $year['value'] ? 'selected' : '' }}>
                                {{ $year['label'] }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label text-muted">Status</label>
                    <select class="form-select" name="status">
                        <option value="">All Status</option>
                        <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>Pending</option>
                        <option value="approved" {{ request('status') == 'approved' ? 'selected' : '' }}>Approved</option>
                        <option value="rejected" {{ request('status') == 'rejected' ? 'selected' : '' }}>Rejected</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label text-muted">&nbsp;</label>
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-outline-primary">
                            <i class="fas fa-filter"></i>
                        </button>
                        <a href="{{ route('instructor.timesheets.index') }}" class="btn btn-outline-secondary">
                            <i class="fas fa-times"></i>
                        </a>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Timesheets Table with DataTables -->
<div class="card border-0 shadow-sm">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table id="timesheetsTable" class="table table-hover mb-0">
                <thead class="bg-light">
                    <tr>
                        <th class="border-0 px-4 py-3">Month</th>
                        <th class="border-0 py-3">Total Hours</th>
                        <th class="border-0 py-3">Status</th>
                        <th class="border-0 py-3">Submitted Date</th>
                        <th class="border-0 py-3">Approved By</th>
                        <th class="border-0 py-3">Estimated Earnings</th>
                        <th class="border-0 py-3">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($timesheets as $timesheet)
                    <tr>
                        <td class="px-4 py-3">
                            <div>
                                <h6 class="mb-1">{{ Carbon\Carbon::createFromFormat('Y-m', $timesheet->month)->format('F Y') }}</h6>
                                <small class="text-muted">{{ $timesheet->month }}</small>
                            </div>
                        </td>
                        <td class="py-3">
                            <span class="fw-semibold">{{ number_format($timesheet->total_hours, 2) }}h</span>
                            @php
                                $avgDaily = $timesheet->total_hours / Carbon\Carbon::createFromFormat('Y-m', $timesheet->month)->daysInMonth;
                            @endphp
                            <small class="text-muted d-block">{{ number_format($avgDaily, 1) }}h/day avg</small>
                        </td>
                        <td class="py-3">
                            @if($timesheet->status == 'approved')
                                <span class="badge bg-success-subtle text-success">
                                    <i class="fas fa-check me-1"></i>Approved
                                </span>
                            @elseif($timesheet->status == 'pending')
                                <span class="badge bg-warning-subtle text-warning">
                                    <i class="fas fa-clock me-1"></i>Pending
                                </span>
                            @else
                                <span class="badge bg-danger-subtle text-danger">
                                    <i class="fas fa-times me-1"></i>Rejected
                                </span>
                            @endif
                        </td>
                        <td class="py-3">
                            <div class="small">
                                <div>{{ $timesheet->created_at->format('M d, Y') }}</div>
                                <div class="text-muted">{{ $timesheet->created_at->format('H:i') }}</div>
                            </div>
                        </td>
                        <td class="py-3">
                            @if($timesheet->approved_by)
                                <span class="small">{{ $timesheet->approved_by }}</span>
                            @else
                                <span class="text-muted">-</span>
                            @endif
                        </td>
                        <td class="py-3">
                            @php
                                $payrate = auth()->user()->instructor->payrate ?? 50000;
                                $earnings = $timesheet->total_hours * ($payrate / 100);
                            @endphp
                            <span class="fw-semibold">${{ number_format($earnings, 2) }}</span>
                            <small class="text-muted d-block">${{ number_format($payrate / 100, 2) }}/hour</small>
                        </td>
                        <td class="py-3">
                            <div class="btn-group" role="group">
                                @if(in_array($timesheet->status, ['pending', 'rejected']))
                                    <button type="button" class="btn btn-sm btn-outline-danger"
                                            onclick="deleteTimesheet({{ $timesheet->id }})"
                                            title="Delete Timesheet">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                @else
                                    <button type="button" class="btn btn-sm btn-outline-secondary" disabled title="Approved - Cannot Delete">
                                        <i class="fas fa-lock"></i>
                                    </button>
                                @endif
                                <button type="button" class="btn btn-sm btn-outline-info"
                                        onclick="viewTimesheetDetails({{ $timesheet->id }})"
                                        title="View Details">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="text-center py-5">
                            <div class="py-4">
                                <i class="fas fa-calendar-times fa-3x text-muted mb-3"></i>
                                <h5 class="text-muted">No timesheets found</h5>
                                <p class="text-muted mb-3">Start by generating your first timesheet from your log hours.</p>
                                <div class="d-flex gap-2 justify-content-center">
                                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#generateModal">
                                        <i class="fas fa-magic me-2"></i>Generate/Update from Log Hours
                                    </button>
                                </div>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>



<!-- Modal for Generate from Log Hours -->
<div class="modal fade" id="generateModal" tabindex="-1" aria-labelledby="generateModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content border-0 shadow">
            <form id="generateForm" method="POST" action="{{ route('instructor.timesheets.generate') }}">
                @csrf

                <div class="modal-header border-0 pb-0">
                    <div>
                        <h5 class="modal-title" id="generateModalLabel">Generate/Update from Log Hours</h5>
                        <p class="text-muted mb-0 small">Create new timesheet or update existing one with latest log hours</p>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <div class="modal-body">
                    <div class="mb-3">
                        <label for="generate_month" class="form-label">Select Month <span class="text-danger">*</span></label>
                        <select class="form-select" id="generate_month" name="month" required>
                            <option value="">Choose month to generate or update</option>
                            @foreach($monthlyBreakdown as $month)
                                @if($month['can_generate'])
                                    <option value="{{ $month['month_key'] }}">
                                        {{ $month['month'] }} {{ date('Y') }}
                                        @if($month['has_timesheet'])
                                            (Update: {{ round($month['log_hours'], 1) }}h available)
                                        @else
                                            (Generate: {{ round($month['log_hours'], 1) }}h available)
                                        @endif
                                    </option>
                                @endif
                            @endforeach
                        </select>
                    </div>

                    <div id="generatePreview" class="alert alert-info" style="display: none;">
                        <h6><i class="fas fa-calculator me-2"></i>Generation Preview</h6>
                        <div class="row">
                            <div class="col-6">
                                <strong>Total Hours:</strong><br>
                                <span id="previewHours" class="h5 text-primary">0</span> hours
                            </div>
                            <div class="col-6">
                                <strong>Working Days:</strong><br>
                                <span id="previewDays" class="h5 text-info">0</span> days
                            </div>
                        </div>
                        <hr>
                        <div class="row">
                            <div class="col-6">
                                <strong>Average/Day:</strong><br>
                                <span id="previewAvg">0</span> hours
                            </div>
                            <div class="col-6">
                                <strong>Estimated Earnings:</strong><br>
                                $<span id="previewEarnings">0</span>
                            </div>
                        </div>
                    </div>

                    <div class="alert alert-success">
                        <i class="fas fa-magic me-2"></i>
                        <strong>Auto-Generation/Update Benefits:</strong>
                        <ul class="mb-0 mt-2">
                            <li>Accurate calculation from actual clock-in/out times</li>
                            <li>Eliminates manual entry errors</li>
                            <li>Includes all logged working sessions</li>
                            <li>Updates existing timesheet with new log hours</li>
                            <li>Instant timesheet creation or update</li>
                        </ul>
                    </div>
                </div>

                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-magic me-2"></i>Generate/Update Timesheet
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
                        <i class="fas fa-exclamation-triangle me-2"></i>Delete Timesheet
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
                        <p class="mb-0">Are you sure you want to delete this timesheet? This will permanently remove the timesheet and you'll need to recreate it.</p>
                    </div>
                </div>
            </div>
            <div class="modal-footer border-0 pt-0">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                <form id="deleteForm" method="POST" style="display: inline;">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-danger">
                        <i class="fas fa-trash me-2"></i>Delete Timesheet
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

@endsection

@push('styles')
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
.text-success {
    color: #198754 !important;
}
.text-warning {
    color: #ffc107 !important;
}
.text-danger {
    color: #dc3545 !important;
}
.border-info {
    border-color: #0dcaf0 !important;
}
.card {
    transition: all 0.3s ease;
}
.card:hover {
    transform: translateY(-2px);
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
</style>
@endpush

@push('scripts')
<script>
$(document).ready(function() {
    // Initialize DataTables
    const table = $('#timesheetsTable').DataTable({
        responsive: true,
        pageLength: 15,
        lengthMenu: [[15, 25, 50, -1], [15, 25, 50, "All"]],
        order: [[0, 'desc']], // Sort by month descending
        columnDefs: [
            {
                targets: -1, // Actions column
                orderable: false,
                searchable: false
            },
            {
                targets: [1], // Hours column
                type: 'num'
            }
        ],
        language: {
            search: "Search timesheets:",
            lengthMenu: "Show _MENU_ timesheets per page",
            info: "Showing _START_ to _END_ of _TOTAL_ timesheets",
            infoEmpty: "No timesheets available",
            infoFiltered: "(filtered from _MAX_ total timesheets)",
            emptyTable: "No timesheets found. Create your first timesheet!",
            zeroRecords: "No matching timesheets found"
        },
        dom: '<"row"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6"f>>' +
             '<"row"<"col-sm-12"tr>>' +
             '<"row"<"col-sm-12 col-md-5"i><"col-sm-12 col-md-7"p>>',
    });

    // Timesheet data for display
    const timesheetData = @json($timesheets);
    const monthlyBreakdown = @json($monthlyBreakdown);

    // Delete timesheet function
    window.deleteTimesheet = function(timesheetId) {
        const deleteUrl = `/instructor/timesheets/${timesheetId}`;
        $('#deleteForm').attr('action', deleteUrl);

        $('#deleteModal').modal('show');
    };

    // View timesheet details function
    window.viewTimesheetDetails = function(timesheetId) {
        const timesheetItem = timesheetData.find(t => t.id === timesheetId);

        if (!timesheetItem) {
            return;
        }

        // Create a simple details modal or redirect to details page
        alert(`Timesheet Details:\nMonth: ${timesheetItem.month}\nHours: ${timesheetItem.total_hours}\nStatus: ${timesheetItem.status}\nCreated: ${timesheetItem.created_at}`);
    };



    // Generate month selection change handler
    $('#generate_month').on('change', function() {
        const selectedMonth = $(this).val();

        if (!selectedMonth) {
            $('#generatePreview').hide();
            return;
        }

        const monthData = monthlyBreakdown.find(m => m.month_key === selectedMonth);

        if (monthData && monthData.log_hours > 0) {
            const hours = monthData.log_hours;
            const payrate = {{ auth()->user()->instructor->payrate ?? 50000 }} / 100;
            const earnings = hours * payrate;

            // Calculate working days (assuming from log hours data)
            const workingDays = Math.ceil(hours / 8); // Rough estimate
            const avgDaily = workingDays > 0 ? hours / workingDays : 0;

            $('#previewHours').text(hours.toFixed(2));
            $('#previewDays').text(workingDays);
            $('#previewAvg').text(avgDaily.toFixed(1));
            $('#previewEarnings').text(earnings.toFixed(2));
            $('#generatePreview').show();
        } else {
            $('#generatePreview').hide();
        }
    });

    // Reset modal when closed
    $('#generateModal').on('hidden.bs.modal', function () {
        $('#generateForm')[0].reset();
        $('#generatePreview').hide();
    });



    // Generate form validation
    $('#generateForm').on('submit', function(e) {
        e.preventDefault();

        const month = $('#generate_month').val();
        if (!month) {
            alert('Please select a month to generate timesheet.');
            return;
        }

        // Show loading state
        const submitBtn = $(this).find('button[type="submit"]');
        const originalText = submitBtn.html();
        submitBtn.html('<i class="fas fa-spinner fa-spin me-2"></i>Generating...').prop('disabled', true);

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
        if (alert.hasClass('alert-dismissible')) {
            setTimeout(function() {
                alert.fadeOut('slow');
            }, 5000);
        }
    });


    // Handle form submission errors
    $(document).ajaxError(function(event, xhr, settings) {
        if (xhr.status === 419) {
            alert('Your session has expired. Please refresh the page and try again.');
            location.reload();
        }
    });

    // Quick action handlers for monthly breakdown
    $('.btn-outline-warning').on('click', function(e) {
        e.preventDefault();
        const href = $(this).attr('href');
        const month = href.split('/').pop();
        const monthName = $(this).closest('.card-body').find('h6').text();

        if (confirm(`Generate timesheet for ${monthName}?`)) {
            window.location.href = href;
        }
    });

    // Enhanced search functionality
    $('#timesheetsTable_filter input').attr('placeholder', 'Search by month, status, hours...');

    // Add custom search functionality
    $.fn.dataTable.ext.search.push(
        function(settings, data, dataIndex) {
            const searchTerm = $('.dataTables_filter input').val().toLowerCase();

            if (!searchTerm) return true;

            // Search in month, status, hours, and earnings
            const month = data[0].toLowerCase();
            const hours = data[1].toLowerCase();
            const status = data[2].toLowerCase();
            const earnings = data[5].toLowerCase();

            return month.includes(searchTerm) ||
                   hours.includes(searchTerm) ||
                   status.includes(searchTerm) ||
                   earnings.includes(searchTerm);
        }
    );
});
</script>
@endpush


