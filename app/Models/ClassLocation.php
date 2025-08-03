<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ClassLocation extends Model
{
    protected $fillable = [
        'city',
        'address',
    ];

    /**
     * Relations
     */
    public function classes()
    {
        return $this->hasMany(Classes::class, 'class_location_id');
    }
}
