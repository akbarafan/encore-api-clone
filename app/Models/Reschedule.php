<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Reschedule extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'schedule_id',
        'student_id',
        'instructor_id',
        'new_date',
        'new_start_time',
        'new_end_time',
        'status',
        'reason'
    ];

    protected $casts = [
        'new_date' => 'date',
        'new_start_time' => 'datetime:H:i',
        'new_end_time' => 'datetime:H:i',
    ];

    public function schedule()
    {
        return $this->belongsTo(Schedule::class);
    }

    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    public function instructor()
    {
        return $this->belongsTo(Instructor::class);
    }

    // Check if reschedule is pending
    public function getIsPendingAttribute()
    {
        return $this->status === 'pending';
    }

    // Check if reschedule is approved
    public function getIsApprovedAttribute()
    {
        return $this->status === 'approved';
    }

    // Check if reschedule is rejected
    public function getIsRejectedAttribute()
    {
        return $this->status === 'rejected';
    }

    // Get status badge class for UI
    public function getStatusBadgeClassAttribute()
    {
        return match ($this->status) {
            'pending' => 'bg-warning-subtle text-warning',
            'approved' => 'bg-success-subtle text-success',
            'rejected' => 'bg-danger-subtle text-danger',
            default => 'bg-secondary-subtle text-secondary'
        };
    }

    // Get requester (student or instructor)
    public function getRequesterAttribute()
    {
        return $this->student_id ? $this->student : $this->instructor;
    }

    // Get requester type
    public function getRequesterTypeAttribute()
    {
        return $this->student_id ? 'student' : 'instructor';
    }
}
