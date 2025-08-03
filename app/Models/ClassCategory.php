<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ClassCategory extends Model
{
    use SoftDeletes;

    protected $fillable = ['name'];

    public function classes()
    {
        return $this->hasMany(Classes::class, 'class_category_id');
    }
}
