<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MessageActivity extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'message_activities';

    protected $fillable = [
        'instructor_id',
        'class_id',
        'title',
        'message',
        'attachments',
        'activity_date',
        'is_pinned',
        'is_active'
    ];

    protected $casts = [
        'attachments' => 'array',
        'activity_date' => 'date',
        'is_pinned' => 'boolean',
        'is_active' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime'
    ];

    /**
     * Relasi ke instructor
     */
    public function instructor(): BelongsTo
    {
        return $this->belongsTo(Instructor::class);
    }

    /**
     * Relasi ke class
     */
    public function class(): BelongsTo
    {
        return $this->belongsTo(Classes::class, 'class_id');
    }

    /**
     * Scope untuk filter berdasarkan instructor
     */
    public function scopeByInstructor($query, $instructorId)
    {
        return $query->where('instructor_id', $instructorId);
    }

    /**
     * Scope untuk filter berdasarkan class
     */
    public function scopeByClass($query, $classId)
    {
        return $query->where('class_id', $classId);
    }

    /**
     * Scope untuk hanya yang aktif
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope untuk yang di-pin
     */
    public function scopePinned($query)
    {
        return $query->where('is_pinned', true);
    }

    /**
     * Scope untuk berdasarkan tanggal
     */
    public function scopeByDate($query, $date)
    {
        return $query->whereDate('activity_date', $date);
    }

    /**
     * Scope untuk rentang tanggal
     */
    public function scopeBetweenDates($query, $startDate, $endDate)
    {
        return $query->whereBetween('activity_date', [$startDate, $endDate]);
    }
}
