<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\OnboardingProgress;
use App\Services\OnboardingService;
use App\Services\PersonalizationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OnboardingController extends Controller
{
    public function __construct(
        protected OnboardingService $onboardingService,
        protected PersonalizationService $personalizationService,
    ) {}

    /**
     * Start (or restart) onboarding for the authenticated user.
     */
    public function start(Request $request): JsonResponse
    {
        $progress = $this->onboardingService->startOnboarding($request->user());

        return response()->json([
            'success' => true,
            'data' => $this->present($progress),
        ], 201);
    }

    /**
     * Get onboarding progress.
     */
    public function progress(Request $request): JsonResponse
    {
        $progress = $this->onboardingService->getProgress($request->user());

        if (! $progress) {
            return response()->json([
                'success' => true,
                'data' => [
                    'status' => 'not_started',
                    'total_steps' => $this->onboardingService->totalSteps(),
                    'completion_percentage' => 0,
                ],
            ]);
        }

        return response()->json([
            'success' => true,
            'data' => $this->present($progress),
        ]);
    }

    /**
     * Get the configuration for one step.
     */
    public function getStep(Request $request, int $stepNumber): JsonResponse
    {
        $step = $this->onboardingService->getStepConfig($stepNumber);

        if (! $step) {
            return response()->json(['success' => false, 'message' => 'Step not found.'], 404);
        }

        $progress = $this->onboardingService->getProgress($request->user());

        if ($progress && $progress->status === 'in_progress' && $stepNumber > $progress->current_step) {
            return response()->json(['success' => false, 'message' => 'Step not yet accessible.'], 403);
        }

        return response()->json(['success' => true, 'data' => $step]);
    }

    /**
     * Complete an onboarding step and refresh the user's recommendations.
     */
    public function completeStep(Request $request, int $stepNumber): JsonResponse
    {
        $validated = $request->validate([
            'step_data' => ['sometimes', 'array'],
        ]);

        $user = $request->user();
        $progress = $this->onboardingService->completeStep($user, $stepNumber, $validated['step_data'] ?? []);

        return response()->json([
            'success' => true,
            'data' => $this->present($progress) + [
                'recommendations' => $this->personalizationService->generateRecommendations($user->fresh(), true),
            ],
        ]);
    }

    /**
     * Skip an onboarding step.
     */
    public function skipStep(Request $request, int $stepNumber): JsonResponse
    {
        $progress = $this->onboardingService->skipStep($request->user(), $stepNumber);

        return response()->json([
            'success' => true,
            'data' => $this->present($progress),
        ]);
    }

    protected function present(OnboardingProgress $progress): array
    {
        return [
            'progress_id' => $progress->id,
            'current_step' => $progress->current_step,
            'total_steps' => $progress->total_steps,
            'completed_steps' => array_map('intval', array_keys($progress->completed_steps ?? [])),
            'skipped_steps' => $progress->skipped_steps ?? [],
            'status' => $progress->status,
            'completion_percentage' => $this->onboardingService->getCompletionPercentage($progress),
            'next_step' => $this->onboardingService->getNextStep($progress),
            'started_at' => $progress->started_at?->toIso8601String(),
            'completed_at' => $progress->completed_at?->toIso8601String(),
        ];
    }
}
