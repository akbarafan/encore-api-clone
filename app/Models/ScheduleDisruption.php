<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ScheduleDisruption extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'schedule_id',
        'instructor_log_hour_id',
        'reason',
        'status',
        'notes',
        'response_deadline',
        'final_decision',
        'final_new_date',
        'final_new_start_time',
        'final_new_end_time',
        'final_replacement_instructor_id',
        'total_students',
        'responses_count',
        'vote_distribution',
    ];

    protected $casts = [
        'final_new_date' => 'date',
        'final_new_start_time' => 'datetime',
        'final_new_end_time' => 'datetime',
        'response_deadline' => 'datetime',
        'vote_distribution' => 'array',
    ];

    public function schedule(): BelongsTo
    {
        return $this->belongsTo(Schedule::class);
    }

    public function instructorLogHour(): BelongsTo
    {
        return $this->belongsTo(InstructorLogHour::class);
    }

    public function finalReplacementInstructor(): BelongsTo
    {
        return $this->belongsTo(Instructor::class, 'final_replacement_instructor_id');
    }

    public function studentResponses(): HasMany
    {
        return $this->hasMany(StudentResponse::class);
    }

    public function getChoiceDistribution(): array
    {
        $total = $this->studentResponses()->count();
        if ($total === 0) return [];
        
        $distribution = $this->studentResponses()
            ->select('choice', \DB::raw('count(*) as count'))
            ->groupBy('choice')
            ->pluck('count', 'choice')
            ->toArray();
            
        // Calculate percentages
        foreach ($distribution as $choice => $count) {
            $distribution[$choice] = [
                'count' => $count,
                'percentage' => round(($count / $total) * 100, 1)
            ];
        }
        
        return $distribution;
    }

    public function getMajorityChoice(): ?string
    {
        $distribution = $this->getChoiceDistribution();
        if (empty($distribution)) return null;
        
        $maxCount = max(array_column($distribution, 'count'));
        foreach ($distribution as $choice => $data) {
            if ($data['count'] === $maxCount) {
                return $choice;
            }
        }
        
        return null;
    }

    public function hasAllStudentsResponded(): bool
    {
        $totalStudents = $this->schedule->classStudents()->count();
        $responses = $this->studentResponses()->count();
        
        return $totalStudents === $responses;
    }
}
