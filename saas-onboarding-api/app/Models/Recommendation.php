<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Recommendation extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'segment',
        'recommendations',
        'generated_at',
    ];

    protected $casts = [
        'recommendations' => 'array',
        'generated_at' => 'datetime',
    ];

    /**
     * Relationship with user
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scope for recent recommendations
     */
    public function scopeRecent($query, int $hours = 24)
    {
        return $query->where('generated_at', '>=', now()->subHours($hours));
    }

    /**
     * Scope for specific segment
     */
    public function scopeBySegment($query, string $segment)
    {
        return $query->where('segment', $segment);
    }
}
