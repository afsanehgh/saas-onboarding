<?php

namespace Database\Factories;

use App\Models\User;
use App\Models\UserBehavior;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<UserBehavior>
 */
class UserBehaviorFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $actions = ['page_view', 'click', 'scroll', 'form_submit', 'feature_used', 'search'];
        $features = ['dashboard', 'analytics', 'reports', 'settings', 'integrations'];

        return [
            'user_id' => User::factory(),
            'action' => fake()->randomElement($actions),
            'context' => [
                'page_url' => fake()->url(),
                'referrer' => fake()->url(),
            ],
            'page_url' => fake()->url(),
            'feature' => fake()->randomElement($features),
            'duration_seconds' => fake()->numberBetween(5, 300),
            'device_type' => fake()->randomElement(['desktop', 'mobile', 'tablet']),
            'browser' => fake()->randomElement(['chrome', 'firefox', 'safari', 'edge']),
            'created_at' => fake()->dateTimeBetween('-14 days', 'now'),
        ];
    }
}
