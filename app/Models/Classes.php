<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Classes extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'season_id',
        'class_category_id',
        'class_type_id',
        'class_time_id',
        'class_location_id',
        'name',
        'description',
        'instructor_id',
        'cost',
        'scheduled_at',
        'is_approved',
    ];

    protected $casts = [
        'scheduled_at' => 'datetime',
        'is_approved' => 'boolean',
        'cost' => 'decimal:2',
    ];

    /**
     * Relations
     */

    // Relasi ke Season
    public function season()
    {
        return $this->belongsTo(Season::class);
    }

    // Relasi ke ClassTime
    public function classTime()
    {
        return $this->belongsTo(ClassTime::class, 'class_time_id');
    }

    // Relasi ke ClassLocation
    public function classLocation()
    {
        return $this->belongsTo(ClassLocation::class, 'class_location_id');
    }

    // Relasi ke ClassCategory
    public function category()
    {
        return $this->belongsTo(ClassCategory::class, 'class_category_id');
    }

    // Relasi ke ClassType
    public function type()
    {
        return $this->belongsTo(ClassType::class, 'class_type_id');
    }

    // Relasi ke Instructor
    public function instructor()
    {
        return $this->belongsTo(Instructor::class);
    }

    // Relasi ke Student melalui tabel enrolls
    public function students()
    {
        return $this->belongsToMany(Student::class, 'enrolls', 'class_id', 'student_id')
            ->withTimestamps()
            ->withPivot('date', 'status');
    }

    // Relasi ke Enroll
    public function enrolls()
    {
        return $this->hasMany(Enroll::class, 'class_id');
    }

    // Relasi ke ClassStudent
    public function classStudents()
    {
        return $this->hasMany(ClassStudent::class, 'class_id');
    }

    // Relasi ke Schedule
    public function schedules()
    {
        return $this->hasMany(Schedule::class, 'class_id');
    }

    // NEW: Message Class relationships
    public function messageClasses()
    {
        return $this->hasMany(MessageClass::class, 'class_id');
    }

    public function announcements()
    {
        return $this->messageClasses()->announcements()->orderBy('created_at', 'desc');
    }

    public function pinnedMessages()
    {
        return $this->messageClasses()->pinned()->orderBy('created_at', 'desc');
    }

    // Message Activities relationship
    public function messageActivities()
    {
        return $this->hasMany(MessageActivity::class, 'class_id');
    }

    public function activeMessageActivities()
    {
        return $this->messageActivities()->active()->orderBy('activity_date', 'desc');
    }

    public function pinnedActivities()
    {
        return $this->messageActivities()->pinned()->orderBy('activity_date', 'desc');
    }

    /**     
     * Scopes
     */
    public function scopeApproved($query)
    {
        return $query->where('is_approved', true);
    }

    public function scopePending($query)
    {
        return $query->where('is_approved', false);
    }

    public function scopeByInstructor($query, $instructorId)
    {
        return $query->where('instructor_id', $instructorId);
    }

    public function scopeBySeason($query, $seasonId)
    {
        return $query->where('season_id', $seasonId);
    }

    public function scopeByCategory($query, $categoryId)
    {
        return $query->where('class_category_id', $categoryId);
    }

    public function scopeByType($query, $typeId)
    {
        return $query->where('class_type_id', $typeId);
    }
}
