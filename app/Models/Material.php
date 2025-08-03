<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Material extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'materials';

    protected $fillable = [
        'instructor_id',
        'schedule_id',
        'file_id',
        'material_type',
        'title',
        'description',
        'instructions',
        'is_mandatory',
        'available_from',
        'due_date',
        'is_active',
    ];

    protected $casts = [
        'available_from' => 'datetime',
        'due_date' => 'datetime',
        'is_mandatory' => 'boolean',
        'is_active' => 'boolean',
    ];

    /**
     * Relationship dengan Instructor
     */
    public function instructor()
    {
        return $this->belongsTo(Instructor::class);
    }

    /**
     * Relationship dengan Schedule
     */
    public function schedule()
    {
        return $this->belongsTo(Schedule::class);
    }

    /**
     * Relationship dengan File
     */
    public function file()
    {
        return $this->belongsTo(File::class);
    }

    /**
     * Scope untuk filter berdasarkan tipe aktivitas
     */
    public function scopeByActivityType($query, $type)
    {
        return $query->where('material_type', $type);
    }

    /**
     * Scope untuk aktivitas yang aktif
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope untuk aktivitas yang wajib
     */
    public function scopeMandatory($query)
    {
        return $query->where('is_mandatory', true);
    }

    /**
     * Scope untuk filter berdasarkan instructor
     */
    public function scopeByInstructor($query, $instructorId)
    {
        return $query->where('instructor_id', $instructorId);
    }

    /**
     * Scope untuk filter berdasarkan schedule
     */
    public function scopeBySchedule($query, $scheduleId)
    {
        return $query->where('schedule_id', $scheduleId);
    }

    /**
     * Check apakah aktivitas sudah tersedia
     */
    public function isAvailable()
    {
        if (!$this->available_from) {
            return true;
        }

        return now() >= $this->available_from;
    }

    /**
     * Check apakah aktivitas sudah lewat due date
     */
    public function isOverdue()
    {
        if (!$this->due_date) {
            return false;
        }

        return now() > $this->due_date;
    }

    /**
     * Get label untuk activity type
     */
    public function getActivityTypeLabel()
    {
        return match($this->material_type) {
            'pre_class' => 'Pre-Class Material',
            'post_class' => 'Post-Class Activity',
            default => 'Unknown'
        };
    }

    /**
     * Get status aktivitas
     */
    public function getStatusAttribute()
    {
        if (!$this->is_active) {
            return 'inactive';
        }

        if (!$this->isAvailable()) {
            return 'upcoming';
        }

        if ($this->isOverdue()) {
            return 'overdue';
        }

        return 'active';
    }
}
