<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Family extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'guardians_name',
        'last_name',
        'address',
        'phone',
        'email',
        'username',
        'city',
        'state',
        'zip_code',
        'emergency_contact_name',
        'emergency_contact_phone',
        'is_term_&_condition',
        'contact_type_id',
        'user_id',  // foreign key ke users
    ];

    public function students()
    {
        return $this->hasMany(Student::class);
    }

    // Relasi ke User
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Relasi ke ContactType
    public function contactType()
    {
        return $this->belongsTo(ContactType::class);
    }
}
