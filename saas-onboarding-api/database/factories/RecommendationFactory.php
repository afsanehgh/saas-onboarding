<?php

namespace Database\Factories;

use App\Models\Recommendation;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Recommendation>
 */
class RecommendationFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $segments = ['new_user', 'casual_user', 'power_user', 'at_risk_user', 'standard_user'];

        return [
            'user_id' => User::factory(),
            'segment' => fake()->randomElement($segments),
            'recommendations' => [
                'features' => [
                    [
                        'key' => 'analytics',
                        'name' => 'Analytics',
                        'priority' => 1,
                        'reason' => 'Not yet explored',
                    ],
                    [
                        'key' => 'reports',
                        'name' => 'Reports',
                        'priority' => 2,
                        'reason' => 'Popular among similar users',
                    ],
                ],
                'content' => [
                    [
                        'title' => 'Getting Started Guide',
                        'type' => 'tutorial',
                    ],
                    [
                        'title' => 'Feature Highlights',
                        'type' => 'article',
                    ],
                ],
                'actions' => [
                    [
                        'action' => 'complete_profile',
                        'title' => 'Complete Your Profile',
                        'priority' => 1,
                    ],
                ],
                'timing' => [
                    'optimal_hour' => 9,
                    'timezone' => 'UTC',
                    'frequency' => 'daily',
                ],
            ],
            'generated_at' => now(),
        ];
    }
}
