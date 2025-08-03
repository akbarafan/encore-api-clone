<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

class File extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'original_name',
        'file_name',
        'file_path',
        'mime_type',
        'file_size',
        'file_extension',
        'file_category',
        'uploaded_by'
    ];

    protected $casts = [
        'file_size' => 'integer',
    ];

    protected $appends = [
        'file_size_human',
        'file_url'
    ];

    // Relationships
    public function uploader()
    {
        return $this->belongsTo(Instructor::class, 'uploaded_by');
    }

    public function schedules()
    {
        return $this->belongsToMany(Schedule::class, 'schedule_files')
            ->withPivot('title', 'description', 'order', 'is_required', 'available_from', 'available_until')
            ->withTimestamps()
            ->orderBy('pivot_order');
    }

    public function downloads()
    {
        return $this->hasMany(FileDownload::class);
    }

    public function materials()
    {
        return $this->hasMany(Material::class);
    }

    // Accessors
    public function getFileSizeHumanAttribute()
    {
        $bytes = $this->file_size;
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];

        for ($i = 0; $bytes > 1024; $i++) {
            $bytes /= 1024;
        }

        return round($bytes, 2) . ' ' . $units[$i];
    }

    public function getFileUrlAttribute()
    {
        return Storage::url($this->file_path);
    }

    // Scopes
    public function scopeByCategory($query, $category)
    {
        return $query->where('file_category', $category);
    }

    public function scopeByUploader($query, $instructorId)
    {
        return $query->where('uploaded_by', $instructorId);
    }

    // Methods
    public function deleteFile()
    {
        if (Storage::exists($this->file_path)) {
            Storage::delete($this->file_path);
        }
    }

    protected static function boot()
    {
        parent::boot();

        static::deleting(function ($file) {
            $file->deleteFile();
        });
    }
}
