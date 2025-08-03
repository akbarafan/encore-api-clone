<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Schedule extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'class_id',
        'title',
        'date',
        'start_time',
        'end_time',
        'notes',
        'status',
        'original_schedule_id'
    ];

    protected $casts = [
        'date' => 'date',
        'start_time' => 'datetime:H:i',
        'end_time' => 'datetime:H:i',
    ];

    protected $appends = [
        'is_rescheduled',
        'actual_date',
        'actual_start_time',
        'actual_end_time',
        'files_count'
    ];

    public function class()
    {
        return $this->belongsTo(Classes::class, 'class_id');
    }

    public function reschedules()
    {
        return $this->hasMany(Reschedule::class);
    }

    public function approvedReschedules()
    {
        return $this->hasMany(Reschedule::class)->where('status', 'approved');
    }

    public function pendingReschedules()
    {
        return $this->hasMany(Reschedule::class)->where('status', 'pending');
    }

    // NEW: File relationships
    public function files()
    {
        return $this->belongsToMany(File::class, 'schedule_files')
            ->withPivot('title', 'description', 'order', 'is_required', 'available_from', 'available_until')
            ->withTimestamps()
            ->orderBy('pivot_order');
    }

    public function scheduleFiles()
    {
        return $this->hasMany(ScheduleFile::class);
    }

    public function availableFiles()
    {
        return $this->files()->wherePivot('available_from', '<=', now())
            ->where(function ($query) {
                $query->whereNull('schedule_files.available_until')
                    ->orWherePivot('available_until', '>=', now());
            });
    }

    // Get the latest approved reschedule
    public function getLatestApprovedRescheduleAttribute()
    {
        return $this->reschedules()->where('status', 'approved')->latest()->first();
    }

    // Check if schedule has been rescheduled
    public function getIsRescheduledAttribute()
    {
        return $this->reschedules()->where('status', 'approved')->exists();
    }

    // Get actual schedule details (considering reschedules)
    public function getActualDateAttribute()
    {
        $latestReschedule = $this->latest_approved_reschedule;
        return $latestReschedule ? $latestReschedule->new_date : $this->date;
    }

    public function getActualStartTimeAttribute()
    {
        $latestReschedule = $this->latest_approved_reschedule;
        return $latestReschedule ? $latestReschedule->new_start_time : $this->start_time;
    }

    public function getActualEndTimeAttribute()
    {
        $latestReschedule = $this->latest_approved_reschedule;
        return $latestReschedule ? $latestReschedule->new_end_time : $this->end_time;
    }

    // NEW: Get files count
    public function getFilesCountAttribute()
    {
        return $this->files()->count();
    }

    // NEW: Materials relationships
    public function materials()
    {
        return $this->hasMany(Material::class);
    }

    public function preClassMaterials()
    {
        return $this->materials()->where('activity_type', 'pre_class')->active();
    }

    public function postClassMaterials()
    {
        return $this->materials()->where('activity_type', 'post_class')->active();
    }

    // NEW: Schedule Disruption relationships
    public function scheduleDisruptions()
    {
        return $this->hasMany(ScheduleDisruption::class);
    }

    public function originalSchedule()
    {
        return $this->belongsTo(Schedule::class, 'original_schedule_id');
    }

    public function rescheduledSchedules()
    {
        return $this->hasMany(Schedule::class, 'original_schedule_id');
    }



    // Check if schedule is disrupted
    public function getIsDisruptedAttribute()
    {
        return $this->status === 'disrupted';
    }

    // Get pending disruptions
    public function pendingDisruptions()
    {
        return $this->scheduleDisruptions()->where('status', 'pending');
    }
}
