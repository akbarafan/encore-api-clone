<div class="topbar d-flex justify-content-between align-items-center flex-wrap">
    <div class="mb-2 mb-md-0">
        <h4 class="mb-1 fw-bold">@yield('page-title', 'Dashboard')</h4>
        <small class="text-muted">@yield('page-subtitle', 'Welcome to your student portal')</small>
    </div>

    <div class="d-flex align-items-center gap-2">
        <!-- Notifications (optional) -->
        <div class="dropdown d-none d-md-block">
            <button class="btn btn-outline-secondary btn-sm" type="button" data-bs-toggle="dropdown">
                <i class="fas fa-bell"></i>
                <span class="badge bg-danger badge-sm">3</span>
            </button>
            <ul class="dropdown-menu dropdown-menu-end">
                <li><h6 class="dropdown-header">Notifications</h6></li>
                <li><a class="dropdown-item" href="#"><i class="fas fa-calendar text-primary me-2"></i>New schedule added</a></li>
                <li><a class="dropdown-item" href="#"><i class="fas fa-user text-success me-2"></i>Student enrolled</a></li>
                <li><a class="dropdown-item" href="#"><i class="fas fa-clock text-warning me-2"></i>Timesheet reminder</a></li>
                <li><hr class="dropdown-divider"></li>
                <li><a class="dropdown-item text-center" href="#">View all</a></li>
            </ul>
        </div>

        <!-- User Dropdown -->
        <div class="dropdown">
            <button class="btn btn-outline-primary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                <i class="fas fa-user-circle me-1"></i>
                <span class="d-none d-sm-inline">{{ auth()->user()->name ?? 'Student' }}</span>
            </button>
            <ul class="dropdown-menu dropdown-menu-end">
                <li><h6 class="dropdown-header">{{ auth()->user()->name ?? 'Student' }}</h6></li>
                <li><hr class="dropdown-divider"></li>

            </ul>
        </div>
    </div>
</div>
