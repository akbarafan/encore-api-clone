<div class="sidebar">
    <div class="p-3 border-bottom">
        <a class="navbar-brand text-white text-decoration-none" href="{{ route('instructor.dashboard.index') }}">
            <i class="fas fa-graduation-cap me-2"></i>Encore
        </a>
        <small class="text-white-50 d-block">Instructor Panel</small>
    </div>

    <nav class="nav flex-column p-2">
        <!-- Dashboard -->
        <a class="nav-link {{ request()->routeIs('instructor.dashboard.index') ? 'active' : '' }}"
           href="{{ route('instructor.dashboard.index') }}">
            <i class="fas fa-tachometer-alt"></i>
            <span>Dashboard</span>
        </a>

        <!-- Teaching Management -->
        <div class="nav-section">
            <small class="nav-section-title">Teaching Management</small>
            
            <a class="nav-link {{ request()->routeIs('instructor.classes.*') ? 'active' : '' }}"
               href="{{ route('instructor.classes.index') }}">
                <i class="fas fa-chalkboard-teacher"></i>
                <span>My Classes</span>
            </a>

            <a class="nav-link {{ request()->routeIs('instructor.students.*') ? 'active' : '' }}"
               href="{{ route('instructor.students.index') }}">
                <i class="fas fa-users"></i>
                <span>Students</span>
            </a>

            <a class="nav-link {{ request()->routeIs('instructor.schedules.*') ? 'active' : '' }}"
               href="{{ route('instructor.schedules.index') }}">
                <i class="fas fa-calendar-alt"></i>
                <span>Schedules</span>
            </a>
        </div>

        <!-- Communication -->
        <div class="nav-section">
            <small class="nav-section-title">Communication</small>
            
            <a class="nav-link {{ request()->routeIs('instructor.message-activities.*') ? 'active' : '' }}"
               href="{{ route('instructor.message-activities.index') }}">
                <i class="fas fa-bullhorn"></i>
                <span>Class Activities</span>
            </a>

            <a class="nav-link {{ request()->routeIs('instructor.chat.*') ? 'active' : '' }}"
               href="{{ route('instructor.chat.index') }}">
                <i class="fas fa-comments"></i>
                <span>Class Chat</span>
            </a>
        </div>

        <!-- Time & Administration -->
        <div class="nav-section">
            <small class="nav-section-title">Time & Administration</small>
            
            <a class="nav-link {{ request()->routeIs('instructor.log-hours.*') ? 'active' : '' }}"
               href="{{ route('instructor.log-hours.index') }}">
                <i class="fas fa-clock"></i>
                <span>Log Hours</span>
            </a>

            <a class="nav-link {{ request()->routeIs('instructor.timesheets.*') ? 'active' : '' }}"
               href="{{ route('instructor.timesheets.index') }}">
                <i class="fas fa-file-alt"></i>
                <span>Timesheets</span>
            </a>
        </div>
    </nav>
</div>
