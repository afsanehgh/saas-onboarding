<?php

namespace App\Services;

use App\Models\Recommendation;
use App\Models\User;
use App\Models\UserBehavior;
use Illuminate\Support\Collection;

/**
 * Rule-based personalisation: segments users from their recent behaviour and
 * builds feature, content, action and timing recommendations for each segment.
 *
 * The rules are deliberately simple and transparent. The interface (behaviour in,
 * recommendations out, events published) is what a machine-learning model would
 * slot in behind later.
 */
class PersonalizationService
{
    protected const FEATURES = [
        'dashboard' => ['name' => 'Dashboard', 'priority' => 1],
        'analytics' => ['name' => 'Analytics', 'priority' => 2],
        'integrations' => ['name' => 'Integrations', 'priority' => 3],
        'automation' => ['name' => 'Automation', 'priority' => 4],
        'collaboration' => ['name' => 'Collaboration', 'priority' => 5],
        'reports' => ['name' => 'Reports', 'priority' => 6],
    ];

    protected const CONTENT = [
        'new_user' => [
            ['id' => 'getting-started', 'title' => 'Getting started guide', 'type' => 'tutorial'],
            ['id' => 'quick-tips', 'title' => 'Quick tips for success', 'type' => 'tip'],
            ['id' => 'platform-overview', 'title' => 'Video: platform overview', 'type' => 'video'],
        ],
        'power_user' => [
            ['id' => 'advanced-features', 'title' => 'Advanced features', 'type' => 'tutorial'],
            ['id' => 'integration-guide', 'title' => 'Integration guide', 'type' => 'guide'],
            ['id' => 'best-practices', 'title' => 'Best practices', 'type' => 'article'],
        ],
        'casual_user' => [
            ['id' => 'feature-highlights', 'title' => 'Feature highlights', 'type' => 'article'],
            ['id' => 'time-saving-tips', 'title' => 'Time-saving tips', 'type' => 'tip'],
            ['id' => 'success-stories', 'title' => 'Success stories', 'type' => 'case_study'],
        ],
        'at_risk_user' => [
            ['id' => 're-engagement', 'title' => "What's new since your last visit", 'type' => 'article'],
            ['id' => 'value-recap', 'title' => 'Getting more value from the platform', 'type' => 'article'],
            ['id' => 'support', 'title' => 'Talk to customer support', 'type' => 'support'],
        ],
        'standard_user' => [
            ['id' => 'feature-updates', 'title' => 'Feature updates', 'type' => 'update'],
            ['id' => 'new-features', 'title' => 'New features', 'type' => 'announcement'],
            ['id' => 'community-tips', 'title' => 'Community tips', 'type' => 'article'],
        ],
    ];

    public function __construct(protected EventPublisher $eventPublisher) {}

    /**
     * Record a behaviour event for the user.
     *
     * @param  array  $attributes  action plus optional context, page_url, feature,
     *                             duration_seconds, device_type and browser
     */
    public function trackBehavior(User $user, string $action, array $attributes = []): UserBehavior
    {
        $context = $attributes['context'] ?? [];

        $behavior = UserBehavior::create([
            'user_id' => $user->id,
            'action' => $action,
            'context' => $context,
            'page_url' => $attributes['page_url'] ?? $context['page_url'] ?? null,
            'feature' => $attributes['feature'] ?? $context['feature'] ?? null,
            'duration_seconds' => $attributes['duration_seconds'] ?? $context['duration_seconds'] ?? null,
            'device_type' => $attributes['device_type'] ?? null,
            'browser' => $attributes['browser'] ?? null,
        ]);

        $this->eventPublisher->publishBehaviorEvent('behavior.tracked', [
            'behavior_id' => $behavior->id,
            'action' => $behavior->action,
            'feature' => $behavior->feature,
        ], $user->id);

        return $behavior;
    }

    /**
     * Return stored recommendations, regenerating them if missing or older than
     * the configured cache window.
     */
    public function getRecommendations(User $user): Recommendation
    {
        $recommendation = Recommendation::where('user_id', $user->id)->first();
        $maxAgeHours = (int) config('services.personalization.cache_hours', 6);

        if (! $recommendation || $recommendation->generated_at->lt(now()->subHours($maxAgeHours))) {
            $this->generateRecommendations($user, true);
            $recommendation = Recommendation::where('user_id', $user->id)->first();
        }

        return $recommendation;
    }

    /**
     * Build and store recommendations for the user.
     */
    public function generateRecommendations(User $user, bool $force = false): array
    {
        if (! $force) {
            $existing = Recommendation::where('user_id', $user->id)->first();
            if ($existing) {
                return $existing->recommendations;
            }
        }

        $behaviors = $user->behaviors()->latest()->limit(100)->get();
        $segment = $this->segmentUser($user, $behaviors);

        $recommendations = [
            'features' => $this->recommendFeatures($behaviors),
            'content' => self::CONTENT[$segment] ?? self::CONTENT['standard_user'],
            'actions' => $this->recommendActions($user, $behaviors),
            'timing' => $this->recommendTiming($user, $behaviors),
        ];

        Recommendation::updateOrCreate(
            ['user_id' => $user->id],
            [
                'segment' => $segment,
                'recommendations' => $recommendations,
                'generated_at' => now(),
            ]
        );

        $this->eventPublisher->publishPersonalizationEvent('recommendation.generated', [
            'segment' => $segment,
            'feature_keys' => array_column($recommendations['features'], 'key'),
        ], $user->id);

        return $recommendations;
    }

    /**
     * Record how the user responded to a recommendation.
     */
    public function trackRecommendationInteraction(User $user, string $recommendationType, string $recommendationId, string $action): void
    {
        $this->eventPublisher->publishPersonalizationEvent('recommendation.interaction', [
            'recommendation_type' => $recommendationType,
            'recommendation_id' => $recommendationId,
            'action' => $action,
        ], $user->id);
    }

    /**
     * Place the user in an engagement segment.
     *
     * @param  Collection<int, UserBehavior>  $behaviors  most recent first
     */
    public function segmentUser(User $user, Collection $behaviors): string
    {
        $total = $behaviors->count();
        $lastSevenDays = $behaviors->filter(fn (UserBehavior $b) => $b->created_at->gt(now()->subDays(7)))->count();
        $accountAgeDays = $user->created_at ? $user->created_at->diffInDays(now(), true) : 0;

        if ($total >= 50 && $lastSevenDays >= 20) {
            return 'power_user';
        }

        if ($accountAgeDays < 7) {
            return 'new_user';
        }

        if ($total >= 10 && $lastSevenDays < 3) {
            return 'at_risk_user';
        }

        if ($total >= 20) {
            return 'casual_user';
        }

        return 'standard_user';
    }

    /**
     * Suggest up to three features the user has not used yet, in priority order.
     */
    protected function recommendFeatures(Collection $behaviors): array
    {
        $usedFeatures = $behaviors->pluck('feature')->filter()->unique()->all();

        return collect(self::FEATURES)
            ->reject(fn ($feature, $key) => in_array($key, $usedFeatures, true))
            ->sortBy('priority')
            ->take(3)
            ->map(fn ($feature, $key) => [
                'key' => $key,
                'name' => $feature['name'],
                'priority' => $feature['priority'],
                'reason' => 'Not yet explored',
            ])
            ->values()
            ->all();
    }

    protected function recommendActions(User $user, Collection $behaviors): array
    {
        $actions = [];

        if (! $user->profile_completed) {
            $actions[] = ['action' => 'complete_profile', 'title' => 'Complete your profile', 'priority' => 1];
        }

        if (($user->projects_count ?? 0) === 0) {
            $actions[] = ['action' => 'create_project', 'title' => 'Create your first project', 'priority' => 2];
        }

        if (! $behaviors->contains('feature', 'analytics')) {
            $actions[] = ['action' => 'view_analytics', 'title' => 'Check your analytics', 'priority' => 3];
        }

        return $actions;
    }

    /**
     * Best hour (in UTC) to send notifications, from when the user is most active.
     */
    protected function recommendTiming(User $user, Collection $behaviors): array
    {
        $peakHour = $behaviors
            ->countBy(fn (UserBehavior $b) => $b->created_at->hour)
            ->sortDesc()
            ->keys()
            ->first();

        return [
            'optimal_hour_utc' => $peakHour ?? 9,
            'timezone' => $user->timezone ?? 'UTC',
            'frequency' => 'daily',
        ];
    }
}
