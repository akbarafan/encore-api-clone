<div class="sidebar">
    <div class="p-3 border-bottom">
        <a class="navbar-brand text-white text-decoration-none" href="{{ route('student.dashboard') }}">
            <i class="fas fa-user-graduate me-2"></i>Encore
        </a>
        <small class="text-white-50 d-block">Student Portal</small>
    </div>

    <nav class="nav flex-column p-2">
        <a class="nav-link {{ request()->routeIs('student.dashboard') ? 'active' : '' }}"
           href="{{ route('student.dashboard') }}">
            <i class="fas fa-tachometer-alt"></i>
            <span>Dashboard</span>
        </a>

        <a class="nav-link {{ request()->routeIs('student.schedule-responses.*') ? 'active' : '' }}"
           href="{{ route('student.schedule-responses.index') }}">
            <i class="fas fa-calendar-exclamation"></i>
            <span>Schedule Changes</span>
        </a>
    </nav>
</div>
