<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ClassCategoryController;
use App\Http\Controllers\ClassesController;
use App\Http\Controllers\ClassLocationController;
use App\Http\Controllers\ClassRescheduleController;
use App\Http\Controllers\ClassStudentController;
use App\Http\Controllers\ClassTimeController;
use App\Http\Controllers\ClassTypeController;
use App\Http\Controllers\ContactTypeController;
use App\Http\Controllers\FileController;
use App\Http\Controllers\InstructorController;
use App\Http\Controllers\MessageController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\RescheduleController;
use App\Http\Controllers\ScheduleController;
use App\Http\Controllers\ScheduleFileController;
use App\Http\Controllers\SeasonController;
use App\Http\Controllers\StudentController;
use App\Http\Controllers\TimesheetController;
use App\Http\Controllers\ActivitiesController;
use App\Http\Controllers\MessageClassController;
use App\Http\Controllers\MessageActivityApiController;

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

// Contact Types routes
Route::get('/contact-types', [ContactTypeController::class, 'index']);

Route::middleware(['auth:api'])->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);

    // Profile routes
    Route::get('/profile', [AuthController::class, 'getProfile']);

    // Notifications - Bearer Token Required
    Route::get('/notifications', [NotificationController::class, 'index']);
    Route::post('/notifications', [NotificationController::class, 'store']);
    Route::patch('/notifications/{id}/read', [NotificationController::class, 'markAsRead']);
    Route::patch('/notifications/mark-all-read', [NotificationController::class, 'markAllAsRead']);

    // Contact Types routes
    Route::post('/contact-types', [ContactTypeController::class, 'store']);
    Route::get('/contact-types/{id}', [ContactTypeController::class, 'show']);
    Route::put('/contact-types/{id}', [ContactTypeController::class, 'update']);
    Route::delete('/contact-types/{id}', [ContactTypeController::class, 'destroy']);
    // Route::post('/contact-types/{id}/restore', [ContactTypeController::class, 'restore']);
    // Route::get('/contact-types-deleted', [ContactTypeController::class, 'deleted']);

    // Family routes
    Route::post('/family/create', [AuthController::class, 'createFamily']);
    Route::get('/family', [AuthController::class, 'getFamily']);
    Route::put('/family', [AuthController::class, 'updateFamily']);
    Route::delete('/family', [AuthController::class, 'deleteFamily']);


    // Basic CRUD
    Route::apiResource('students', StudentController::class);

    // Student specific routes
    Route::prefix('students/{student}')->group(function () {
        Route::get('classes', [StudentController::class, 'getClasses'])->name('students.classes');
        Route::get('schedules', [StudentController::class, 'getSchedules'])->name('students.schedules');
        Route::get('schedules/today', [StudentController::class, 'getTodaySchedule'])->name('students.schedules.today');
        Route::get('schedules/upcoming', [StudentController::class, 'getUpcomingSchedules'])->name('students.schedules.upcoming');
        Route::get('files/available', [StudentController::class, 'getAvailableFiles'])->name('students.files.available');
        Route::get('progress', [StudentController::class, 'getLearningProgress'])->name('students.progress');
        Route::get('attendance', [StudentController::class, 'getAttendanceHistory'])->name('students.attendance');
        Route::get('dashboard', [StudentController::class, 'getDashboard'])->name('students.dashboard');
        Route::get('enrollment/{class}', [StudentController::class, 'checkEnrollment'])->name('students.enrollment.check');
    });


    // Instructors routes
    Route::resource('/instructors', InstructorController::class);

    // Instructor Log Hours routes
    Route::resource('/instructor-log-hours', InstructorController::class);

    // Timesheet routes
    Route::resource('/timesheets', TimesheetController::class);

    // Class routes
    Route::resource('/classes', ClassesController::class);

    // Additional Class routes
    Route::prefix('classes')->group(function () {
        Route::resource('/', ClassesController::class);
        Route::put('/{id}/approve', [ClassesController::class, 'approve']);
        Route::put('/{id}/reject', [ClassesController::class, 'reject']);
        Route::get('/instructor/{instructorId}', [ClassesController::class, 'getByInstructor']);
        Route::get('/season/{seasonId}', [ClassesController::class, 'getBySeason']);
        Route::get('/approved', [ClassesController::class, 'getApproved']);
        Route::get('/student/{student_id}', [ClassesController::class, 'getStudentClasses']);
        Route::post('/enrollment', [ClassesController::class, 'enrollClass']);
    });



    // Class Seasons routes
    Route::apiResource('/seasons', SeasonController::class);



    // Class Types routes


    // Attendance routes
    Route::apiResource('attendances', AttendanceController::class);


    // Files CRUD
    Route::apiResource('files', FileController::class);

    // File download and statistics
    Route::get('files/{id}/download', [FileController::class, 'download'])->name('files.download');
    Route::get('files/{id}/stats', [FileController::class, 'downloadStats'])->name('files.stats');

    // Activities API routes (Full CRUD)
    Route::prefix('activities')->group(function () {
        Route::get('/', [ActivitiesController::class, 'index'])->name('api.activities.index');
        Route::post('/', [ActivitiesController::class, 'store'])->name('api.activities.store');
        Route::get('/{id}', [ActivitiesController::class, 'show'])->name('api.activities.show');
        Route::put('/{id}', [ActivitiesController::class, 'update'])->name('api.activities.update');
        Route::delete('/{id}', [ActivitiesController::class, 'destroy'])->name('api.activities.destroy');
        Route::get('/student/{student_id}', [ActivitiesController::class, 'studentActivities'])->name('api.activities.student');
        Route::get('/student/{student_id}/stats', [ActivitiesController::class, 'studentStats'])->name('api.activities.student.stats');
        Route::get('/files/{file_id}/download', [ActivitiesController::class, 'download'])->name('api.activities.download');
    });

    // Message Activities API routes (Full CRUD + Advanced Features)
    Route::prefix('message-activities')->group(function () {
        // Basic CRUD
        Route::get('/', [MessageActivityApiController::class, 'index'])->name('api.message-activities.index');
        Route::post('/', [MessageActivityApiController::class, 'store'])->name('api.message-activities.store');
        Route::get('/{id}', [MessageActivityApiController::class, 'show'])->name('api.message-activities.show');
        Route::put('/{id}', [MessageActivityApiController::class, 'update'])->name('api.message-activities.update');
        Route::delete('/{id}', [MessageActivityApiController::class, 'destroy'])->name('api.message-activities.destroy');

        // Toggle actions
        Route::post('/{id}/toggle-pin', [MessageActivityApiController::class, 'togglePin'])->name('api.message-activities.toggle-pin');
        Route::post('/{id}/toggle-active', [MessageActivityApiController::class, 'toggleActive'])->name('api.message-activities.toggle-active');

        // Filtering routes
        Route::get('/instructor/{instructor_id}', [MessageActivityApiController::class, 'getByInstructor'])->name('api.message-activities.instructor');
        Route::get('/class/{class_id}', [MessageActivityApiController::class, 'getByClass'])->name('api.message-activities.class');
        Route::get('/today', [MessageActivityApiController::class, 'getTodayActivities'])->name('api.message-activities.today');
        Route::get('/pinned', [MessageActivityApiController::class, 'getPinned'])->name('api.message-activities.pinned');

        // File download
        Route::get('/{id}/download/{attachment_index}', [MessageActivityApiController::class, 'downloadAttachment'])->name('api.message-activities.download');

        // Statistics
        Route::get('/statistics', [MessageActivityApiController::class, 'getStatistics'])->name('api.message-activities.statistics');
    });

    // Schedule Files Management
    Route::prefix('schedules/{schedule}')->group(function () {
        Route::get('files', [ScheduleFileController::class, 'index'])->name('schedules.files.index');
        Route::post('files', [ScheduleFileController::class, 'store'])->name('schedules.files.store');
        Route::put('files/{file}', [ScheduleFileController::class, 'update'])->name('schedules.files.update');
        Route::delete('files/{file}', [ScheduleFileController::class, 'destroy'])->name('schedules.files.destroy');
        Route::post('files/reorder', [ScheduleFileController::class, 'reorder'])->name('schedules.files.reorder');
        Route::post('files/bulk-attach', [ScheduleFileController::class, 'bulkAttach'])->name('schedules.files.bulk-attach');
        Route::get('files/available', [ScheduleFileController::class, 'availableFiles'])->name('schedules.files.available');
    });

    // Schedules CRUD
    Route::apiResource('/schedules', ScheduleController::class);

    // Enhanced Schedule Routes
    Route::get('schedules/today', [ScheduleController::class, 'today'])->name('schedules.today');
    Route::get('schedules/upcoming', [ScheduleController::class, 'upcoming'])->name('schedules.upcoming');
    Route::get('schedules/statistics', [ScheduleController::class, 'statistics'])->name('schedules.statistics');


    // Reschedules CRUD
    Route::apiResource('/reschedules', RescheduleController::class);

    // Custom actions for reschedules
    Route::put('/reschedules/{id}/approve', [RescheduleController::class, 'approve']);
    Route::put('/reschedules/{id}/reject', [RescheduleController::class, 'reject']);

    // Class Student Enrollment routes
    Route::post('/students/{studentId}/enroll', [ClassStudentController::class, 'enrollStudent']);
    Route::post('/students/{studentId}/unenroll', [ClassStudentController::class, 'unenrollStudent']);
    Route::get('/my-students/classes', [ClassStudentController::class, 'myStudentsClasses']);

    // Additional Class Student routes
    Route::get('/classes/{classId}/students', [ClassStudentController::class, 'getClassStudents']);
    Route::get('/students/{studentId}/classes/{classId}/status', [ClassStudentController::class, 'getEnrollmentStatus']);
    Route::post('/students/{studentId}/bulk-enroll', [ClassStudentController::class, 'bulkEnrollStudent']);
    Route::post('/students/{studentId}/bulk-unenroll', [ClassStudentController::class, 'bulkUnenrollStudent']);

    // Class Reschedule routes
    Route::get('/class-reschedules', [ClassRescheduleController::class, 'index']);
    Route::post('/classes/{classId}/reschedule', [ClassRescheduleController::class, 'requestReschedule']);
    Route::post('/class-reschedules/{id}/approve', [ClassRescheduleController::class, 'approveReschedule']);
    Route::post('/class-reschedules/{id}/reject', [ClassRescheduleController::class, 'rejectReschedule']);
    Route::delete('/class-reschedules/{id}', [ClassRescheduleController::class, 'destroy']);


    // Chat routes
    Route::prefix('chat')->group(function () {
        // Get family students (for family to choose which student's chat to view)
        Route::get('/family/students', [MessageClassController::class, 'getFamilyStudents']);
        
        // Get conversations
        Route::get('/conversations', [MessageClassController::class, 'getConversations']);

        // Get messages for a class
        Route::get('/class/{classId}/messages', [MessageClassController::class, 'getMessages']);

        // Send message
        Route::post('/send', [MessageClassController::class, 'sendMessage']);

        // Delete message
        Route::delete('/message/{messageId}', [MessageClassController::class, 'deleteMessage']);

        // Toggle pin message
        Route::post('/message/{messageId}/pin', [MessageClassController::class, 'togglePin']);

        // Real-time features
        Route::post('/typing', [MessageClassController::class, 'updateTypingStatus']);
        Route::post('/online', [MessageClassController::class, 'updateOnlineStatus']);
    });

    // Class Location routes
    Route::prefix('class-locations')->group(
        function () {
            Route::get('/', [ClassLocationController::class, 'getData']);
            Route::post('/', [ClassLocationController::class, 'store']);
            Route::get('/{id}', [ClassLocationController::class, 'show']);
            Route::put('/{id}', [ClassLocationController::class, 'update']);
            Route::delete('/{id}', [ClassLocationController::class, 'destroy']);
        }
    );
    Route::prefix('class-times')->group(
        function () {
            Route::get('/', [ClassTimeController::class, 'getData']);
            Route::post('/', [ClassTimeController::class, 'store']);
            Route::get('/{id}', [ClassTimeController::class, 'show']);
            Route::put('/{id}', [ClassTimeController::class, 'update']);
            Route::delete('/{id}', [ClassTimeController::class, 'destroy']);
        }
    );
    Route::prefix('class-types')->group(
        function () {
            Route::get('/', [ClassTypeController::class, 'getData']);
            Route::post('/', [ClassTypeController::class, 'store']);
            Route::get('/{id}', [ClassTypeController::class, 'show']);
            Route::put('/{id}', [ClassTypeController::class, 'update']);
            Route::delete('/{id}', [ClassTypeController::class, 'destroy']);
        }
    );
    Route::prefix('class-categories')->group(
        function () {
            Route::get('/', [ClassCategoryController::class, 'getData']);
            Route::post('/', [ClassCategoryController::class, 'store']);
            Route::get('/{id}', [ClassCategoryController::class, 'show']);
            Route::put('/{id}', [ClassCategoryController::class, 'update']);
            Route::delete('/{id}', [ClassCategoryController::class, 'destroy']);
        }
    );
});
