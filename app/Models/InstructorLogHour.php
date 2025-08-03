<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Carbon\Carbon;

class InstructorLogHour extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'instructor_id',
        'schedule_id',
        'date',
        'clock_in',
        'clock_out',
        'activity_type',
        'clock_in_notes',
        'clock_out_notes',
        'approval_status',
        'causes_disruption',
        'disruption_status',
    ];

    protected $casts = [
        'date' => 'date:Y-m-d',
        'clock_in' => 'datetime',
        'clock_out' => 'datetime',
        'causes_disruption' => 'boolean',
    ];

    protected $dates = [
        'date',
        'clock_in',
        'clock_out',
        'deleted_at',
    ];

    public function instructor()
    {
        return $this->belongsTo(Instructor::class);
    }

    public function schedule()
    {
        return $this->belongsTo(Schedule::class);
    }

    public function scheduleDisruptions()
    {
        return $this->hasMany(ScheduleDisruption::class);
    }

    // Scope untuk filter berdasarkan instructor
    public function scopeByInstructor($query, $instructorId)
    {
        return $query->where('instructor_id', $instructorId);
    }

    // Scope untuk filter berdasarkan status
    public function scopeByStatus($query, $status)
    {
        switch ($status) {
            case 'clocked_in':
                return $query->whereNotNull('clock_in')->whereNull('clock_out');
            case 'completed':
                return $query->whereNotNull('clock_in')->whereNotNull('clock_out');
            case 'draft':
                return $query->whereNull('clock_in');
            default:
                return $query;
        }
    }

    // Scope untuk filter berdasarkan activity type
    public function scopeByActivityType($query, $activityType)
    {
        return $query->where('activity_type', $activityType);
    }

    // Scope untuk filter berdasarkan approval status
    public function scopeByApprovalStatus($query, $approvalStatus)
    {
        return $query->where('approval_status', $approvalStatus);
    }

    // Helper method untuk get activity type label
    public function getActivityTypeLabel()
    {
        return match($this->activity_type) {
            'teaching' => 'Teaching',
            'admin' => 'Admin Work',
            'overtime' => 'Overtime',
            'time_off' => 'Time Off',
            'sick' => 'Sick Leave',
            default => 'Unknown'
        };
    }

    // Helper method untuk get approval status label
    public function getApprovalStatusLabel()
    {
        return match($this->approval_status) {
            'pending' => 'Pending',
            'approved' => 'Approved',
            'rejected' => 'Rejected',
            default => 'Unknown'
        };
    }
}
