<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Timesheet extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'instructor_id',
        'month',
        'total_hours',
        'status',
        'approved_by',
    ];

    public function instructor()
    {
        return $this->belongsTo(Instructor::class);
    }
}
