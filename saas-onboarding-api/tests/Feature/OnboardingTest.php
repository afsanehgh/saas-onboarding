<?php

namespace Tests\Feature;

use App\Models\OnboardingProgress;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class OnboardingTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        Sanctum::actingAs($this->user);
    }

    public function test_can_start_onboarding(): void
    {
        $response = $this->postJson('/api/onboarding/start');

        $response->assertCreated()
            ->assertJsonStructure([
                'success',
                'data' => ['progress_id', 'current_step', 'total_steps', 'status', 'next_step'],
            ])
            ->assertJsonPath('data.current_step', 1)
            ->assertJsonPath('data.next_step.name', 'welcome');

        $this->assertDatabaseHas('onboarding_progress', [
            'user_id' => $this->user->id,
            'status' => 'in_progress',
        ]);
    }

    public function test_restarting_onboarding_does_not_create_duplicates(): void
    {
        $this->postJson('/api/onboarding/start')->assertCreated();
        $this->postJson('/api/onboarding/start')->assertCreated();

        $this->assertSame(1, OnboardingProgress::where('user_id', $this->user->id)->count());
    }

    public function test_progress_reports_not_started_before_onboarding_begins(): void
    {
        $this->getJson('/api/onboarding/progress')
            ->assertOk()
            ->assertJsonPath('data.status', 'not_started')
            ->assertJsonPath('data.completion_percentage', 0);
    }

    public function test_can_get_onboarding_progress(): void
    {
        OnboardingProgress::factory()->inProgress()->create([
            'user_id' => $this->user->id,
            'current_step' => 1,
        ]);

        $this->getJson('/api/onboarding/progress')
            ->assertOk()
            ->assertJsonStructure([
                'success',
                'data' => ['progress_id', 'current_step', 'total_steps', 'status', 'completion_percentage'],
            ]);
    }

    public function test_can_complete_onboarding_step(): void
    {
        $progress = OnboardingProgress::factory()->inProgress()->create([
            'user_id' => $this->user->id,
            'current_step' => 1,
        ]);

        $this->postJson('/api/onboarding/steps/1/complete', ['step_data' => ['seen' => true]])
            ->assertOk()
            ->assertJsonPath('data.current_step', 2)
            ->assertJsonPath('data.completion_percentage', 20)
            ->assertJsonStructure(['data' => ['recommendations' => ['features', 'content', 'actions', 'timing']]]);

        $this->assertSame(2, $progress->fresh()->current_step);
    }

    public function test_profile_step_updates_the_user(): void
    {
        OnboardingProgress::factory()->inProgress()->create([
            'user_id' => $this->user->id,
            'current_step' => 2,
        ]);

        $this->postJson('/api/onboarding/steps/2/complete', [
            'step_data' => ['name' => 'Jane Citizen', 'company' => 'Acme', 'role' => 'CTO'],
        ])->assertOk();

        $this->user->refresh();
        $this->assertSame('Jane Citizen', $this->user->name);
        $this->assertSame('Acme', $this->user->company);
        $this->assertTrue($this->user->profile_completed);
    }

    public function test_can_skip_onboarding_step(): void
    {
        $progress = OnboardingProgress::factory()->inProgress()->create([
            'user_id' => $this->user->id,
            'current_step' => 1,
        ]);

        $this->postJson('/api/onboarding/steps/1/skip')->assertOk();

        $progress->refresh();
        $this->assertSame(2, $progress->current_step);
        $this->assertContains(1, $progress->skipped_steps);
    }

    public function test_cannot_jump_ahead_to_a_future_step(): void
    {
        OnboardingProgress::factory()->inProgress()->create([
            'user_id' => $this->user->id,
            'current_step' => 1,
        ]);

        $this->postJson('/api/onboarding/steps/4/complete')
            ->assertStatus(409)
            ->assertJsonPath('success', false);
    }

    public function test_completing_a_step_without_starting_returns_conflict(): void
    {
        $this->postJson('/api/onboarding/steps/1/complete')->assertStatus(409);
    }

    public function test_full_journey_marks_user_as_onboarded(): void
    {
        $this->postJson('/api/onboarding/start')->assertCreated();

        foreach ([1, 2, 3, 4] as $step) {
            $this->postJson("/api/onboarding/steps/{$step}/complete")->assertOk();
        }

        $this->postJson('/api/onboarding/steps/5/skip')
            ->assertOk()
            ->assertJsonPath('data.status', 'completed')
            ->assertJsonPath('data.completion_percentage', 100)
            ->assertJsonPath('data.next_step', null);

        $this->assertTrue($this->user->fresh()->onboarding_completed);
    }

    public function test_can_fetch_a_step_definition(): void
    {
        $this->getJson('/api/onboarding/steps/1')
            ->assertOk()
            ->assertJsonPath('data.name', 'welcome');

        $this->getJson('/api/onboarding/steps/99')->assertNotFound();
    }

    public function test_onboarding_requires_authentication(): void
    {
        $this->app['auth']->forgetGuards();

        $this->withHeaders(['Authorization' => ''])
            ->postJson('/api/onboarding/start')
            ->assertUnauthorized();
    }
}
