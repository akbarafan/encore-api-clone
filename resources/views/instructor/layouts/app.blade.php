<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Instructor Panel') - Encore</title>

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">

    @stack('styles')

    <style>
        :root {
            --sidebar-width: 250px;
            --primary-gradient: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }

        .sidebar {
            position: fixed;
            top: 0;
            left: 0;
            height: 100vh;
            width: var(--sidebar-width);
            background: var(--primary-gradient);
            transition: transform 0.3s ease;
            z-index: 1000;
            overflow-y: auto;
        }

        .main-content {
            margin-left: var(--sidebar-width);
            min-height: 100vh;
            background: #f8f9fa;
            transition: margin-left 0.3s ease;
        }

        .navbar-brand {
            font-weight: 700;
            font-size: 1.5rem;
        }

        .nav-link {
            color: rgba(255,255,255,0.8) !important;
            padding: 0.75rem 1rem !important;
            margin: 0.125rem 0.5rem;
            border-radius: 0.5rem;
            transition: all 0.2s ease;
            display: flex;
            align-items: center;
        }

        .nav-link:hover, .nav-link.active {
            background: rgba(255,255,255,0.15);
            color: white !important;
            transform: translateX(4px);
        }

        .nav-link i {
            width: 20px;
            margin-right: 0.75rem;
            text-align: center;
        }

        .nav-link span {
            font-weight: 500;
        }

        /* Navigation Sections */
        .nav-section {
            margin: 1rem 0;
        }

        .nav-section-title {
            color: rgba(255,255,255,0.6);
            font-weight: 600;
            font-size: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            padding: 0.5rem 1rem 0.25rem 1rem;
            margin-left: 0.5rem;
            display: block;
            border-bottom: 1px solid rgba(255,255,255,0.1);
            margin-bottom: 0.5rem;
        }

        .nav-section .nav-link {
            margin-left: 0.75rem;
            font-size: 0.9rem;
        }

        .nav-section .nav-link i {
            width: 18px;
            font-size: 0.85rem;
        }

        .topbar {
            background: white;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            padding: 1rem 1.5rem;
            border-bottom: 1px solid #e9ecef;
        }

        .card {
            border: none;
            border-radius: 0.75rem;
            box-shadow: 0 2px 12px rgba(0,0,0,0.08);
            transition: transform 0.2s ease;
        }

        .card:hover {
            transform: translateY(-2px);
        }

        .btn {
            border-radius: 0.5rem;
            font-weight: 500;
        }

        .table {
            border-radius: 0.5rem;
            overflow: hidden;
        }

        .badge {
            font-weight: 500;
            padding: 0.5em 0.75em;
        }

        /* Mobile Responsive */
        @media (max-width: 768px) {
            .sidebar {
                transform: translateX(-100%);
            }

            .sidebar.show {
                transform: translateX(0);
            }

            .main-content {
                margin-left: 0;
            }

            .nav-link span {
                display: inline;
            }
        }

        @media (max-width: 576px) {
            .topbar {
                padding: 0.75rem 1rem;
            }

            .nav-link {
                padding: 0.5rem 0.75rem !important;
                margin: 0.125rem 0.25rem;
            }

            .nav-link i {
                margin-right: 0.5rem;
            }
        }

        /* Sidebar toggle button */
        .sidebar-toggle {
            display: none;
            position: fixed;
            top: 1rem;
            left: 1rem;
            z-index: 1001;
            background: var(--primary-gradient);
            border: none;
            color: white;
            width: 40px;
            height: 40px;
            border-radius: 0.5rem;
            box-shadow: 0 2px 8px rgba(0,0,0,0.2);
        }

        @media (max-width: 768px) {
            .sidebar-toggle {
                display: flex;
                align-items: center;
                justify-content: center;
            }
        }

        /* Overlay for mobile */
        .sidebar-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            z-index: 999;
        }

        @media (max-width: 768px) {
            .sidebar-overlay.show {
                display: block;
            }
        }
    </style>
</head>
<body>
    <!-- Mobile Sidebar Toggle -->
    <button class="sidebar-toggle" id="sidebarToggle">
        <i class="fas fa-bars"></i>
    </button>

    <!-- Sidebar Overlay for Mobile -->
    <div class="sidebar-overlay" id="sidebarOverlay"></div>

    <!-- Sidebar -->
    @include('instructor.partials.sidebar')

    <!-- Main Content -->
    <div class="main-content">
        <!-- Top Navigation -->
        @include('instructor.partials.topbar')

        <!-- Content -->
        <div class="container-fluid p-3 p-md-4">
            @yield('content')
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/laravel-echo@1.16.1/dist/echo.iife.js"></script>

    <!-- Global CSRF Setup -->
    <script>
        $.ajaxSetup({
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            }
        });

        // Mobile sidebar toggle
        document.addEventListener('DOMContentLoaded', function() {
            const sidebarToggle = document.getElementById('sidebarToggle');
            const sidebar = document.querySelector('.sidebar');
            const overlay = document.getElementById('sidebarOverlay');

            function toggleSidebar() {
                sidebar.classList.toggle('show');
                overlay.classList.toggle('show');
            }

            sidebarToggle?.addEventListener('click', toggleSidebar);
            overlay?.addEventListener('click', toggleSidebar);

            // Close sidebar on window resize if desktop
            window.addEventListener('resize', function() {
                if (window.innerWidth > 768) {
                    sidebar.classList.remove('show');
                    overlay.classList.remove('show');
                }
            });
        });
    </script>

    @stack('scripts')
</body>
</html>
