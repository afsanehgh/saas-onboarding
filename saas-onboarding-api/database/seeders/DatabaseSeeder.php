<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\UserBehavior;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed a demo account so the API and frontend can be tried straight away.
     *
     * Login: demo@example.com / password
     */
    public function run(): void
    {
        $user = User::updateOrCreate(
            ['email' => 'demo@example.com'],
            [
                'name' => 'Demo User',
                'password' => 'password',
                'company' => 'Example Pty Ltd',
                'role' => 'Product Manager',
                'timezone' => 'Australia/Melbourne',
            ]
        );

        if ($user->behaviors()->doesntExist()) {
            UserBehavior::factory()->count(15)->create(['user_id' => $user->id]);
        }
    }
}
