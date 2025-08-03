<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use PhpParser\Builder\Class_;

class ClassTime extends Model
{

    protected $fillable = [
        'name',
    ];

    public function class(){
        return $this->hasMany(Classes::class);
    }
}
