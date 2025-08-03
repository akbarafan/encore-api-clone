<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Season extends Model
{
    use SoftDeletes;

    protected $fillable = ['name', 'start_date', 'end_date'];

    public function classes()
    {
        return $this->hasMany(Classes::class);
    }
}
