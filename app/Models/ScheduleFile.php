<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;
use Illuminate\Database\Eloquent\SoftDeletes;

class ScheduleFile extends Pivot
{
    use SoftDeletes;

    protected $table = 'schedule_files';

    protected $fillable = [
        'schedule_id',
        'file_id',
        'title',
        'description',
        'order',
        'is_required',
        'available_from',
        'available_until'
    ];

    protected $casts = [
        'is_required' => 'boolean',
        'available_from' => 'datetime',
        'available_until' => 'datetime',
        'order' => 'integer'
    ];

    public function schedule()
    {
        return $this->belongsTo(Schedule::class);
    }

    public function file()
    {
        return $this->belongsTo(File::class);
    }

    // Check if file is available for download
    public function isAvailable()
    {
        $now = now();

        if ($this->available_from && $now->lt($this->available_from)) {
            return false;
        }

        if ($this->available_until && $now->gt($this->available_until)) {
            return false;
        }

        return true;
    }
}
