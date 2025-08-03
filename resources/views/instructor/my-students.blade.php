@extends('instructor.layouts.app')

@section('title', 'My Students')
@section('page-title', 'My Students')
@section('page-subtitle', 'Manage your students')

@section('content')
<!-- Statistics Cards -->
<div class="row mb-4">
    <div class="col-xl-3 col-md-6">
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="flex-shrink-0">
                        <div class="bg-primary bg-opacity-10 rounded-3 p-3">
                            <i class="fas fa-users text-primary fa-lg"></i>
                        </div>
                    </div>
                    <div class="flex-grow-1 ms-3">
                        <h6 class="text-muted mb-1">Total Students</h6>
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
                            <i class="fas fa-user-check text-success fa-lg"></i>
                        </div>
                    </div>
                    <div class="flex-grow-1 ms-3">
                        <h6 class="text-muted mb-1">Active Students</h6>
                        <h4 class="mb-0">{{ $stats['active'] }}</h4>
                        <small class="text-muted">Last 30 days</small>
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
                            <i class="fas fa-user text-info fa-lg"></i>
                        </div>
                    </div>
                    <div class="flex-grow-1 ms-3">
                        <h6 class="text-muted mb-1">Private Classes</h6>
                        <h4 class="mb-0">{{ $stats['private_classes'] ?? 0 }}</h4>
                        <small class="text-muted">Students</small>
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
                            <i class="fas fa-users text-warning fa-lg"></i>
                        </div>
                    </div>
                    <div class="flex-grow-1 ms-3">
                        <h6 class="text-muted mb-1">Group Classes</h6>
                        <h4 class="mb-0">{{ $stats['group_classes'] ?? 0 }}</h4>
                        <small class="text-muted">Students</small>
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
                <h4 class="mb-0">Student Management</h4>
                <p class="text-muted mb-0">Manage students enrolled in your classes</p>
            </div>
            <div class="btn-group" role="group">
                <button type="button" class="btn btn-outline-primary" onclick="exportStudents()">
                    <i class="fas fa-download me-2"></i>Export
                </button>
                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#bulkActionModal">
                    <i class="fas fa-cogs me-2"></i>Bulk Actions
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

<!-- Filters -->
<div class="card border-0 shadow-sm mb-4">
    <div class="card-body">
        <form method="GET" action="{{ route('instructor.students.index') }}">
            <div class="row g-3">
                <div class="col-md-3">
                    <label class="form-label text-muted">Search</label>
                    <div class="input-group">
                        <span class="input-group-text bg-light border-end-0">
                            <i class="fas fa-search text-muted"></i>
                        </span>
                        <input type="text" class="form-control border-start-0" name="search"
                               value="{{ request('search') }}" placeholder="Search students...">
                    </div>
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
                    <label class="form-label text-muted">Category</label>
                    <select class="form-select" name="category_id">
                        <option value="">All Categories</option>
                        @foreach($categories as $category)
                            <option value="{{ $category->id }}" {{ request('category_id') == $category->id ? 'selected' : '' }}>
                                {{ $category->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label text-muted">Type</label>
                    <select class="form-select" name="type_id">
                        <option value="">All Types</option>
                        @foreach($types as $type)
                            <option value="{{ $type->id }}" {{ request('type_id') == $type->id ? 'selected' : '' }}>
                                {{ $type->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label text-muted">Status</label>
                    <select class="form-select" name="status">
                        <option value="">All Status</option>
                        <option value="active" {{ request('status') == 'active' ? 'selected' : '' }}>Active</option>
                        <option value="inactive" {{ request('status') == 'inactive' ? 'selected' : '' }}>Inactive</option>
                    </select>
                </div>
                <div class="col-md-1">
                    <label class="form-label text-muted">&nbsp;</label>
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-outline-primary">
                            <i class="fas fa-filter"></i>
                        </button>
                        <a href="{{ route('instructor.students.index') }}" class="btn btn-outline-secondary">
                            <i class="fas fa-times"></i>
                        </a>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Students Table with DataTables -->
<div class="card border-0 shadow-sm">
    <div class="card-body">
        <div class="table-responsive">
            <table id="studentsTable" class="table table-hover">
                <thead class="bg-light">
                    <tr>
                        <th>
                            <input type="checkbox" id="selectAll" class="form-check-input">
                        </th>
                        <th>Student Details</th>
                        <th>Contact Info</th>
                        <th>Enrolled Classes</th>
                        <th>Class Types</th>
                        <th>Family</th>
                        <th>Attendance</th>
                        <th>Joined Date</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($students as $student)
                    <tr>
                        <td>
                            <input type="checkbox" class="form-check-input student-checkbox" value="{{ $student->id }}">
                        </td>
                        <td>
                            <div class="d-flex align-items-center">
                                <div class="avatar-sm bg-primary rounded-circle d-flex align-items-center justify-content-center me-3">
                                    <i class="fas fa-user text-white"></i>
                                </div>
                                <div>
                                    <h6 class="mb-1">{{ $student->first_name }} {{ $student->last_name }}</h6>
                                    <small class="text-muted">ID: {{ $student->id }}</small>
                                </div>
                            </div>
                        </td>
                        <td>
                            <div class="small">
                                @if($student->family)
                                <div class="small">
                                    <div class="text-muted">{{ $student->family->phone ?? 'N/A' }}</div>
                                </div>
                                @else
                                    <span class="text-muted">N/A</span>
                                @endif
                            </div>
                        </td>
                        <td>
                            @foreach($student->activeClasses as $class)
                                <span class="badge bg-info-subtle text-info me-1 mb-1">
                                    {{ $class->name }}
                                </span>
                            @endforeach
                        </td>
                        <td>
                            @php
                                $classTypes = $student->activeClasses->pluck('type.name')->unique();
                            @endphp
                            @foreach($classTypes as $type)
                                @if($type === 'Private')
                                    <span class="badge bg-success-subtle text-success me-1">
                                        <i class="fas fa-user me-1"></i>{{ $type }}
                                    </span>
                                @elseif($type === 'Group')
                                    <span class="badge bg-warning-subtle text-warning me-1">
                                        <i class="fas fa-users me-1"></i>{{ $type }}
                                    </span>
                                @else
                                    <span class="badge bg-light text-dark me-1">{{ $type }}</span>
                                @endif
                            @endforeach
                        </td>
                        <td>
                            @if($student->family)
                                <div class="small">
                                    <div>{{ $student->family->guardians_name }}</div>
                                    <div class="text-muted">{{ $student->family->email ?? 'N/A' }}</div>
                                </div>
                            @else
                                <span class="text-muted">N/A</span>
                            @endif
                        </td>
                        <td>
                            @php
                                $attendanceCount = $student->attendances->count();
                                $totalSessions = $student->activeClasses->sum(function($class) {
                                    return $class->schedules()->count();
                                });
                                $attendanceRate = $totalSessions > 0 ? round(($attendanceCount / $totalSessions) * 100, 1) : 0;
                            @endphp
                            <div class="small">
                                <div class="fw-semibold">{{ $attendanceRate }}%</div>
                                <div class="text-muted">{{ $attendanceCount }}/{{ $totalSessions }} sessions</div>
                            </div>
                        </td>
                        <td>
                            <div class="small">
                                @if($student->created_at)
                                    <div>{{ $student->created_at->format('M d, Y') }}</div>
                                    <div class="text-muted">{{ $student->created_at->diffForHumans() }}</div>
                                @else
                                    <div>N/A</div>
                                    <div class="text-muted">Unknown</div>
                                @endif
                            </div>
                        </td>
                        <td>
                            <div class="btn-group" role="group">
                                <button type="button" class="btn btn-sm btn-outline-primary"
                                        onclick="viewStudent({{ $student->id }})"
                                        title="View Details">
                                    <i class="fas fa-eye"></i>
                                </button>
                                <button type="button" class="btn btn-sm btn-outline-success"
                                        onclick="viewAttendance({{ $student->id }})"
                                        title="View Attendance">
                                    <i class="fas fa-calendar-check"></i>
                                </button>
                                <button type="button" class="btn btn-sm btn-outline-info"
                                        onclick="sendMessage({{ $student->id }})"
                                        title="Send Message">
                                    <i class="fas fa-envelope"></i>
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

<!-- Student Detail Modal -->
<div class="modal fade" id="studentModal" tabindex="-1" aria-labelledby="studentModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content border-0 shadow">
            <div class="modal-header border-0 pb-0">
                <div>
                    <h5 class="modal-title" id="studentModalLabel">Student Details</h5>
                    <p class="text-muted mb-0 small">Complete student information and progress</p>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="studentModalBody">
                <div class="text-center py-4">
                    <i class="fas fa-spinner fa-spin fa-2x text-muted"></i>
                    <p class="text-muted mt-2">Loading student details...</p>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Bulk Action Modal -->
<div class="modal fade" id="bulkActionModal" tabindex="-1" aria-labelledby="bulkActionModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content border-0 shadow">
            <div class="modal-header border-0 pb-0">
                <div>
                    <h5 class="modal-title" id="bulkActionModalLabel">Bulk Actions</h5>
                    <p class="text-muted mb-0 small">Perform actions on selected students</p>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label">Select Action</label>
                    <select class="form-select" id="bulkAction">
                        <option value="">Choose action...</option>
                        <option value="export">Export Selected</option>
                        <option value="send_message">Send Message</option>
                        <option value="mark_attendance">Mark Attendance</option>
                    </select>
                </div>
                <div id="bulkActionContent"></div>
            </div>
            <div class="modal-footer border-0 pt-0">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" onclick="executeBulkAction()">
                    <i class="fas fa-check me-2"></i>Execute Action
                </button>
            </div>
        </div>
    </div>
</div>

@endsection

@push('styles')
<style>
.bg-info-subtle {
    background-color: rgba(13, 202, 240, 0.1) !important;
}
.bg-success-subtle {
    background-color: rgba(25, 135, 84, 0.1) !important;
}
.bg-warning-subtle {
    background-color: rgba(255, 193, 7, 0.1) !important;
}
.text-info {
    color: #0dcaf0 !important;
}
.text-success {
    color: #198754 !important;
}
.text-warning {
    color: #ffc107 !important;
}
.avatar-sm {
    width: 40px;
    height: 40px;
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
.badge {
    font-weight: 500;
    padding: 0.5em 0.75em;
}
</style>
@endpush

@push('scripts')
<script>
$(document).ready(function() {
    // Initialize DataTables
    const table = $('#studentsTable').DataTable({
        responsive: true,
        pageLength: 10,
        lengthMenu: [[10, 25, 50, -1], [10, 25, 50, "All"]],
        order: [[1, 'asc']],
        columnDefs: [
            {
                targets: [0, -1], // Checkbox and Actions columns
                orderable: false,
                searchable: false
            }
        ],
        language: {
            search: "Search students:",
            lengthMenu: "Show _MENU_ students per page",
            info: "Showing _START_ to _END_ of _TOTAL_ students",
            infoEmpty: "No students available",
            infoFiltered: "(filtered from _MAX_ total students)",
            emptyTable: "No students found in your classes",
            zeroRecords: "No matching students found"
        },
        dom: '<"row"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6"f>>' +
             '<"row"<"col-sm-12"tr>>' +
             '<"row"<"col-sm-12 col-md-5"i><"col-sm-12 col-md-7"p>>',
    });

    // Select All functionality
    $('#selectAll').on('change', function() {
        $('.student-checkbox').prop('checked', this.checked);
        updateBulkActionButton();
    });

    $('.student-checkbox').on('change', function() {
        updateBulkActionButton();

        // Update select all checkbox
        const totalCheckboxes = $('.student-checkbox').length;
        const checkedCheckboxes = $('.student-checkbox:checked').length;

        $('#selectAll').prop('indeterminate', checkedCheckboxes > 0 && checkedCheckboxes < totalCheckboxes);
        $('#selectAll').prop('checked', checkedCheckboxes === totalCheckboxes);
    });

    function updateBulkActionButton() {
        const selectedCount = $('.student-checkbox:checked').length;
        const bulkBtn = $('[data-bs-target="#bulkActionModal"]');

        if (selectedCount > 0) {
            bulkBtn.html(`<i class="fas fa-cogs me-2"></i>Bulk Actions (${selectedCount})`);
            bulkBtn.removeClass('btn-primary').addClass('btn-warning');
        } else {
            bulkBtn.html('<i class="fas fa-cogs me-2"></i>Bulk Actions');
            bulkBtn.removeClass('btn-warning').addClass('btn-primary');
        }
    }

    // View student function
    window.viewStudent = function(studentId) {
        $('#studentModalBody').html(`
            <div class="text-center py-4">
                <i class="fas fa-spinner fa-spin fa-2x text-muted"></i>
                <p class="text-muted mt-2">Loading student details...</p>
            </div>
        `);

        $('#studentModal').modal('show');

                // Fetch student details via AJAX
                $.get(`{{ route('instructor.students.index') }}/${studentId}`)
            .done(function(response) {
                const student = response.student;
                const classes = response.classes;
                const attendances = response.recent_attendances;

                $('#studentModalBody').html(`
                    <div class="row">
                        <!-- Student Info -->
                        <div class="col-md-4">
                            <div class="card border-0 bg-light">
                                <div class="card-body text-center">
                                    <div class="avatar-lg bg-primary rounded-circle d-flex align-items-center justify-content-center mx-auto mb-3">
                                        <i class="fas fa-user text-white fa-2x"></i>
                                    </div>
                                    <h5 class="mb-1">${student.first_name} ${student.last_name}</h5>
                                    <p class="text-muted mb-3">Student ID: ${student.id}</p>

                                    <div class="row text-center">
                                        <div class="col-4">
                                            <div class="fw-bold text-primary">${response.total_classes}</div>
                                            <small class="text-muted">Classes</small>
                                        </div>
                                        <div class="col-4">
                                            <div class="fw-bold text-success">${response.attendance_rate}%</div>
                                            <small class="text-muted">Attendance</small>
                                        </div>
                                        <div class="col-4">
                                            <div class="fw-bold text-info">${attendances.length}</div>
                                            <small class="text-muted">Sessions</small>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Contact Info -->
                            <div class="card border-0 mt-3">
                                <div class="card-header bg-transparent">
                                    <h6 class="mb-0"><i class="fas fa-address-card me-2"></i>Contact Information</h6>
                                </div>
                                <div class="card-body">
                                    ${student.email ? `<div class="mb-2"><i class="fas fa-envelope text-muted me-2"></i>${student.email}</div>` : ''}
                                    ${student.phone ? `<div class="mb-2"><i class="fas fa-phone text-muted me-2"></i>${student.phone}</div>` : ''}
                                    ${student.family ? `<div class="mb-2"><i class="fas fa-users text-muted me-2"></i>${student.family.name}</div>` : ''}
                                    <div class="mb-2"><i class="fas fa-calendar text-muted me-2"></i>Joined ${student.created_at ? new Date(student.created_at).toLocaleDateString() : 'Unknown'}</div>
                                </div>
                            </div>
                        </div>

                        <!-- Classes and Progress -->
                        <div class="col-md-8">
                            <!-- Enrolled Classes -->
                            <div class="card border-0 mb-3">
                                <div class="card-header bg-transparent">
                                    <h6 class="mb-0"><i class="fas fa-chalkboard-teacher me-2"></i>Enrolled Classes</h6>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        ${classes.map(classItem => `
                                            <div class="col-md-6 mb-3">
                                                <div class="border rounded p-3">
                                                    <h6 class="mb-2">${classItem.name}</h6>
                                                    <div class="small text-muted">
                                                        <div><i class="fas fa-tag me-1"></i>${classItem.category ? classItem.category.name : 'N/A'}</div>
                                                        <div><i class="fas fa-layer-group me-1"></i>${classItem.type ? classItem.type.name : 'N/A'}</div>
                                                        <div><i class="fas fa-calendar me-1"></i>${classItem.season ? classItem.season.name : 'N/A'}</div>
                                                        ${classItem.class_time ? `<div><i class="fas fa-clock me-1"></i>${classItem.class_time.time_slot || 'N/A'}</div>` : ''}
                                                        ${classItem.class_location ? `<div><i class="fas fa-map-marker-alt me-1"></i>${classItem.class_location.city || 'N/A'}</div>` : ''}
                                                        <div><i class="fas fa-dollar-sign me-1"></i>$${parseFloat(classItem.cost).toFixed(2)}</div>
                                                    </div>
                                                </div>
                                            </div>
                                        `).join('')}
                                    </div>
                                </div>
                            </div>

                            <!-- Recent Attendance -->
                            <div class="card border-0">
                                <div class="card-header bg-transparent">
                                    <h6 class="mb-0"><i class="fas fa-calendar-check me-2"></i>Recent Attendance</h6>
                                </div>
                                <div class="card-body">
                                    ${attendances.length > 0 ? `
                                        <div class="table-responsive">
                                            <table class="table table-sm">
                                                <thead>
                                                    <tr>
                                                        <th>Date</th>
                                                        <th>Status</th>
                                                        <th>Notes</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    ${attendances.map(attendance => `
                                                        <tr>
                                                            <td>${new Date(attendance.date).toLocaleDateString()}</td>
                                                            <td>
                                                                <span class="badge ${attendance.status === 'present' ? 'bg-success' : attendance.status === 'absent' ? 'bg-danger' : 'bg-warning'}">
                                                                    ${attendance.status}
                                                                </span>
                                                            </td>
                                                            <td>${attendance.notes || '-'}</td>
                                                        </tr>
                                                    `).join('')}
                                                </tbody>
                                            </table>
                                        </div>
                                    ` : '<p class="text-muted text-center py-3">No attendance records found</p>'}
                                </div>
                            </div>
                        </div>
                    </div>
                `);
            })
            .fail(function() {
                $('#studentModalBody').html(`
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        Failed to load student details. Please try again.
                    </div>
                `);
            });
    };

    // View attendance function
    window.viewAttendance = function(studentId) {
        // Navigate to attendance page for this student
        window.location.href = `{{ route('instructor.students.index') }}/${studentId}/attendance`;
    };

    // Send message function
    window.sendMessage = function(studentId) {
        // Open message modal or navigate to messaging system
        alert('Message functionality for student ID: ' + studentId + ' would be implemented here.');
    };

    // Export students function
    window.exportStudents = function() {
        const selectedStudents = $('.student-checkbox:checked').map(function() {
            return this.value;
        }).get();

        if (selectedStudents.length === 0) {
            // Export all visible students
            window.location.href = '{{ route("instructor.students.index") }}?export=all';
        } else {
            // Export selected students
            const form = $('<form>', {
                method: 'POST',
                action: '{{ route("instructor.students.index") }}/export'
            });

            selectedStudents.forEach(function(studentId) {
                form.append($('<input>', {
                    type: 'hidden',
                    name: 'student_ids[]',
                    value: studentId
                }));
            });

            $('body').append(form);
            form.submit();
            form.remove();
        }
    };

    // Bulk action execution
    window.executeBulkAction = function() {
        const action = $('#bulkAction').val();
        const selectedStudents = $('.student-checkbox:checked').map(function() {
            return this.value;
        }).get();

        if (!action) {
            alert('Please select an action');
            return;
        }

        if (selectedStudents.length === 0) {
            alert('Please select at least one student');
            return;
        }

        switch(action) {
            case 'export':
                exportStudents();
                break;
            case 'send_message':
                alert('Bulk message functionality would be implemented here');
                break;
            case 'mark_attendance':
                alert('Bulk attendance marking would be implemented here');
                break;
        }

        $('#bulkActionModal').modal('hide');
    };

    // Auto-submit form when filter changes
    $('select[name="class_id"], select[name="category_id"], select[name="type_id"], select[name="status"]').on('change', function() {
        $(this).closest('form').submit();
    });

    // Auto-dismiss alerts after 5 seconds
    $('.alert').each(function() {
        const alert = $(this);
        setTimeout(function() {
            alert.fadeOut('slow');
        }, 5000);
    });
});
</script>
@endpush
