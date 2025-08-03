<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Student extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'first_name',
        'last_name',
        'email',
        'phone',
        'date_of_birth',
        'family_id',
        'emergency_contact',
        'medical_notes',
        'gender',
        'medical_condition',
        'one_time_reg_fee'
    ];

    protected $casts = [
        'date_of_birth' => 'date',
        'one_time_reg_fee' => 'decimal:2'
    ];

    protected $appends = ['name'];

    // Accessor untuk nama lengkap
    public function getNameAttribute()
    {
        return $this->first_name . ' ' . $this->last_name;
    }

    /**
     * Relations
     */

    // Relasi ke Family
    public function family()
    {
        return $this->belongsTo(Family::class);
    }



    // Relasi ke Classes melalui tabel enrolls
    public function classes()
    {
        return $this->belongsToMany(Classes::class, 'enrolls', 'student_id', 'class_id')
            ->withTimestamps()
            ->withPivot('date', 'status')
            ->using(Enroll::class);
    }

    // Relasi ke Enroll
    public function enrolls()
    {
        return $this->hasMany(Enroll::class);
    }

    // Relasi ke active enrollments
    public function activeEnrolls()
    {
        return $this->hasMany(Enroll::class)->where('status', 'active');
    }

    // Relasi ke active classes melalui enrolls
    public function activeClasses()
    {
        return $this->belongsToMany(Classes::class, 'enrolls', 'student_id', 'class_id')
            ->wherePivot('status', 'active')
            ->withTimestamps()
            ->withPivot('date', 'status')
            ->using(Enroll::class);
    }

    // Relasi ke Attendance
    public function attendances()
    {
        return $this->hasMany(Attendance::class);
    }

    // Relasi ke Reschedules (jika student request reschedule)
    public function reschedules()
    {
        return $this->hasMany(Reschedule::class);
    }

    /**
     * Scopes
     */

    // Scope untuk student dengan enrollments aktif
    public function scopeActive($query)
    {
        return $query->whereHas('enrolls', function ($q) {
            $q->where('status', 'active');
        });
    }

    // Scope untuk student berdasarkan instructor
    public function scopeByInstructor($query, $instructorId)
    {
        return $query->whereHas('classes', function ($q) use ($instructorId) {
            $q->where('instructor_id', $instructorId);
        });
    }

    // Scope untuk student yang enrolled di class tertentu
    public function scopeEnrolledInClass($query, $classId)
    {
        return $query->whereHas('enrolls', function ($q) use ($classId) {
            $q->where('class_id', $classId)->where('status', 'active');
        });
    }

    /**
     * Methods
     */

    // Get current active classes
    public function getCurrentClasses()
    {
        return $this->classes()
            ->wherePivot('status', 'active')
            ->with(['instructor', 'season', 'category', 'type', 'classTime', 'classLocation'])
            ->get();
    }

    // Get schedules for student's enrolled classes
    public function getSchedules($startDate = null, $endDate = null)
    {
        $query = Schedule::whereHas('class.students', function ($q) {
            $q->where('student_id', $this->id)
                ->where('enrolls.status', 'active');
        })->with(['class', 'files']);

        if ($startDate) {
            $query->where('date', '>=', $startDate);
        }

        if ($endDate) {
            $query->where('date', '<=', $endDate);
        }

        return $query->orderBy('date')->orderBy('start_time')->get();
    }

    // Get available files for student
    public function getAvailableFiles($scheduleId = null)
    {
        $query = File::whereHas('schedules.class.students', function ($q) {
            $q->where('student_id', $this->id)
                ->where('enrolls.status', 'active');
        })
            ->whereHas('schedules', function ($q) {
                $q->where(function ($subQuery) {
                    $subQuery->whereNull('schedule_files.available_from')
                        ->orWhere('schedule_files.available_from', '<=', now());
                })
                    ->where(function ($subQuery) {
                        $subQuery->whereNull('schedule_files.available_until')
                            ->orWhere('schedule_files.available_until', '>=', now());
                    });
            });

        if ($scheduleId) {
            $query->whereHas('schedules', function ($q) use ($scheduleId) {
                $q->where('schedules.id', $scheduleId);
            });
        }

        return $query->with(['uploader', 'schedules'])->get();
    }

    // Check if student is enrolled in specific class
    public function isEnrolledInClass($classId)
    {
        return $this->enrolls()
            ->where('class_id', $classId)
            ->where('status', 'active')
            ->exists();
    }

    // Get student's learning progress
    public function getLearningProgress()
    {
        $enrolledClasses = $this->getCurrentClasses();
        $totalSchedules = 0;
        $attendedSchedules = 0;
        $totalFiles = 0;

        foreach ($enrolledClasses as $class) {
            $classSchedules = $class->schedules()->where('date', '<=', now())->count();
            $totalSchedules += $classSchedules;

            $attendedCount = $this->attendances()
                ->whereHas('schedule.class', function ($q) use ($class) {
                    $q->where('id', $class->id);
                })
                ->where('status', 'present')
                ->count();
            $attendedSchedules += $attendedCount;

            $classFiles = File::whereHas('schedules.class', function ($q) use ($class) {
                $q->where('id', $class->id);
            })->count();
            $totalFiles += $classFiles;
        }

        return [
            'enrolled_classes' => $enrolledClasses->count(),
            'attendance_rate' => $totalSchedules > 0 ? round(($attendedSchedules / $totalSchedules) * 100, 2) : 0,
            'total_schedules' => $totalSchedules,
            'attended_schedules' => $attendedSchedules,
            'total_files' => $totalFiles,
        ];
    }
}
