<?php

use App\Http\Controllers\Auth\LoginController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Instructor\DashboardController;
use App\Http\Controllers\Instructor\ClassController;
use App\Http\Controllers\Instructor\StudentController;
use App\Http\Controllers\Instructor\ScheduleController;
use App\Http\Controllers\Instructor\LogHourController;
use App\Http\Controllers\Instructor\TimesheetController;
use App\Http\Controllers\Instructor\MaterialController;
use App\Http\Controllers\Instructor\MessageActivityController;
use App\Http\Controllers\Instructor\MessageClassController;

Route::get('/', function () {
    return view('welcome');
});

// Authentication Routes
Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
Route::post('/login', [LoginController::class, 'login'])->name('login.post');

Route::middleware(['auth', 'admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/dashboard', function () {
        return view('admin.dashboard.index');
    })->name('dashboard');
});

// New instructor routes with prefix
Route::middleware(['auth', 'instructor'])->prefix('instructor')->name('instructor.')->group(
    function () {
    Route::post('/logout', [LoginController::class, 'logout'])->name('logout');

    Route::get('/dashboard', [App\Http\Controllers\Instructor\DashboardController::class, 'index'])->name('dashboard.index');
    Route::get('/dashboard/chart-data', [App\Http\Controllers\Instructor\DashboardController::class, 'getChartData'])->name('dashboard.chart-data');

    // Classes
    Route::get('/classes', [ClassController::class, 'index'])->name('classes.index');
    Route::post('/classes', [ClassController::class, 'store'])->name('classes.store');
    Route::put('/classes/{id}', [ClassController::class, 'update'])->name('classes.update');
    Route::delete('/classes/{id}', [ClassController::class, 'destroy'])->name('classes.destroy');

    // Students (new URL structure)
    Route::get('/students', [StudentController::class, 'index'])->name('students.index');
    Route::get('/students/{students}', [StudentController::class, 'show'])->name('students.show');
    Route::post('/students/export', [StudentController::class, 'export'])->name('students.export');
    Route::get('/students/{student}/attendance', [StudentController::class, 'attendance'])->name('students.attendance');

    // Schedules
    Route::get('/schedules', [ScheduleController::class, 'index'])->name('schedules.index');
    Route::post('/schedules', [ScheduleController::class, 'store'])->name('schedules.store');
    Route::get('/schedules/{id}', [ScheduleController::class, 'show'])->name('schedules.show');
    Route::put('/schedules/{id}', [ScheduleController::class, 'update'])->name('schedules.update');
    Route::delete('/schedules/{id}', [ScheduleController::class, 'destroy'])->name('schedules.destroy');

    Route::post('schedules/{schedule}/reschedule', [ScheduleController::class, 'requestReschedule'])->name('schedules.reschedule');
    Route::delete('schedules/{schedule}/cancel-reschedule', [ScheduleController::class, 'cancelReschedule'])->name('schedules.cancel-reschedule');

    // Materials (was Message Activities)
    Route::get('/schedules/{schedule}/materials', [MaterialController::class, 'getScheduleMaterials'])->name('schedules.materials');
    Route::post('/materials', [MaterialController::class, 'store'])->name('materials.store');
    Route::get('/materials/{material}', [MaterialController::class, 'show'])->name('materials.show');
    Route::put('/materials/{material}', [MaterialController::class, 'update'])->name('materials.update');
    Route::delete('/materials/{material}', [MaterialController::class, 'destroy'])->name('materials.destroy');
    Route::get('/files/{file}/download', [MaterialController::class, 'downloadFile'])->name('files.download');

    // Message Activities (Teacher posting activities to class)
    Route::get('/message-activities', [MessageActivityController::class, 'index'])->name('message-activities.index');

    Route::post('/message-activities', [MessageActivityController::class, 'store'])->name('message-activities.store');
    Route::get('/message-activities/{id}', [MessageActivityController::class, 'show'])->name('message-activities.show');
    Route::put('/message-activities/{id}', [MessageActivityController::class, 'update'])->name('message-activities.update');
    Route::delete('/message-activities/{id}', [MessageActivityController::class, 'destroy'])->name('message-activities.destroy');
    Route::post('/message-activities/{id}/toggle-pin', [MessageActivityController::class, 'togglePin'])->name('message-activities.toggle-pin');
    Route::post('/message-activities/{id}/toggle-active', [MessageActivityController::class, 'toggleActive'])->name('message-activities.toggle-active');
    Route::get('/classes/{class}/activities', [MessageActivityController::class, 'getClassActivities'])->name('classes.activities');
    Route::get('/message-activities/{id}/download/{attachment}', [MessageActivityController::class, 'downloadAttachment'])->name('message-activities.download');

        // Chat/Messaging
    Route::prefix('chat')->group(function () {
        Route::get('/', [MessageClassController::class, 'index'])->name('chat.index');
        Route::get('/class/{classId}/messages', [MessageClassController::class, 'getClassMessages'])->name('chat.messages');
        Route::post('/send', [MessageClassController::class, 'sendMessage'])->name('chat.send');
        Route::get('/class/{classId}/participants', [MessageClassController::class, 'getClassParticipants'])->name('chat.participants');
        Route::delete('/message/{messageId}', [MessageClassController::class, 'deleteMessage'])->name('chat.delete');
        Route::post('/message/{messageId}/pin', [MessageClassController::class, 'togglePin'])->name('chat.pin');
        Route::get('/summary', [MessageClassController::class, 'getConversationSummary'])->name('chat.summary');

        // Real-time features
        Route::post('/typing', [MessageClassController::class, 'updateTypingStatus'])->name('chat.typing');
        Route::post('/online', [MessageClassController::class, 'updateOnlineStatus'])->name('chat.online');
    });

    // Log Hours
    Route::get('/log-hours', [LogHourController::class, 'index'])->name('log-hours.index');
    Route::post('/log-hours', [LogHourController::class, 'store'])->name('log-hours.store');
    Route::get('/log-hours/{id}', [LogHourController::class, 'show'])->name('log-hours.show');
    Route::put('/log-hours/{id}', [LogHourController::class, 'update'])->name('log-hours.update');
    Route::delete('/log-hours/{id}', [LogHourController::class, 'destroy'])->name('log-hours.destroy');
    Route::post('/log-hours/clock-in', [LogHourController::class, 'clockIn'])->name('log-hours.clock-in');
    Route::post('/log-hours/clock-out', [LogHourController::class, 'clockOut'])->name('log-hours.clock-out');

    // Timesheets
    Route::get('/timesheets', [TimesheetController::class, 'index'])->name('timesheets.index');
    Route::post('/timesheets', [TimesheetController::class, 'store'])->name('timesheets.store');
    Route::put('/timesheets/{id}', [TimesheetController::class, 'update'])->name('timesheets.update');
    Route::delete('/timesheets/{id}', [TimesheetController::class, 'destroy'])->name('timesheets.destroy');
    Route::post('/timesheets/generate', [TimesheetController::class, 'generateFromLogHours'])->name('timesheets.generate');
    Route::get('/timesheets/quick-generate/{month}', [TimesheetController::class, 'quickGenerate'])->name('timesheets.quick-generate');
});

// Student Routes
Route::middleware(['auth', 'student'])->prefix('student')->name('student.')->group(function () {
    Route::get('/dashboard', function () {
        return view('student.dashboard.index');
    })->name('dashboard');

    // Schedule Responses
    Route::get('/schedule-responses', [App\Http\Controllers\Student\ScheduleResponseController::class, 'index'])->name('schedule-responses.index');
    Route::get('/schedule-responses/{disruption}', [App\Http\Controllers\Student\ScheduleResponseController::class, 'show'])->name('schedule-responses.show');
    Route::post('/schedule-responses/{disruption}', [App\Http\Controllers\Student\ScheduleResponseController::class, 'store'])->name('schedule-responses.store');
    Route::put('/schedule-responses/{disruption}', [App\Http\Controllers\Student\ScheduleResponseController::class, 'update'])->name('schedule-responses.update');
});

// Quick test route (remove after testing)
Route::get('/quick-student-test', function() {
    \Auth::loginUsingId('019823e2-ec55-7127-ab0a-a819f111f47d');
    return redirect()->route('student.schedule-responses.index');
});
