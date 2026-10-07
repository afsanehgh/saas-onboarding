<?php

namespace Tests\Feature;

use App\Models\Recommendation;
use App\Models\User;
use App\Models\UserBehavior;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class PersonalizationTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        Sanctum::actingAs($this->user);
    }

    public function test_can_track_user_behavior(): void
    {
        $this->postJson('/api/personalization/behavior', [
            'action' => 'page_view',
            'context' => ['page_url' => '/dashboard'],
            'feature' => 'analytics',
            'device_type' => 'desktop',
            'browser' => 'chrome',
        ])
            ->assertCreated()
            ->assertJsonStructure(['success', 'data' => ['behavior_id', 'action', 'tracked_at']]);

        $this->assertDatabaseHas('user_behaviors', [
            'user_id' => $this->user->id,
            'action' => 'page_view',
            'feature' => 'analytics',
            'page_url' => '/dashboard',
        ]);
    }

    public function test_behavior_requires_an_action(): void
    {
        $this->postJson('/api/personalization/behavior', [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('action');
    }

    public function test_can_get_recommendations(): void
    {
        Recommendation::factory()->create([
            'user_id' => $this->user->id,
            'segment' => 'new_user',
        ]);

        $this->getJson('/api/personalization/recommendations')
            ->assertOk()
            ->assertJsonPath('data.user_segment', 'new_user')
            ->assertJsonStructure(['success', 'data' => ['recommendations', 'user_segment', 'generated_at']]);
    }

    public function test_recommendations_are_generated_when_missing(): void
    {
        $this->getJson('/api/personalization/recommendations')
            ->assertOk()
            ->assertJsonStructure(['data' => ['recommendations' => ['features', 'content', 'actions', 'timing']]]);

        $this->assertDatabaseHas('recommendations', ['user_id' => $this->user->id]);
    }

    public function test_can_generate_recommendations(): void
    {
        UserBehavior::factory()->count(10)->create(['user_id' => $this->user->id]);

        $this->postJson('/api/personalization/recommendations/generate')
            ->assertCreated()
            ->assertJsonStructure(['success', 'data' => ['recommendations', 'user_segment', 'generated_at']]);

        $this->assertDatabaseHas('recommendations', ['user_id' => $this->user->id]);
    }

    public function test_used_features_are_not_recommended(): void
    {
        UserBehavior::factory()->create(['user_id' => $this->user->id, 'feature' => 'dashboard']);
        UserBehavior::factory()->create(['user_id' => $this->user->id, 'feature' => 'analytics']);

        $keys = collect($this->postJson('/api/personalization/recommendations/generate')
            ->json('data.recommendations.features'))->pluck('key')->all();

        $this->assertSame(['integrations', 'automation', 'collaboration'], $keys);
    }

    public function test_can_track_recommendation_interaction(): void
    {
        $this->postJson('/api/personalization/recommendations/interaction', [
            'recommendation_type' => 'features',
            'recommendation_id' => 'analytics',
            'action' => 'clicked',
        ])->assertOk();
    }

    public function test_can_get_behavior_history(): void
    {
        UserBehavior::factory()->count(5)->create(['user_id' => $this->user->id]);
        UserBehavior::factory()->count(3)->create(); // other users' data must not leak

        $response = $this->getJson('/api/personalization/behavior-history')
            ->assertOk()
            ->assertJsonPath('data.total_count', 5);

        $this->assertCount(5, $response->json('data.behaviors'));
    }
}
