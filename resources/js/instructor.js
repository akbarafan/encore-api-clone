// Instructor Panel JavaScript

document.addEventListener('DOMContentLoaded', function() {
    initializeInstructorPanel();
});

function initializeInstructorPanel() {
    // Initialize clock functionality
    initializeClock();
    
    // Initialize notifications
    initializeNotifications();
    
    // Initialize tooltips
    initializeTooltips();
    
    // Initialize auto-refresh for time-sensitive data
    initializeAutoRefresh();
}

// Clock In/Out functionality
function initializeClock() {
    const clockInBtn = document.getElementById('clock-in-btn');
    const clockOutBtn = document.getElementById('clock-out-btn');
    
    if (clockInBtn) {
        clockInBtn.addEventListener('click', function() {
            clockAction('in');
        });
    }
    
    if (clockOutBtn) {
        clockOutBtn.addEventListener('click', function() {
            clockAction('out');
        });
    }
    
    // Update clock status on page load
    updateClockStatus();
}

function clockAction(action) {
    const btn = document.getElementById(`clock-${action}-btn`);
    if (btn) {
        btn.classList.add('loading');
        btn.disabled = true;
    }
    
    fetch(`/instructor/time-management/clock-${action}`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showNotification('success', data.message);
            updateClockStatus();
        } else {
            showNotification('error', data.message || 'An error occurred');
        }
    })
    .catch(error => {
        console.error('Clock action error:', error);
        showNotification('error', 'Failed to record time');
    })
    .finally(() => {
        if (btn) {
            btn.classList.remove('loading');
            btn.disabled = false;
        }
    });
}

function updateClockStatus() {
    fetch('/instructor/time-management/status')
        .then(response => response.json())
        .then(data => {
            const clockInBtn = document.getElementById('clock-in-btn');
            const clockOutBtn = document.getElementById('clock-out-btn');
            
            if (data.is_clocked_in) {
                if (clockInBtn) clockInBtn.style.display = 'none';
                if (clockOutBtn) clockOutBtn.style.display = 'inline-block';
            } else {
                if (clockInBtn) clockInBtn.style.display = 'inline-block';
                if (clockOutBtn) clockOutBtn.style.display = 'none';
            }
            
            // Update working hours display if available
            if (data.hours_today && document.getElementById('hours-today')) {
                document.getElementById('hours-today').textContent = data.hours_today;
            }
        })
        .catch(error => {
            console.error('Status update error:', error);
        });
}

// Notifications system
function initializeNotifications() {
    // Load notifications on page load
    loadNotifications();
    
    // Set up periodic refresh
    setInterval(loadNotifications, 60000); // Every minute
}

function loadNotifications() {
    fetch('/instructor/notifications/recent')
        .then(response => response.json())
        .then(data => {
            updateNotificationBadge(data.count);
            updateNotificationsList(data.notifications);
        })
        .catch(error => {
            console.error('Notifications load error:', error);
        });
}

function updateNotificationBadge(count) {
    const badge = document.querySelector('.position-absolute.badge');
    if (badge) {
        if (count > 0) {
            badge.textContent = count > 99 ? '99+' : count;
            badge.style.display = 'block';
        } else {
            badge.style.display = 'none';
        }
    }
}

function updateNotificationsList(notifications) {
    const list = document.getElementById('notifications-list');
    if (!list) return;
    
    if (notifications.length === 0) {
        list.innerHTML = `
            <div class="dropdown-item-text text-center text-muted py-3">
                <i class="bi bi-bell-slash"></i><br>
                No new notifications
            </div>
        `;
        return;
    }
    
    list.innerHTML = notifications.map(notification => `
        <div class="dropdown-item notification-item ${notification.read_at ? '' : 'fw-bold'}" 
             data-id="${notification.id}">
            <div class="d-flex">
                <div class="me-2">
                    <i class="bi bi-${getNotificationIcon(notification.type)} text-${getNotificationColor(notification.type)}"></i>
                </div>
                <div class="flex-grow-1">
                    <div class="small">${notification.title}</div>
                    <div class="text-muted small">${notification.message}</div>
                    <div class="text-muted small">${formatTimeAgo(notification.created_at)}</div>
                </div>
            </div>
        </div>
    `).join('');
    
    // Add click listeners to mark as read
    list.querySelectorAll('.notification-item').forEach(item => {
        item.addEventListener('click', function() {
            markNotificationAsRead(this.dataset.id);
        });
    });
}

function getNotificationIcon(type) {
    const icons = {
        'attendance': 'clipboard-check',
        'schedule': 'calendar',
        'message': 'chat-dots',
        'timesheet': 'file-text',
        'absence': 'calendar-x',
        'default': 'bell'
    };
    return icons[type] || icons.default;
}

function getNotificationColor(type) {
    const colors = {
        'attendance': 'success',
        'schedule': 'info',
        'message': 'primary',
        'timesheet': 'warning',
        'absence': 'danger',
        'default': 'secondary'
    };
    return colors[type] || colors.default;
}

function markNotificationAsRead(id) {
    fetch(`/instructor/notifications/${id}/read`, {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            loadNotifications(); // Refresh notifications
        }
    })
    .catch(error => {
        console.error('Mark notification error:', error);
    });
}

// Tooltips initialization
function initializeTooltips() {
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
}

// Auto refresh for time-sensitive data
function initializeAutoRefresh() {
    // Refresh current time every second
    setInterval(updateCurrentTime, 1000);
    
    // Refresh dashboard stats every 5 minutes
    if (window.location.pathname.includes('/instructor/dashboard')) {
        setInterval(refreshDashboardStats, 300000);
    }
}

function updateCurrentTime() {
    const timeElement = document.getElementById('current-time');
    if (timeElement) {
        const now = new Date();
        timeElement.textContent = now.toLocaleTimeString();
    }
}

function refreshDashboardStats() {
    fetch('/instructor/dashboard/stats')
        .then(response => response.json())
        .then(data => {
            // Update stats cards
            Object.keys(data).forEach(key => {
                const element = document.getElementById(`stat-${key}`);
                if (element) {
                    element.textContent = data[key];
                }
            });
        })
        .catch(error => {
            console.error('Stats refresh error:', error);
        });
}

// Utility functions
function showNotification(type, message) {
    // Create notification toast
    const toast = document.createElement('div');
    toast.className = `alert alert-${type === 'error' ? 'danger' : type} alert-dismissible fade show position-fixed`;
    toast.style.cssText = 'top: 20px; right: 20px; z-index: 9999; min-width: 300px;';
    toast.innerHTML = `
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;
    
    document.body.appendChild(toast);
    
    // Auto remove after 5 seconds
    setTimeout(() => {
        if (toast.parentNode) {
            toast.parentNode.removeChild(toast);
        }
    }, 5000);
}

function formatTimeAgo(dateString) {
    const now = new Date();
    const date = new Date(dateString);
    const diffInSeconds = Math.floor((now - date) / 1000);
    
    if (diffInSeconds < 60) return 'Just now';
    if (diffInSeconds < 3600) return `${Math.floor(diffInSeconds / 60)}m ago`;
    if (diffInSeconds < 86400) return `${Math.floor(diffInSeconds / 3600)}h ago`;
    return `${Math.floor(diffInSeconds / 86400)}d ago`;
}

// AJAX form handling
function handleAjaxForm(formId, successCallback) {
    const form = document.getElementById(formId);
    if (!form) return;
    
    form.addEventListener('submit', function(e) {
        e.preventDefault();
        
        const formData = new FormData(form);
        const submitBtn = form.querySelector('button[type="submit"]');
        
        if (submitBtn) {
            submitBtn.classList.add('loading');
            submitBtn.disabled = true;
        }
        
        fetch(form.action, {
            method: form.method,
            body: formData,
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showNotification('success', data.message);
                if (successCallback) successCallback(data);
            } else {
                showNotification('error', data.message || 'An error occurred');
            }
        })
        .catch(error => {
            console.error('Form submission error:', error);
            showNotification('error', 'Failed to submit form');
        })
        .finally(() => {
            if (submitBtn) {
                submitBtn.classList.remove('loading');
                submitBtn.disabled = false;
            }
        });
    });
}

// Data table sorting and filtering
function initializeDataTable(tableId) {
    const table = document.getElementById(tableId);
    if (!table) return;
    
    // Add sorting functionality to headers
    const headers = table.querySelectorAll('th[data-sort]');
    headers.forEach(header => {
        header.style.cursor = 'pointer';
        header.addEventListener('click', function() {
            sortTable(table, this.dataset.sort);
        });
    });
}

function sortTable(table, column) {
    // Simple client-side sorting implementation
    const rows = Array.from(table.querySelectorAll('tbody tr'));
    const headerRow = table.querySelector('thead tr');
    const columnIndex = Array.from(headerRow.children).findIndex(th => th.dataset.sort === column);
    
    rows.sort((a, b) => {
        const aValue = a.children[columnIndex].textContent.trim();
        const bValue = b.children[columnIndex].textContent.trim();
        
        if (isNaN(aValue) || isNaN(bValue)) {
            return aValue.localeCompare(bValue);
        } else {
            return parseFloat(aValue) - parseFloat(bValue);
        }
    });
    
    const tbody = table.querySelector('tbody');
    rows.forEach(row => tbody.appendChild(row));
}

// Export functions
function exportData(url, params = {}) {
    const queryString = new URLSearchParams(params).toString();
    const fullUrl = queryString ? `${url}?${queryString}` : url;
    
    // Create temporary link and trigger download
    const link = document.createElement('a');
    link.href = fullUrl;
    link.download = '';
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
}

// Confirmation dialogs
function confirmAction(message, callback) {
    if (confirm(message)) {
        callback();
    }
}

function confirmDelete(itemName, callback) {
    confirmAction(`Are you sure you want to delete ${itemName}? This action cannot be undone.`, callback);
}

// Global error handler for AJAX requests
window.addEventListener('unhandledrejection', function(event) {
    console.error('Unhandled promise rejection:', event.reason);
    showNotification('error', 'An unexpected error occurred');
});
