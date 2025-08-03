<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ClassType extends Model
{
    use SoftDeletes;

    protected $fillable = ['name'];

    public function classes()
    {
        return $this->hasMany(Classes::class, 'class_type_id');
    }
}
