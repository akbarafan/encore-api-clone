<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Instructor extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'user_id',
        'name',
        'availability',
        'payrate',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function classes()
    {
        return $this->hasMany(Classes::class);
    }

    public function instructorLogHours()
    {
        return $this->hasMany(InstructorLogHour::class);
    }

    // NEW: Materials relationships
    public function materials()
    {
        return $this->hasMany(Material::class);
    }

    public function activeMaterials()
    {
        return $this->materials()->active();
    }

    // Files uploaded by instructor
    public function files()
    {
        return $this->hasMany(File::class, 'uploaded_by');
    }

    // Message Activities relationships
    public function messageActivities()
    {
        return $this->hasMany(MessageActivity::class);
    }

    public function activeMessageActivities()
    {
        return $this->messageActivities()->active()->orderBy('activity_date', 'desc');
    }
}
