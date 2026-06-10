<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class TontineFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => fake()->words(3, true),
            'description' => fake()->sentence(),
            'amount' => fake()->randomElement([5000, 10000, 25000, 50000]),
            'frequency' => fake()->randomElement(['weekly', 'monthly']),
            'type' => 'fixed',
            'status' => 'pending',
            'start_date' => now()->addDays(3)->toDateString(),
            'max_members' => 10,
            'penalty_rate' => 0,
            'draw_method' => 'sequential',
            'weighted_draw' => false,
            'veto_threshold' => null,
            'quorum' => 1,
            'created_by' => User::factory(),
        ];
    }

    public function active(): static
    {
        return $this->state(fn () => ['status' => 'active']);
    }
}
