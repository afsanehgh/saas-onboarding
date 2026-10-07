<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\OnboardingController;
use App\Http\Controllers\Api\PersonalizationController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Every route here is prefixed with /api. Authenticated routes expect a
| Sanctum personal access token in the "Authorization: Bearer <token>" header.
|
*/

// Health check (no auth required)
Route::get('/health', function () {
    return response()->json([
        'status' => 'healthy',
        'timestamp' => now()->toIso8601String(),
        'version' => config('app.version'),
    ]);
});

// Authentication
Route::prefix('auth')->middleware('throttle:auth')->group(function () {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);
});

Route::middleware(['auth:sanctum', 'throttle:api'])->group(function () {
    Route::get('/auth/me', [AuthController::class, 'me']);
    Route::post('/auth/logout', [AuthController::class, 'logout']);

    // Onboarding
    Route::prefix('onboarding')->group(function () {
        Route::post('/start', [OnboardingController::class, 'start']);
        Route::get('/progress', [OnboardingController::class, 'progress']);
        Route::get('/steps/{stepNumber}', [OnboardingController::class, 'getStep'])->whereNumber('stepNumber');
        Route::post('/steps/{stepNumber}/complete', [OnboardingController::class, 'completeStep'])->whereNumber('stepNumber');
        Route::post('/steps/{stepNumber}/skip', [OnboardingController::class, 'skipStep'])->whereNumber('stepNumber');
    });

    // Personalisation
    Route::prefix('personalization')->group(function () {
        Route::post('/behavior', [PersonalizationController::class, 'trackBehavior']);
        Route::get('/behavior-history', [PersonalizationController::class, 'getBehaviorHistory']);
        Route::get('/recommendations', [PersonalizationController::class, 'getRecommendations']);
        Route::post('/recommendations/generate', [PersonalizationController::class, 'generateRecommendations']);
        Route::post('/recommendations/interaction', [PersonalizationController::class, 'trackRecommendationInteraction']);
    });
});
