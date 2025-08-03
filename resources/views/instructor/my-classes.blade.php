@extends('instructor.layouts.app')

@section('title', 'My Classes')
@section('page-title', 'My Classes')
@section('page-subtitle', 'Manage your classes')

@section('content')
<!-- Statistics Cards -->
<div class="row mb-4">
    <div class="col-xl-4 col-md-6">
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="flex-shrink-0">
                        <div class="bg-primary bg-opacity-10 rounded-3 p-3">
                            <i class="fas fa-chalkboard-teacher text-primary fa-lg"></i>
                        </div>
                    </div>
                    <div class="flex-grow-1 ms-3">
                        <h6 class="text-muted mb-1">Total Classes</h6>
                        <h4 class="mb-0">{{ $stats['total'] }}</h4>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xl-4 col-md-6">
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
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xl-4 col-md-6">
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="flex-shrink-0">
                        <div class="bg-warning bg-opacity-10 rounded-3 p-3">
                            <i class="fas fa-clock text-warning fa-lg"></i>
                        </div>
                    </div>
                    <div class="flex-grow-1 ms-3">
                        <h6 class="text-muted mb-1">Pending</h6>
                        <h4 class="mb-0">{{ $stats['pending'] }}</h4>
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
                <h4 class="mb-0">Class Management</h4>
                <p class="text-muted mb-0">Manage and organize your teaching classes</p>
            </div>
            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#classModal">
                <i class="fas fa-plus me-2"></i>Add New Class
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

<!-- Classes Table with DataTables -->
<div class="card border-0 shadow-sm">
    <div class="card-body">
        <div class="table-responsive">
            <table id="classesTable" class="table table-hover">
                <thead class="bg-light">
                    <tr>
                        <th>Class Details</th>
                        <th>Category</th>
                        <th>Type</th>
                        <th>Season</th>
                        <th>Schedule</th>
                        <th>Location</th>
                        <th>Cost</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($classes as $class)
                    <tr>
                        <td>
                            <div>
                                <h6 class="mb-1">{{ $class->name }}</h6>
                                @if($class->description)
                                    <p class="text-muted mb-0 small">{{ Str::limit($class->description, 60) }}</p>
                                @endif
                            </div>
                        </td>
                        <td>
                            <span class="badge bg-light text-dark">{{ $class->category->name ?? 'N/A' }}</span>
                        </td>
                        <td>
                            <span class="badge bg-light text-dark">{{ $class->type->name ?? 'N/A' }}</span>
                        </td>
                        <td>
                            <span class="badge bg-light text-dark">{{ $class->season->name ?? 'N/A' }}</span>
                        </td>
                        <td>
                            <div class="small">
                                @if($class->classTime)
                                    <div><i class="fas fa-clock text-muted me-1"></i>{{ $class->classTime->time_slot ?? 'N/A' }}</div>
                                @endif
                                @if($class->scheduled_at)
                                    <div class="text-muted">{{ $class->scheduled_at->format('M d, Y') }}</div>
                                @endif
                            </div>
                        </td>
                        <td>
                            <div class="small">
                                <div><i class="fas fa-map-marker-alt text-muted me-1"></i>{{ $class->classLocation->city ?? 'N/A' }}</div>
                                @if($class->classLocation && isset($class->classLocation->room))
                                    <div class="text-muted">Room: {{ $class->classLocation->room }}</div>
                                @endif
                            </div>
                        </td>
                        <td>
                            <span class="fw-semibold">${{ number_format($class->cost, 2) }}</span>
                        </td>
                        <td>
                            @if($class->is_approved)
                                <span class="badge bg-success-subtle text-success">
                                    <i class="fas fa-check me-1"></i>Approved
                                </span>
                            @else
                                <span class="badge bg-warning-subtle text-warning">
                                    <i class="fas fa-clock me-1"></i>Pending
                                </span>
                            @endif
                        </td>
                        <td>
                            <div class="btn-group" role="group">
                                <button type="button" class="btn btn-sm btn-outline-primary edit-class-btn"
                                        data-class-id="{{ $class->id }}"
                                        data-bs-toggle="modal"
                                        data-bs-target="#classModal"
                                        title="Edit Class">
                                    <i class="fas fa-edit"></i>
                                </button>
                                                                <button type="button" class="btn btn-sm btn-outline-danger delete-class-btn"
                                        data-class-id="{{ $class->id }}"
                                        title="Delete Class">
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

<!-- Modal for Add/Edit Class -->
<div class="modal fade" id="classModal" tabindex="-1" aria-labelledby="classModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content border-0 shadow">
            <form id="classForm" method="POST" action="{{ route('instructor.classes.store') }}">
                @csrf
                <input type="hidden" name="_method" value="POST" id="methodField">

                <div class="modal-header border-0 pb-0">
                    <div>
                        <h5 class="modal-title" id="classModalLabel">Add New Class</h5>
                        <p class="text-muted mb-0 small">Fill in the details to create a new class</p>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="season_id" class="form-label">Season <span class="text-danger">*</span></label>
                            <select class="form-select" id="season_id" name="season_id" required>
                                <option value="">Select Season</option>
                                @foreach($seasons as $season)
                                    <option value="{{ $season->id }}">{{ $season->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label for="class_category_id" class="form-label">Category <span class="text-danger">*</span></label>
                            <select class="form-select" id="class_category_id" name="class_category_id" required>
                                <option value="">Select Category</option>
                                @foreach($categories as $category)
                                    <option value="{{ $category->id }}">{{ $category->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="class_type_id" class="form-label">Type <span class="text-danger">*</span></label>
                            <select class="form-select" id="class_type_id" name="class_type_id" required>
                                <option value="">Select Type</option>
                                @foreach($types as $type)
                                    <option value="{{ $type->id }}">{{ $type->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label for="class_time_id" class="form-label">Time Slot <span class="text-danger">*</span></label>
                            <select class="form-select" id="class_time_id" name="class_time_id" required>
                                <option value="">Select Time Slot</option>
                                @foreach($times as $time)
                                    <option value="{{ $time->id }}">{{ $time->time_slot ?? $time->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="class_location_id" class="form-label">Location <span class="text-danger">*</span></label>
                            <select class="form-select" id="class_location_id" name="class_location_id" required>
                                <option value="">Select Location</option>
                                @foreach($locations as $location)
                                    <option value="{{ $location->id }}">{{ $location->city }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label for="name" class="form-label">Class Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="name" name="name" required placeholder="Enter class name">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="description" class="form-label">Description</label>
                        <textarea class="form-control" id="description" name="description" rows="3"
                                  placeholder="Describe your class content, objectives, and requirements..."></textarea>
                    </div>

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="cost" class="form-label">Cost <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text">$</span>
                                <input type="number" class="form-control" id="cost" name="cost" step="0.01" min="0" required placeholder="0.00">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label for="scheduled_at" class="form-label">Scheduled Date</label>
                            <input type="datetime-local" class="form-control" id="scheduled_at" name="scheduled_at">
                        </div>
                    </div>
                </div>

                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save me-2"></i>Save Class
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
                        <i class="fas fa-exclamation-triangle me-2"></i>Delete Class
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
                        <p class="mb-0">Are you sure you want to delete this class? This will permanently remove the class and all associated data.</p>
                    </div>
                </div>
            </div>
            <div class="modal-footer border-0 pt-0">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                <form id="deleteForm" method="POST" style="display: inline;">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-danger">
                        <i class="fas fa-trash me-2"></i>Delete Class
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
.text-success {
    color: #198754 !important;
}
.text-warning {
    color: #ffc107 !important;
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
    const table = $('#classesTable').DataTable({
        responsive: true,
        pageLength: 10,
        lengthMenu: [[10, 25, 50, -1], [10, 25, 50, "All"]],
        order: [[0, 'asc']],
        columnDefs: [
            {
                targets: -1, // Actions column
                orderable: false,
                searchable: false
            }
        ],
        language: {
            search: "Search classes:",
            lengthMenu: "Show _MENU_ classes per page",
            info: "Showing _START_ to _END_ of _TOTAL_ classes",
            infoEmpty: "No classes available",
            infoFiltered: "(filtered from _MAX_ total classes)",
            emptyTable: "No classes found. Add your first class!",
            zeroRecords: "No matching classes found"
        },
        dom: '<"row"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6"f>>' +
             '<"row"<"col-sm-12"tr>>' +
             '<"row"<"col-sm-12 col-md-5"i><"col-sm-12 col-md-7"p>>',
    });

    // Store classes data for JavaScript access
    const classesData = {!! json_encode($classes) !!};
    let editingClassId = null;

    // Edit class button click handler
    $(document).on('click', '.edit-class-btn', function() {
        const classId = $(this).data('class-id');
        editClass(classId);
    });

    // Delete class button click handler
    $(document).on('click', '.delete-class-btn', function() {
        const classId = $(this).data('class-id');
        deleteClass(classId);
    });

    // Edit class function
    function editClass(classId) {
        editingClassId = classId;
        const classItem = classesData.find(c => c.id == classId);

        if (!classItem) {
            console.error('Class not found');
            return;
        }

        // Update modal title
        $('#classModalLabel').text('Edit Class');

        // Update form action and method
        const form = $('#classForm');
        form.attr('action', `{{ url('instructor/classes') }}/${classId}`);
        $('#methodField').val('PUT');

        // Fill form with current data
        $('#season_id').val(classItem.season_id || '');
        $('#class_category_id').val(classItem.class_category_id || '');
        $('#class_type_id').val(classItem.class_type_id || '');
        $('#class_time_id').val(classItem.class_time_id || '');
        $('#class_location_id').val(classItem.class_location_id || '');
        $('#name').val(classItem.name || '');
        $('#description').val(classItem.description || '');
        $('#cost').val(classItem.cost || '');

        // Handle scheduled_at datetime
        if (classItem.scheduled_at) {
            const date = new Date(classItem.scheduled_at);
            const formattedDate = date.toISOString().slice(0, 16);
            $('#scheduled_at').val(formattedDate);
        } else {
            $('#scheduled_at').val('');
        }
    }

    // Delete class function
    function deleteClass(classId) {
        $('#deleteForm').attr('action', `{{ url('instructor/classes') }}/${classId}`);
        $('#deleteModal').modal('show');
    }

    // Reset modal when closed
    $('#classModal').on('hidden.bs.modal', function () {
        editingClassId = null;
        $('#classModalLabel').text('Add New Class');

        const form = $('#classForm');
        form.attr('action', '{{ route("instructor.classes.store") }}');
        form[0].reset();

        // Reset method to POST
        $('#methodField').val('POST');

        // Remove validation classes
        $('.is-invalid').removeClass('is-invalid');
        $('.invalid-feedback').remove();
    });

    // Form validation and submission
    $('#classForm').on('submit', function(e) {
        e.preventDefault();

        const requiredFields = ['season_id', 'class_category_id', 'class_type_id', 'class_time_id', 'class_location_id', 'name', 'cost'];
        let isValid = true;

        // Remove previous validation classes
        $('.is-invalid').removeClass('is-invalid');
        $('.invalid-feedback').remove();

        // Validate required fields
                requiredFields.forEach(function(fieldId) {
            const field = $('#' + fieldId);
            if (!field.val() || field.val().trim() === '') {
                field.addClass('is-invalid');
                field.after('<div class="invalid-feedback">This field is required.</div>');
                isValid = false;
            }
        });

        if (!isValid) {
            // Focus on first invalid field
            $('.is-invalid').first().focus();
            return;
        }

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
});
</script>
@endpush


