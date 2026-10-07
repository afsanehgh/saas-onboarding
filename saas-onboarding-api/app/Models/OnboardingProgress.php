<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OnboardingProgress extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'current_step',
        'total_steps',
        'completed_steps',
        'skipped_steps',
        'status',
        'started_at',
        'completed_at',
    ];

    protected $casts = [
        'completed_steps' => 'array',
        'skipped_steps' => 'array',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    /**
     * Relationship with user
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scope for in-progress onboarding
     */
    public function scopeInProgress($query)
    {
        return $query->where('status', 'in_progress');
    }

    /**
     * Scope for completed onboarding
     */
    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    /**
     * Get completion percentage
     */
    public function getCompletionPercentageAttribute(): float
    {
        $completedSteps = count($this->completed_steps ?? []);

        return ($completedSteps / $this->total_steps) * 100;
    }
}
