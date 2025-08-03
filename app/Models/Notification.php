<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Notification extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'title',
        'message',
        'read_at',
        'action_type',
        'action_data',
    ];

    protected $casts = [
        'action_data' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
