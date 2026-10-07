<?php

namespace App\Services;

use App\Exceptions\OnboardingException;
use App\Models\OnboardingProgress;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class OnboardingService
{
    /**
     * The onboarding journey. In a real product this would live in the database
     * or a CMS so non-engineers could edit it.
     */
    protected const STEPS = [
        1 => [
            'name' => 'welcome',
            'title' => 'Welcome to the platform',
            'description' => "Let's get you set up quickly",
            'type' => 'welcome',
        ],
        2 => [
            'name' => 'profile_setup',
            'title' => 'Set up your profile',
            'description' => 'Tell us a bit about yourself',
            'type' => 'form',
            'fields' => ['name', 'company', 'role'],
        ],
        3 => [
            'name' => 'goal_selection',
            'title' => "What's your primary goal?",
            'description' => 'Select your main objective',
            'type' => 'selection',
            'options' => ['increase_productivity', 'improve_collaboration', 'automate_workflows'],
        ],
        4 => [
            'name' => 'product_tour',
            'title' => 'Quick product tour',
            'description' => 'Explore key features',
            'type' => 'tour',
        ],
        5 => [
            'name' => 'first_action',
            'title' => 'Complete your first action',
            'description' => 'Get hands-on experience',
            'type' => 'action',
        ],
    ];

    public function __construct(protected EventPublisher $eventPublisher) {}

    public function totalSteps(): int
    {
        return count(self::STEPS);
    }

    /**
     * Start (or restart) onboarding for a user.
     */
    public function startOnboarding(User $user): OnboardingProgress
    {
        $progress = OnboardingProgress::updateOrCreate(
            ['user_id' => $user->id],
            [
                'current_step' => 1,
                'total_steps' => $this->totalSteps(),
                'completed_steps' => [],
                'skipped_steps' => [],
                'status' => 'in_progress',
                'started_at' => now(),
                'completed_at' => null,
            ]
        );

        $user->forceFill(['onboarding_completed' => false, 'onboarding_completed_at' => null])->save();
        $user->setRelation('onboardingProgress', $progress);

        $this->eventPublisher->publishOnboardingEvent('onboarding.started', [
            'progress_id' => $progress->id,
            'total_steps' => $progress->total_steps,
        ], $user->id);

        return $progress;
    }

    /**
     * Mark a step as complete and advance the journey.
     */
    public function completeStep(User $user, int $stepNumber, array $stepData = []): OnboardingProgress
    {
        $progress = $this->activeProgress($user, $stepNumber);

        DB::transaction(function () use ($progress, $stepNumber, $stepData, $user) {
            $completedSteps = $progress->completed_steps ?? [];
            $completedSteps[$stepNumber] = [
                'completed_at' => now()->toIso8601String(),
                'data' => $stepData,
            ];

            $progress->update([
                'completed_steps' => $completedSteps,
                'current_step' => min($stepNumber + 1, $progress->total_steps),
            ]);

            if ($stepNumber === 2) {
                $this->applyProfileData($user, $stepData);
            }

            $this->eventPublisher->publishOnboardingEvent('onboarding.step_completed', [
                'step_number' => $stepNumber,
                'step_name' => self::STEPS[$stepNumber]['name'],
            ], $user->id);

            if ($stepNumber >= $progress->total_steps) {
                $this->completeOnboarding($progress, $user);
            }
        });

        return $progress->fresh();
    }

    /**
     * Skip a step and advance the journey.
     */
    public function skipStep(User $user, int $stepNumber): OnboardingProgress
    {
        if (! config('services.onboarding.skip_allowed', true)) {
            throw new OnboardingException('Skipping onboarding steps is not allowed.', 403);
        }

        $progress = $this->activeProgress($user, $stepNumber);

        DB::transaction(function () use ($progress, $stepNumber, $user) {
            $skippedSteps = $progress->skipped_steps ?? [];
            if (! in_array($stepNumber, $skippedSteps, true)) {
                $skippedSteps[] = $stepNumber;
            }

            $progress->update([
                'skipped_steps' => $skippedSteps,
                'current_step' => min($stepNumber + 1, $progress->total_steps),
            ]);

            $this->eventPublisher->publishOnboardingEvent('onboarding.step_skipped', [
                'step_number' => $stepNumber,
                'step_name' => self::STEPS[$stepNumber]['name'],
            ], $user->id);

            if ($stepNumber >= $progress->total_steps) {
                $this->completeOnboarding($progress, $user);
            }
        });

        return $progress->fresh();
    }

    public function getProgress(User $user): ?OnboardingProgress
    {
        return $user->onboardingProgress()->first();
    }

    /**
     * Percentage of steps that have been completed or skipped.
     */
    public function getCompletionPercentage(?OnboardingProgress $progress): float
    {
        if (! $progress || $progress->total_steps === 0) {
            return 0.0;
        }

        if ($progress->status === 'completed') {
            return 100.0;
        }

        $done = count($progress->completed_steps ?? []) + count($progress->skipped_steps ?? []);

        return round(min($done, $progress->total_steps) / $progress->total_steps * 100, 1);
    }

    /**
     * The step the user should see next, or null if onboarding is not in progress.
     */
    public function getNextStep(?OnboardingProgress $progress): ?array
    {
        if (! $progress || $progress->status !== 'in_progress') {
            return null;
        }

        return $this->getStepConfig($progress->current_step);
    }

    public function getStepConfig(int $stepNumber): ?array
    {
        if (! isset(self::STEPS[$stepNumber])) {
            return null;
        }

        return ['step_number' => $stepNumber] + self::STEPS[$stepNumber];
    }

    /**
     * Load the user's in-progress journey and check the step is actionable.
     */
    protected function activeProgress(User $user, int $stepNumber): OnboardingProgress
    {
        $progress = $this->getProgress($user);

        if (! $progress || $progress->status !== 'in_progress') {
            throw new OnboardingException('No active onboarding found. Start onboarding first.', 409);
        }

        if (! isset(self::STEPS[$stepNumber])) {
            throw new OnboardingException("Step {$stepNumber} does not exist.", 404);
        }

        if ($stepNumber > $progress->current_step) {
            throw new OnboardingException("Step {$stepNumber} is not yet available.", 409);
        }

        return $progress;
    }

    protected function completeOnboarding(OnboardingProgress $progress, User $user): void
    {
        $progress->update([
            'status' => 'completed',
            'completed_at' => now(),
        ]);

        $user->forceFill([
            'onboarding_completed' => true,
            'onboarding_completed_at' => now(),
        ])->save();

        $this->eventPublisher->publishOnboardingEvent('onboarding.completed', [
            'progress_id' => $progress->id,
            'duration_seconds' => (int) $progress->started_at->diffInSeconds($progress->completed_at, true),
            'skipped_steps' => $progress->skipped_steps ?? [],
        ], $user->id);
    }

    /**
     * Copy profile answers from the profile step onto the user record.
     */
    protected function applyProfileData(User $user, array $stepData): void
    {
        $profile = array_filter(
            array_intersect_key($stepData, array_flip(['name', 'company', 'role'])),
            fn ($value) => is_string($value) && trim($value) !== ''
        );

        if ($profile !== []) {
            $user->fill($profile);
            $user->profile_completed = true;
            $user->save();
        }
    }
}
