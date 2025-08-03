@extends('instructor.layouts.app')

@section('title', 'Class Activities')
@section('page-title', 'Class Activities')
@section('page-subtitle', 'Share daily activities and moments with your students')

@section('content')
<div class="container-fluid px-0">
    <div class="row g-0">
        <!-- Sidebar - Class Selection & Stats -->
        <div class="col-lg-3 col-md-4 border-end bg-light">
            <div class="p-3 border-bottom bg-white">
                <h5 class="mb-3">
                    <i class="fas fa-comments text-primary me-2"></i>Your Classes
                </h5>

                <!-- Stats Cards -->
                <div class="row g-2 mb-3">
                    <div class="col-6">
                        <div class="card border-0 bg-primary bg-opacity-10">
                            <div class="card-body p-2 text-center">
                                <div class="text-primary fw-bold fs-4">{{ $totalActivities }}</div>
                                <div class="small text-muted">Total Posts</div>
                            </div>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="card border-0 bg-success bg-opacity-10">
                            <div class="card-body p-2 text-center">
                                <div class="text-success fw-bold fs-4">{{ $todayActivities }}</div>
                                <div class="small text-muted">Today</div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Filter Form -->
                <form method="GET" action="{{ route('instructor.message-activities.index') }}" id="filterForm">
                    <div class="mb-2">
                        <select class="form-select form-select-sm" name="class_id" onchange="document.getElementById('filterForm').submit()">
                            <option value="">All Classes</option>
                            @foreach($instructorClasses as $class)
                                <option value="{{ $class->id }}" {{ request('class_id') == $class->id ? 'selected' : '' }}>
                                    {{ $class->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="row g-1">
                        <div class="col-6">
                            <input type="date" class="form-control form-control-sm" name="date_from"
                                   value="{{ request('date_from') }}" placeholder="From"
                                   onchange="document.getElementById('filterForm').submit()">
                        </div>
                        <div class="col-6">
                            <input type="date" class="form-control form-control-sm" name="date_to"
                                   value="{{ request('date_to') }}" placeholder="To"
                                   onchange="document.getElementById('filterForm').submit()">
                        </div>
                    </div>
                </form>
            </div>

            <!-- Class List -->
            <div class="list-group list-group-flush">
                <a href="{{ route('instructor.message-activities.index') }}"
                   class="list-group-item list-group-item-action {{ !request('class_id') ? 'active' : '' }}">
                    <div class="d-flex align-items-center">
                        <div class="rounded-circle bg-primary bg-opacity-10 p-2 me-3">
                            <i class="fas fa-users text-primary"></i>
                        </div>
                        <div>
                            <div class="fw-semibold">All Classes</div>
                            <div class="small text-muted">{{ $totalActivities }} activities</div>
                        </div>
                    </div>
                </a>

                @foreach($instructorClasses as $class)
                <a href="{{ route('instructor.message-activities.index', ['class_id' => $class->id]) }}"
                   class="list-group-item list-group-item-action {{ request('class_id') == $class->id ? 'active' : '' }}">
                    <div class="d-flex align-items-center">
                        <div class="rounded-circle bg-info bg-opacity-10 p-2 me-3">
                            <i class="fas fa-graduation-cap text-info"></i>
                        </div>
                        <div class="flex-grow-1">
                            <div class="fw-semibold">{{ $class->name }}</div>
                            <div class="small text-muted">
                                {{ $class->category->name ?? 'N/A' }} • {{ $class->messageActivities->count() }} posts
                            </div>
                        </div>
                    </div>
                </a>
                @endforeach
            </div>
        </div>

        <!-- Main Content - Activities Timeline -->
        <div class="col-lg-9 col-md-8">
            <!-- Header with Post Button -->
            <div class="bg-white border-bottom p-3 sticky-top">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h4 class="mb-1">
                            @if(request('class_id'))
                                {{ $instructorClasses->find(request('class_id'))->name ?? 'Class' }} Activities
                            @else
                                All Class Activities
                            @endif
                        </h4>
                        <p class="text-muted mb-0 small">Share what's happening in your classes today</p>
                    </div>
                    <button type="button" class="btn btn-primary btn-sm rounded-pill px-3" data-bs-toggle="modal" data-bs-target="#postModal">
                        <i class="fas fa-plus me-1"></i>New Post
                    </button>
                </div>
            </div>

            <!-- Activities Timeline -->
            <div class="activities-timeline p-3" style="height: calc(100vh - 200px); overflow-y: auto;">
                @if($activities->count() > 0)
                    @foreach($activities as $activity)
                    <div class="activity-card mb-3" data-activity-id="{{ $activity->id }}">
                        <div class="card border-0 shadow-sm">
                            <div class="card-body">
                                <!-- Activity Header -->
                                <div class="d-flex align-items-start mb-3">
                                    <div class="rounded-circle bg-primary bg-opacity-10 p-2 me-3">
                                        <i class="fas fa-user-tie text-primary"></i>
                                    </div>
                                    <div class="flex-grow-1">
                                        <div class="d-flex align-items-center justify-content-between">
                                            <div>
                                                <h6 class="mb-0 fw-semibold">You</h6>
                                                <div class="small text-muted">
                                                    <i class="fas fa-graduation-cap me-1"></i>{{ $activity->class->name }}
                                                    <span class="mx-2">•</span>
                                                    <i class="fas fa-calendar me-1"></i>{{ $activity->activity_date->format('M d, Y') }}
                                                    <span class="mx-2">•</span>
                                                    <i class="fas fa-clock me-1"></i>{{ $activity->created_at->diffForHumans() }}
                                                </div>
                                            </div>
                                            <div class="dropdown">
                                                <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                                                    <i class="fas fa-ellipsis-h"></i>
                                                </button>
                                                <ul class="dropdown-menu dropdown-menu-end">
                                                    <li>
                                                        <button class="dropdown-item edit-activity-btn" data-activity-id="{{ $activity->id }}">
                                                            <i class="fas fa-edit me-2"></i>Edit
                                                        </button>
                                                    </li>
                                                    <li>
                                                        <button class="dropdown-item toggle-pin-btn" data-activity-id="{{ $activity->id }}">
                                                            <i class="fas fa-thumbtack me-2"></i>
                                                            {{ $activity->is_pinned ? 'Unpin' : 'Pin' }}
                                                        </button>
                                                    </li>
                                                    <li><hr class="dropdown-divider"></li>
                                                    <li>
                                                        <button class="dropdown-item text-danger delete-activity-btn" data-activity-id="{{ $activity->id }}">
                                                            <i class="fas fa-trash me-2"></i>Delete
                                                        </button>
                                                    </li>
                                                </ul>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Activity Title -->
                                @if($activity->title)
                                <h5 class="activity-title mb-3">{{ $activity->title }}</h5>
                                @endif

                                <!-- Activity Content -->
                                <div class="activity-message mb-3">
                                    <p class="mb-0">{!! nl2br(e($activity->message)) !!}</p>
                                </div>

                                <!-- Attachments -->
                                @if($activity->attachments && count($activity->attachments) > 0)
                                <div class="activity-attachments mb-3">
                                    @php
                                        $images = collect($activity->attachments)->filter(function($attachment, $key) {
                                            return in_array(strtolower(pathinfo($attachment['original_name'], PATHINFO_EXTENSION)), ['jpg', 'jpeg', 'png', 'gif', 'webp']);
                                        });
                                        $videos = collect($activity->attachments)->filter(function($attachment, $key) {
                                            return in_array(strtolower(pathinfo($attachment['original_name'], PATHINFO_EXTENSION)), ['mp4', 'avi', 'mov', 'wmv', 'webm']);
                                        });
                                        $documents = collect($activity->attachments)->filter(function($attachment, $key) {
                                            return !in_array(strtolower(pathinfo($attachment['original_name'], PATHINFO_EXTENSION)), ['jpg', 'jpeg', 'png', 'gif', 'webp', 'mp4', 'avi', 'mov', 'wmv', 'webm']);
                                        });
                                    @endphp

                                    <!-- Image Gallery - WhatsApp Style -->
                                    @if($images->count() > 0)
                                    <div class="image-gallery mb-3">
                                        @if($images->count() == 1)
                                            <!-- Single Image - Full Width -->
                                            @php $attachment = $images->first(); @endphp
                                            <div class="single-image-container position-relative mb-2">
                                                <img src="{{ asset('storage/' . $attachment['path']) }}"
                                                     class="img-fluid rounded shadow-sm w-100"
                                                     alt="{{ $attachment['original_name'] }}"
                                                     style="max-height: 400px; object-fit: cover; cursor: pointer;"
                                                     data-bs-toggle="modal"
                                                     data-bs-target="#imageModal"
                                                     data-image-src="{{ asset('storage/' . $attachment['path']) }}"
                                                     data-image-title="{{ $attachment['original_name'] }}">
                                                <div class="image-overlay position-absolute top-0 end-0 m-2">
                                                    <a href="{{ route('instructor.message-activities.download', [$activity->id, 0]) }}"
                                                       class="btn btn-sm btn-dark bg-opacity-75 rounded-circle"
                                                       title="Download">
                                                        <i class="fas fa-download text-white"></i>
                                                    </a>
                                                </div>
                                            </div>
                                        @elseif($images->count() == 2)
                                            <!-- Two Images - Side by Side -->
                                            <div class="row g-1">
                                                @foreach($images as $index => $attachment)
                                                <div class="col-6">
                                                    <div class="image-container position-relative">
                                                        <img src="{{ asset('storage/' . $attachment['path']) }}"
                                                             class="img-fluid rounded shadow-sm w-100"
                                                             alt="{{ $attachment['original_name'] }}"
                                                             style="height: 200px; object-fit: cover; cursor: pointer;"
                                                             data-bs-toggle="modal"
                                                             data-bs-target="#imageModal"
                                                             data-image-src="{{ asset('storage/' . $attachment['path']) }}"
                                                             data-image-title="{{ $attachment['original_name'] }}">
                                                        <div class="image-overlay position-absolute top-0 end-0 m-1">
                                                            <a href="{{ route('instructor.message-activities.download', [$activity->id, $index]) }}"
                                                               class="btn btn-sm btn-dark bg-opacity-75 rounded-circle"
                                                               title="Download">
                                                                <i class="fas fa-download text-white"></i>
                                                            </a>
                                                        </div>
                                                    </div>
                                                </div>
                                                @endforeach
                                            </div>
                                        @elseif($images->count() == 3)
                                            <!-- Three Images - Instagram Style -->
                                            <div class="row g-1">
                                                <div class="col-6">
                                                    @php $attachment = $images->first(); @endphp
                                                    <div class="image-container position-relative">
                                                        <img src="{{ asset('storage/' . $attachment['path']) }}"
                                                             class="img-fluid rounded shadow-sm w-100"
                                                             alt="{{ $attachment['original_name'] }}"
                                                             style="height: 250px; object-fit: cover; cursor: pointer;"
                                                             data-bs-toggle="modal"
                                                             data-bs-target="#imageModal"
                                                             data-image-src="{{ asset('storage/' . $attachment['path']) }}"
                                                             data-image-title="{{ $attachment['original_name'] }}">
                                                        <div class="image-overlay position-absolute top-0 end-0 m-1">
                                                            <a href="{{ route('instructor.message-activities.download', [$activity->id, 0]) }}"
                                                               class="btn btn-sm btn-dark bg-opacity-75 rounded-circle"
                                                               title="Download">
                                                                <i class="fas fa-download text-white"></i>
                                                            </a>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="col-6">
                                                    <div class="row g-1">
                                                        @foreach($images->skip(1) as $index => $attachment)
                                                        <div class="col-12">
                                                            <div class="image-container position-relative">
                                                                <img src="{{ asset('storage/' . $attachment['path']) }}"
                                                                     class="img-fluid rounded shadow-sm w-100"
                                                                     alt="{{ $attachment['original_name'] }}"
                                                                     style="height: 122px; object-fit: cover; cursor: pointer;"
                                                                     data-bs-toggle="modal"
                                                                     data-bs-target="#imageModal"
                                                                     data-image-src="{{ asset('storage/' . $attachment['path']) }}"
                                                                     data-image-title="{{ $attachment['original_name'] }}">
                                                                <div class="image-overlay position-absolute top-0 end-0 m-1">
                                                                    <a href="{{ route('instructor.message-activities.download', [$activity->id, $index + 1]) }}"
                                                                       class="btn btn-sm btn-dark bg-opacity-75 rounded-circle"
                                                                       title="Download">
                                                                        <i class="fas fa-download text-white"></i>
                                                                    </a>
                                                                </div>
                                                            </div>
                                                        </div>
                                                        @endforeach
                                                    </div>
                                                </div>
                                            </div>
                                        @else
                                            <!-- 4+ Images - Grid with More indicator -->
                                            <div class="row g-1">
                                                @foreach($images->take(4) as $index => $attachment)
                                                <div class="col-6">
                                                    <div class="image-container position-relative">
                                                        <img src="{{ asset('storage/' . $attachment['path']) }}"
                                                             class="img-fluid rounded shadow-sm w-100"
                                                             alt="{{ $attachment['original_name'] }}"
                                                             style="height: 150px; object-fit: cover; cursor: pointer;"
                                                             data-bs-toggle="modal"
                                                             data-bs-target="#imageModal"
                                                             data-image-src="{{ asset('storage/' . $attachment['path']) }}"
                                                             data-image-title="{{ $attachment['original_name'] }}">

                                                        @if($index == 3 && $images->count() > 4)
                                                            <!-- More Images Overlay -->
                                                            <div class="position-absolute top-0 start-0 w-100 h-100 d-flex align-items-center justify-content-center bg-dark bg-opacity-50 rounded">
                                                                <span class="text-white fw-bold fs-4">+{{ $images->count() - 4 }}</span>
                                                            </div>
                                                        @endif

                                                        <div class="image-overlay position-absolute top-0 end-0 m-1">
                                                            <a href="{{ route('instructor.message-activities.download', [$activity->id, $index]) }}"
                                                               class="btn btn-sm btn-dark bg-opacity-75 rounded-circle"
                                                               title="Download">
                                                                <i class="fas fa-download text-white"></i>
                                                            </a>
                                                        </div>
                                                    </div>
                                                </div>
                                                @endforeach
                                            </div>
                                        @endif
                                    </div>
                                    @endif

                                    <!-- Video Gallery - Chat Style -->
                                    @if($videos->count() > 0)
                                    <div class="video-gallery mb-3">
                                        @foreach($videos as $index => $attachment)
                                        <div class="video-container position-relative mb-2">
                                            <video class="w-100 rounded shadow-sm"
                                                   controls
                                                   preload="metadata"
                                                   style="max-height: 300px; background: #000;">
                                                <source src="{{ asset('storage/' . $attachment['path']) }}" type="{{ $attachment['mime_type'] }}">
                                                Your browser does not support the video tag.
                                            </video>
                                            <!-- Video Info Overlay -->
                                            <div class="position-absolute top-0 end-0 m-2">
                                                <a href="{{ route('instructor.message-activities.download', [$activity->id, $index]) }}"
                                                   class="btn btn-sm btn-dark bg-opacity-75 rounded-circle"
                                                   title="Download {{ $attachment['original_name'] }}">
                                                    <i class="fas fa-download text-white"></i>
                                                </a>
                                            </div>
                                            <!-- Video Title -->
                                            <div class="video-caption mt-1">
                                                <small class="text-muted">
                                                    <i class="fas fa-video me-1"></i>{{ $attachment['original_name'] }}
                                                </small>
                                            </div>
                                        </div>
                                        @endforeach
                                    </div>
                                    @endif

                                    <!-- Document Files - Chat Style -->
                                    @if($documents->count() > 0)
                                    <div class="document-attachments">
                                        @foreach($documents as $index => $attachment)
                                        @php
                                            $extension = strtolower(pathinfo($attachment['original_name'], PATHINFO_EXTENSION));
                                            $fileIcon = 'fas fa-file';
                                            $fileColor = 'text-secondary';
                                            $bgColor = 'bg-light';

                                            switch($extension) {
                                                case 'pdf':
                                                    $fileIcon = 'fas fa-file-pdf';
                                                    $fileColor = 'text-danger';
                                                    $bgColor = 'bg-danger bg-opacity-10';
                                                    break;
                                                case 'doc':
                                                case 'docx':
                                                    $fileIcon = 'fas fa-file-word';
                                                    $fileColor = 'text-primary';
                                                    $bgColor = 'bg-primary bg-opacity-10';
                                                    break;
                                                case 'xls':
                                                case 'xlsx':
                                                    $fileIcon = 'fas fa-file-excel';
                                                    $fileColor = 'text-success';
                                                    $bgColor = 'bg-success bg-opacity-10';
                                                    break;
                                                case 'ppt':
                                                case 'pptx':
                                                    $fileIcon = 'fas fa-file-powerpoint';
                                                    $fileColor = 'text-warning';
                                                    $bgColor = 'bg-warning bg-opacity-10';
                                                    break;
                                                case 'zip':
                                                case 'rar':
                                                    $fileIcon = 'fas fa-file-archive';
                                                    $fileColor = 'text-info';
                                                    $bgColor = 'bg-info bg-opacity-10';
                                                    break;
                                                case 'txt':
                                                    $fileIcon = 'fas fa-file-alt';
                                                    break;
                                            }
                                            $fileSize = isset($attachment['size']) ? number_format($attachment['size'] / 1024, 1) . ' KB' : '';
                                        @endphp
                                        <div class="document-item mb-2">
                                            <a href="{{ route('instructor.message-activities.download', [$activity->id, $index]) }}"
                                               class="text-decoration-none">
                                                <div class="document-card p-2 border rounded {{ $bgColor }} document-hover">
                                                    <div class="d-flex align-items-center">
                                                        <div class="file-icon me-2">
                                                            <i class="{{ $fileIcon }} {{ $fileColor }}" style="font-size: 1.5rem;"></i>
                                                        </div>
                                                        <div class="file-info flex-grow-1">
                                                            <div class="file-name fw-semibold text-dark" style="font-size: 0.9rem;">
                                                                {{ Str::limit($attachment['original_name'], 40) }}
                                                            </div>
                                                            <div class="file-meta small text-muted">
                                                                {{ strtoupper($extension) }}
                                                                @if($fileSize)
                                                                    • {{ $fileSize }}
                                                                @endif
                                                            </div>
                                                        </div>
                                                        <div class="file-actions">
                                                            <div class="btn btn-sm btn-outline-secondary">
                                                                <i class="fas fa-download"></i>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </a>
                                        </div>
                                        @endforeach
                                    </div>
                                    @endif
                                </div>
                                @endif

                                <!-- Activity Footer -->
                                <div class="activity-footer d-flex align-items-center justify-content-between pt-2 border-top">
                                    <div class="d-flex align-items-center gap-3">
                                        @if($activity->is_pinned)
                                        <span class="badge bg-warning">
                                            <i class="fas fa-thumbtack me-1"></i>Pinned
                                        </span>
                                        @endif
                                        @if(!$activity->is_active)
                                        <span class="badge bg-secondary">
                                            <i class="fas fa-eye-slash me-1"></i>Hidden
                                        </span>
                                        @endif
                                    </div>
                                    <div class="small text-muted">
                                        <i class="fas fa-clock me-1"></i>{{ $activity->created_at->format('M d, Y - H:i') }}
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    @endforeach

                    <!-- Pagination -->
                    <div class="d-flex justify-content-center mt-4">
                        {{ $activities->appends(request()->query())->links() }}
                    </div>
                @else
                    <!-- Empty State -->
                    <div class="text-center py-5">
                        <div class="mb-4">
                            <i class="fas fa-comments fa-4x text-muted opacity-50"></i>
                        </div>
                        <h5 class="text-muted">No activities yet</h5>
                        <p class="text-muted mb-4">Start sharing daily activities and moments with your students</p>
                        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#postModal">
                            <i class="fas fa-plus me-2"></i>Create Your First Post
                        </button>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

<!-- Post Activity Modal -->
<div class="modal fade" id="postModal" tabindex="-1" aria-labelledby="postModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content border-0 shadow">
            <form id="postForm" method="POST" action="{{ route('instructor.message-activities.store') }}" enctype="multipart/form-data">
                @csrf
                <input type="hidden" name="_method" value="POST" id="methodField">
                <input type="hidden" name="activity_id" id="activityId">

                <div class="modal-header border-0 pb-0">
                    <div>
                        <h5 class="modal-title" id="postModalLabel">Share Class Activity</h5>
                        <p class="text-muted mb-0 small">Tell your students what's happening today</p>
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
                                    <option value="{{ $class->id }}" {{ request('class_id') == $class->id ? 'selected' : '' }}>
                                        {{ $class->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label for="activity_date" class="form-label">Activity Date <span class="text-danger">*</span></label>
                            <input type="date" class="form-control" id="activity_date" name="activity_date"
                                   value="{{ date('Y-m-d') }}" required>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="title" class="form-label">Title (Optional)</label>
                        <input type="text" class="form-control" id="title" name="title"
                               placeholder="Contoh: Kegiatan Prakarya Hari Ini">
                    </div>

                    <div class="mb-3">
                        <label for="message" class="form-label">Activity Message <span class="text-danger">*</span></label>
                        <textarea class="form-control" id="message" name="message" rows="4" required
                                  placeholder="Ceritakan kegiatan, pencapaian, atau momen hari ini..."></textarea>
                        <div class="form-text text-muted">Maksimal 500 karakter.</div>
                    </div>

                    <div class="mb-3">
                        <label for="attachments" class="form-label">
                            <i class="fas fa-paperclip me-1"></i>Attachments (Optional)
                        </label>
                        <input type="file" class="form-control" id="attachments" name="attachments[]" multiple
                               accept="image/*,video/*,.pdf,.doc,.docx">
                        <div id="filePreview" class="mb-2"></div>
                        <div class="form-text">Upload gambar, video, atau dokumen (maks 10MB per file)</div>
                    </div>

                    <div class="row g-3">
                        <div class="col-md-6">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" id="is_pinned" name="is_pinned" value="1">
                                <label class="form-check-label" for="is_pinned">
                                    <i class="fas fa-thumbtack me-1"></i>Pin this post
                                </label>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" id="is_active" name="is_active" value="1" checked>
                                <label class="form-check-label" for="is_active">
                                    <i class="fas fa-eye me-1"></i>Visible to students
                                </label>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-paper-plane me-2"></i>Post Activity
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
                        <i class="fas fa-exclamation-triangle me-2"></i>Delete Activity
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
                        <p class="mb-0">Are you sure you want to delete this activity? Students will no longer see this post.</p>
                    </div>
                </div>
            </div>
            <div class="modal-footer border-0 pt-0">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                <form id="deleteForm" method="POST" style="display: inline;">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-danger">
                        <i class="fas fa-trash me-2"></i>Delete Activity
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Image Modal for Full View -->
<div class="modal fade" id="imageModal" tabindex="-1" aria-labelledby="imageModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered">
        <div class="modal-content bg-dark border-0">
            <div class="modal-header border-0 text-white">
                <h5 class="modal-title" id="imageModalLabel">Image Preview</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body text-center p-0">
                <img id="modalImage" src="" alt="" class="img-fluid" style="max-height: 80vh; max-width: 100%;">
            </div>
        </div>
    </div>
</div>

@endsection

@push('styles')
<style>
.activities-timeline {
    background-color: #f8f9fa;
}

.activity-card {
    transition: all 0.3s ease;
}

.activity-card:hover {
    transform: translateY(-2px);
}

.activity-card .card {
    border-radius: 1rem;
    overflow: hidden;
}

.activity-title {
    color: #2c3e50;
    font-weight: 600;
}

.activity-message {
    font-size: 0.95rem;
    line-height: 1.6;
    color: #495057;
}

.activity-footer {
    background-color: #f8f9fa;
    margin: -1rem -1rem -1rem -1rem;
    padding: 0.75rem 1rem;
    border-radius: 0 0 1rem 1rem;
}

.list-group-item.active {
    background-color: #e3f2fd;
    border-color: #2196f3;
    color: #1976d2;
}

.list-group-item:hover {
    background-color: #f5f5f5;
}

.modal-content {
    border-radius: 1rem;
}

.form-control:focus,
.form-select:focus {
    border-color: #2196f3;
    box-shadow: 0 0 0 0.2rem rgba(33, 150, 243, 0.25);
}

.btn-primary {
    background-color: #2196f3;
    border-color: #2196f3;
}

.btn-primary:hover {
    background-color: #1976d2;
    border-color: #1976d2;
}

.badge {
    font-weight: 500;
    padding: 0.4em 0.7em;
}

@media (max-width: 768px) {
    .col-lg-3, .col-md-4 {
        display: none;
    }

    .col-lg-9, .col-md-8 {
        flex: 0 0 100%;
        max-width: 100%;
    }
}

.sticky-top {
    z-index: 1020;
}

#charCount {
    font-weight: 500;
}

.activity-attachments .btn {
    border-radius: 50px;
}

/* Image Gallery Styles */
.image-gallery .image-container {
    overflow: hidden;
    border-radius: 0.75rem;
    transition: transform 0.3s ease;
}

.image-gallery .image-container:hover {
    transform: scale(1.02);
}

.image-gallery .activity-image {
    transition: transform 0.3s ease;
}

.image-gallery .image-container:hover .activity-image {
    transform: scale(1.05);
}

.image-overlay {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0, 0, 0, 0.3);
    opacity: 0;
    transition: opacity 0.3s ease;
    display: flex;
    align-items: center;
    justify-content: center;
}

.image-container:hover .image-overlay {
    opacity: 1;
}

/* Video Gallery Styles */
.video-gallery .video-container {
    border-radius: 0.75rem;
    overflow: hidden;
}

.video-gallery video {
    object-fit: cover;
}

.video-info {
    display: flex;
    align-items: center;
    justify-content: between;
}

/* Document Cards - Chat Style */
.document-card {
    transition: all 0.3s ease;
    border: 1px solid #e9ecef !important;
    max-width: 400px;
}

.document-hover:hover {
    transform: translateY(-1px);
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
    border-color: #2196f3 !important;
}

.document-card .file-icon {
    transition: transform 0.3s ease;
}

.document-hover:hover .file-icon {
    transform: scale(1.05);
}

/* Chat-Style Image Gallery */
.single-image-container .image-overlay {
    opacity: 0;
    transition: opacity 0.3s ease;
}

.single-image-container:hover .image-overlay {
    opacity: 1;
}

/* Video Chat Style */
.video-container {
    max-width: 500px;
}

.video-caption {
    margin-top: 0.25rem;
}

.file-name {
    font-size: 0.9rem;
    color: #495057;
}

.file-meta {
    color: #6c757d;
}

/* Lightbox Modal */
#imageModal .modal-content {
    background: rgba(0, 0, 0, 0.9) !important;
}

#imageModal .modal-body {
    background: transparent;
}

/* Responsive adjustments */
@media (max-width: 768px) {
    .image-gallery .col-4 {
        flex: 0 0 50%;
        max-width: 50%;
    }

    .document-card {
        margin-bottom: 0.5rem;
    }

    .file-name {
        font-size: 0.8rem;
    }
}
</style>
@endpush

@push('scripts')
<script>
$(document).ready(function() {
    let editingActivityId = null;

    // Character count for message
    $('#message').on('input', function() {
        const length = $(this).val().length;
        $('#charCount').text(length);

        if (length > 500) {
            $('#charCount').addClass('text-danger');
        } else if (length > 400) {
            $('#charCount').addClass('text-warning').removeClass('text-danger');
        } else {
            $('#charCount').removeClass('text-warning text-danger');
        }
    });

    // Edit activity button
    $(document).on('click', '.edit-activity-btn', function() {
        const activityId = $(this).data('activity-id');
        // Fetch data via GET (boleh AJAX, hanya untuk isi form)
        $.get(`{{ url('instructor/message-activities') }}/${activityId}`)
            .done(function(response) {
                if (response.success) {
                    const activity = response.activity;
                    $('#postModalLabel').text('Edit Activity');
                    $('#postForm').attr('action', `{{ url('instructor/message-activities') }}/${activityId}`);
                    $('#methodField').val('PUT');
                    // isi field form
                    $('#class_id').val(activity.class_id);
                    $('#title').val(activity.title || '');
                    $('#message').val(activity.message);
                    $('#activity_date').val(activity.activity_date);
                    $('#is_pinned').prop('checked', activity.is_pinned);
                    $('#is_active').prop('checked', activity.is_active);
                    $('#postModal').modal('show');
                }
            });
    });

    // Delete activity button
    $(document).on('click', '.delete-activity-btn', function() {
        const activityId = $(this).data('activity-id');
        deleteActivity(activityId);
    });

    // Toggle pin button
    $(document).on('click', '.toggle-pin-btn', function() {
        const activityId = $(this).data('activity-id');
        togglePin(activityId);
    });

    // Reset form when modal is closed
    $('#postModal').on('hidden.bs.modal', function() {
        $('#postForm').attr('action', '{{ route("instructor.message-activities.store") }}');
        $('#methodField').val('POST');
        // reset field
        $('#postForm')[0].reset();
    });

    // Image modal functionality
    $(document).on('click', '.activity-image', function() {
        const imageSrc = $(this).data('image-src');
        const imageTitle = $(this).data('image-title');

        $('#modalImage').attr('src', imageSrc).attr('alt', imageTitle);
        $('#imageModalLabel').text(imageTitle);
    });

    // File upload preview
    $('#attachments').on('change', function() {
        $('#filePreview').empty();
        const files = this.files;
        if (files.length > 0) {
            Array.from(files).forEach(file => {
                if (file.type.startsWith('image/')) {
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        $('#filePreview').append(`<img src="${e.target.result}" class="img-thumbnail m-1" style="max-width:80px;max-height:80px;">`);
                    };
                    reader.readAsDataURL(file);
                } else if (file.type.startsWith('video/')) {
                    $('#filePreview').append(`<span class="badge bg-info m-1">${file.name} (video)</span>`);
                } else {
                    $('#filePreview').append(`<span class="badge bg-secondary m-1">${file.name}</span>`);
                }
            });
        }
    });

    // Functions
    function editActivity(activityId) {
        editingActivityId = activityId;

        // Fetch activity data
        $.get(`{{ url('instructor/message-activities') }}/${activityId}`)
            .done(function(response) {
                if (response.success) {
                    const activity = response.activity;

                    // Update modal title
                    $('#postModalLabel').text('Edit Activity');
                    $('#methodField').val('PUT');
                    $('#activityId').val(activityId);

                    // Fill form
                    $('#class_id').val(activity.class_id);
                    $('#title').val(activity.title || '');
                    $('#message').val(activity.message);
                    $('#activity_date').val(activity.activity_date);
                    $('#is_pinned').prop('checked', activity.is_pinned);
                    $('#is_active').prop('checked', activity.is_active);

                    // Update character count
                    $('#charCount').text(activity.message.length);

                    // Show modal
                    $('#postModal').modal('show');
                } else {
                    showToast('error', 'Failed to load activity data');
                }
            })
            .fail(function() {
                showToast('error', 'Failed to load activity data');
            });
    }

    function deleteActivity(activityId) {
        $('#deleteForm').attr('action', `{{ url('instructor/message-activities') }}/${activityId}`);
        $('#deleteModal').modal('show');
    }

    function togglePin(activityId) {
        $.post(`{{ url('instructor/message-activities') }}/${activityId}/toggle-pin`)
            .done(function(response) {
                if (response.success) {
                    showToast('success', response.message);
                    setTimeout(() => {
                        window.location.reload();
                    }, 1000);
                } else {
                    showToast('error', 'Failed to update pin status');
                }
            })
            .fail(function() {
                showToast('error', 'Failed to update pin status');
            });
    }

    function resetForm() {
        editingActivityId = null;
        $('#postModalLabel').text('Share Class Activity');
        $('#methodField').val('POST');
        $('#activityId').val('');
        $('#postForm')[0].reset();
        $('#activity_date').val('{{ date("Y-m-d") }}');
        $('#is_active').prop('checked', true);
        $('#charCount').text('0').removeClass('text-warning text-danger');
        $('#filePreview').empty(); // Clear file preview on modal close
    }

    function showToast(type, message) {
        // Simple toast notification (you can replace with your preferred toast library)
        const toastClass = type === 'success' ? 'bg-success' : 'bg-danger';
        const toast = $(`
            <div class="toast align-items-center text-white ${toastClass} border-0" role="alert" aria-live="assertive" aria-atomic="true">
                <div class="d-flex">
                    <div class="toast-body">${message}</div>
                    <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
                </div>
            </div>
        `);

        $('.toast-container').remove();
        $('body').append(`<div class="toast-container position-fixed top-0 end-0 p-3">${toast[0].outerHTML}</div>`);
        $('.toast').toast('show');
    }
});
</script>
@endpush
