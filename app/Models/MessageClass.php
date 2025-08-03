<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class MessageClass extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'class_id',
        'student_id',
        'sender_type',
        'message',
        'reply_to',
        'attachments',
        'is_read',
        'read_at',
        'is_pinned',
        'is_announcement',
    ];

    protected $casts = [
        'attachments' => 'array',
        'is_read' => 'boolean',
        'read_at' => 'datetime',
        'is_pinned' => 'boolean',
        'is_announcement' => 'boolean',
    ];

    /**
     * Relationship dengan User (bisa student atau instructor)
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Relationship dengan Class
     */
    public function class()
    {
        return $this->belongsTo(Classes::class, 'class_id');
    }

    /**
     * Relationship untuk reply message
     */
    public function replyTo()
    {
        return $this->belongsTo(MessageClass::class, 'reply_to');
    }

    /**
     * Relationship untuk replies dari message ini
     */
    public function replies()
    {
        return $this->hasMany(MessageClass::class, 'reply_to');
    }

    /**
     * Relationship dengan Student (untuk family yang kirim atas nama student)
     */
    public function student()
    {
        return $this->belongsTo(Student::class, 'student_id');
    }

    /**
     * Get sender info (instructor or student)
     */
    public function getSenderAttribute()
    {
        if ($this->sender_type === 'instructor') {
            return $this->user->instructor ?? null;
        } else {
            return $this->user->student ?? null;
        }
    }

    /**
     * Scope untuk filter berdasarkan class
     */
    public function scopeByClass($query, $classId)
    {
        return $query->where('class_id', $classId);
    }

    /**
     * Scope untuk filter berdasarkan user
     */
    public function scopeByUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope untuk filter berdasarkan tipe sender
     */
    public function scopeBySenderType($query, $type)
    {
        return $query->where('sender_type', $type);
    }

    /**
     * Scope untuk message yang belum dibaca
     */
    public function scopeUnread($query)
    {
        return $query->where('is_read', false);
    }

    /**
     * Scope untuk message yang dipinned
     */
    public function scopePinned($query)
    {
        return $query->where('is_pinned', true);
    }

    /**
     * Scope untuk announcement
     */
    public function scopeAnnouncements($query)
    {
        return $query->where('is_announcement', true);
    }

    /**
     * Scope untuk message yang bukan reply
     */
    public function scopeMainMessages($query)
    {
        return $query->whereNull('reply_to');
    }

    /**
     * Mark message sebagai dibaca
     */
    public function markAsRead()
    {
        $this->update([
            'is_read' => true,
            'read_at' => now(),
        ]);
    }

    /**
     * Toggle pin status
     */
    public function togglePin()
    {
        $this->update([
            'is_pinned' => !$this->is_pinned,
        ]);
    }

    /**
     * Get sender name
     */
    public function getSenderNameAttribute()
    {
        if ($this->sender_type === 'instructor') {
            $instructor = $this->user->instructor ?? null;
            return $instructor ? $instructor->name : $this->user->name;
        }

        // Untuk student - prioritas: student_id (dari family) > user->student > user->name
        if ($this->student_id && $this->student) {
            // Family sending as student - use student name, not family name
            return $this->student->first_name . ' ' . $this->student->last_name;
        }
        
        // Direct student login
        $student = $this->user->student ?? null;
        return $student ? $student->first_name . ' ' . $student->last_name : $this->user->name;
    }

    /**
     * Get total replies count
     */
    public function getRepliesCountAttribute()
    {
        return $this->replies()->count();
    }

    /**
     * Check if message has attachments
     */
    public function hasAttachments()
    {
        return !empty($this->attachments);
    }

    /**
     * Get attachment count
     */
    public function getAttachmentCountAttribute()
    {
        return $this->attachments ? count($this->attachments) : 0;
    }

    /**
     * Scope untuk conversation thread
     */
    public function scopeConversationThread($query, $messageId)
    {
        return $query->where(function ($q) use ($messageId) {
            $q->where('id', $messageId)
                ->orWhere('reply_to', $messageId);
        })->orderBy('created_at');
    }
}
