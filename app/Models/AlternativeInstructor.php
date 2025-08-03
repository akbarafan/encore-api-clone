<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AlternativeInstructor extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'class_category_id',
        'instructor_id',
        'availability_start_date',
        'availability_end_date',
        'hourly_rate',
        'is_active',
        'notes',
    ];

    protected $casts = [
        'availability_start_date' => 'date',
        'availability_end_date' => 'date',
        'hourly_rate' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    public function classCategory(): BelongsTo
    {
        return $this->belongsTo(ClassCategory::class);
    }

    public function instructor(): BelongsTo
    {
        return $this->belongsTo(Instructor::class);
    }

    public function isAvailableOn($date): bool
    {
        return $this->is_active && 
               $this->availability_start_date <= $date && 
               $this->availability_end_date >= $date;
    }

    public static function getAvailableForClass($classId, $date)
    {
        $class = ClassModel::find($classId);
        
        return self::where('class_category_id', $class->class_category_id)
                   ->where('is_active', true)
                   ->where('availability_start_date', '<=', $date)
                   ->where('availability_end_date', '>=', $date)
                   ->with('instructor')
                   ->get();
    }
}
