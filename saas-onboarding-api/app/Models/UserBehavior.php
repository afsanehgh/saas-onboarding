<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserBehavior extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'action',
        'context',
        'page_url',
        'feature',
        'duration_seconds',
        'device_type',
        'browser',
    ];

    protected $casts = [
        'context' => 'array',
    ];

    /**
     * Relationship with user
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scope for recent behaviors
     */
    public function scopeRecent($query, int $days = 7)
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }

    /**
     * Scope for specific action
     */
    public function scopeByAction($query, string $action)
    {
        return $query->where('action', $action);
    }

    /**
     * Scope for specific feature
     */
    public function scopeByFeature($query, string $feature)
    {
        return $query->where('feature', $feature);
    }
}
