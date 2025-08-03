<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StudentResponse extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'schedule_disruption_id',
        'student_id',
        'choice',
        'preferred_date',
        'preferred_start_time',
        'preferred_end_time',
        'preferred_replacement_instructor_id',
        'notes',
        'responded_at',
    ];

    protected $casts = [
        'preferred_date' => 'date',
        'preferred_start_time' => 'datetime',
        'preferred_end_time' => 'datetime',
        'responded_at' => 'datetime',
    ];

    public function scheduleDisruption(): BelongsTo
    {
        return $this->belongsTo(ScheduleDisruption::class);
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function preferredReplacementInstructor(): BelongsTo
    {
        return $this->belongsTo(Instructor::class, 'preferred_replacement_instructor_id');
    }

    protected static function boot()
    {
        parent::boot();
        
        static::creating(function ($model) {
            if (!$model->responded_at) {
                $model->responded_at = now();
            }
        });
    }
}
