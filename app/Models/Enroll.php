<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;
use Illuminate\Database\Eloquent\SoftDeletes;

class Enroll extends Pivot
{
    use SoftDeletes;

    protected $table = 'enrolls';

    protected $fillable = [
        'student_id',
        'class_id',
        'date',
        'status'
    ];

    protected $casts = [
        'date' => 'date'
    ];

    /**
     * Relations
     */
    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    public function class()
    {
        return $this->belongsTo(Classes::class, 'class_id');
    }

    // Scope untuk enrollment aktif
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    // Scope untuk enrollment berdasarkan instructor
    public function scopeByInstructor($query, $instructorId)
    {
        return $query->whereHas('class', function ($q) use ($instructorId) {
            $q->where('instructor_id', $instructorId);
        });
    }
}
