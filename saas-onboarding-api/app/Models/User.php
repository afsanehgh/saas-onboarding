<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'company',
        'role',
        'timezone',
        'preferences',
        'onboarding_completed',
        'onboarding_completed_at',
        'profile_completed',
        'projects_count',
    ];

    /**
     * The attributes that should be hidden for serialisation.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'preferences' => 'array',
            'onboarding_completed' => 'boolean',
            'profile_completed' => 'boolean',
            'onboarding_completed_at' => 'datetime',
            'projects_count' => 'integer',
        ];
    }

    /**
     * The user's onboarding journey.
     */
    public function onboardingProgress(): HasOne
    {
        return $this->hasOne(OnboardingProgress::class);
    }

    /**
     * Tracked behaviour events for this user.
     */
    public function behaviors(): HasMany
    {
        return $this->hasMany(UserBehavior::class);
    }

    /**
     * Generated recommendation sets for this user.
     */
    public function recommendations(): HasMany
    {
        return $this->hasMany(Recommendation::class);
    }

    /**
     * Check if user has completed onboarding.
     */
    public function hasCompletedOnboarding(): bool
    {
        return (bool) $this->onboarding_completed;
    }

    /**
     * The user's current segment, taken from their latest recommendations.
     */
    public function getSegmentAttribute(): string
    {
        $recommendation = $this->recommendations()->latest('generated_at')->first();

        return $recommendation ? $recommendation->segment : 'new_user';
    }
}
