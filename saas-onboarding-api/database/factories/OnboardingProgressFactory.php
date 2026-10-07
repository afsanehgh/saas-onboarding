<?php

namespace Database\Factories;

use App\Models\OnboardingProgress;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<OnboardingProgress>
 */
class OnboardingProgressFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'current_step' => fake()->numberBetween(1, 5),
            'total_steps' => 5,
            'completed_steps' => [],
            'skipped_steps' => [],
            'status' => fake()->randomElement(['in_progress', 'completed', 'abandoned']),
            'started_at' => now(),
            'completed_at' => fake()->optional(0.7)->dateTimeBetween('-30 days', 'now'),
        ];
    }

    /**
     * Indicate that onboarding is in progress.
     */
    public function inProgress(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'in_progress',
            'completed_at' => null,
        ]);
    }

    /**
     * Indicate that onboarding is completed.
     */
    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'completed',
            'completed_at' => now(),
            'current_step' => 5,
            'completed_steps' => [
                1 => ['completed_at' => now()->subMinutes(10)],
                2 => ['completed_at' => now()->subMinutes(8)],
                3 => ['completed_at' => now()->subMinutes(6)],
                4 => ['completed_at' => now()->subMinutes(4)],
                5 => ['completed_at' => now()],
            ],
        ]);
    }
}
