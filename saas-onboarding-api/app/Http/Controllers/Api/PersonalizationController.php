<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\PersonalizationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PersonalizationController extends Controller
{
    public function __construct(protected PersonalizationService $personalizationService) {}

    /**
     * Record a behaviour event (page view, feature use, etc.).
     */
    public function trackBehavior(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'action' => ['required', 'string', 'max:100'],
            'context' => ['sometimes', 'array'],
            'page_url' => ['nullable', 'string', 'max:2048'],
            'feature' => ['nullable', 'string', 'max:100'],
            'duration_seconds' => ['nullable', 'integer', 'min:0'],
            'device_type' => ['nullable', 'string', 'max:50'],
            'browser' => ['nullable', 'string', 'max:50'],
        ]);

        $behavior = $this->personalizationService->trackBehavior(
            $request->user(),
            $validated['action'],
            $validated
        );

        return response()->json([
            'success' => true,
            'data' => [
                'behavior_id' => $behavior->id,
                'action' => $behavior->action,
                'tracked_at' => $behavior->created_at?->toIso8601String(),
            ],
        ], 201);
    }

    /**
     * Get the user's recommendations, generating them if missing or stale.
     */
    public function getRecommendations(Request $request): JsonResponse
    {
        $user = $request->user();
        $recommendation = $this->personalizationService->getRecommendations($user);

        return response()->json([
            'success' => true,
            'data' => [
                'recommendations' => $recommendation->recommendations,
                'user_segment' => $recommendation->segment,
                'generated_at' => $recommendation->generated_at->toIso8601String(),
            ],
        ]);
    }

    /**
     * Force a fresh set of recommendations.
     */
    public function generateRecommendations(Request $request): JsonResponse
    {
        $user = $request->user();
        $recommendations = $this->personalizationService->generateRecommendations($user, true);

        return response()->json([
            'success' => true,
            'data' => [
                'recommendations' => $recommendations,
                'user_segment' => $user->segment,
                'generated_at' => now()->toIso8601String(),
            ],
        ], 201);
    }

    /**
     * Record that the user clicked, dismissed or completed a recommendation.
     */
    public function trackRecommendationInteraction(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'recommendation_type' => ['required', 'string', 'in:features,content,actions'],
            'recommendation_id' => ['required', 'string', 'max:100'],
            'action' => ['required', 'string', 'in:clicked,dismissed,completed'],
        ]);

        $this->personalizationService->trackRecommendationInteraction(
            $request->user(),
            $validated['recommendation_type'],
            $validated['recommendation_id'],
            $validated['action']
        );

        return response()->json([
            'success' => true,
            'message' => 'Interaction tracked successfully',
        ]);
    }

    /**
     * Paginated behaviour history, newest first.
     */
    public function getBehaviorHistory(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'limit' => ['nullable', 'integer', 'min:1', 'max:100'],
            'offset' => ['nullable', 'integer', 'min:0'],
        ]);

        $user = $request->user();

        $behaviors = $user->behaviors()
            ->latest()
            ->offset($validated['offset'] ?? 0)
            ->limit($validated['limit'] ?? 50)
            ->get();

        return response()->json([
            'success' => true,
            'data' => [
                'behaviors' => $behaviors,
                'total_count' => $user->behaviors()->count(),
            ],
        ]);
    }
}
