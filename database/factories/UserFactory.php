<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name'               => fake()->name(),
            'email'              => fake()->unique()->safeEmail(),
            'role'               => 'member',
            'preferred_language' => 'fr',
            'kyc_verified'       => false,
            'is_active'          => true,
            'remember_token'     => Str::random(10),
        ];
    }

    public function manager(): static
    {
        return $this->state(fn() => ['role' => 'manager']);
    }

    public function admin(): static
    {
        return $this->state(fn() => ['role' => 'admin']);
    }

    public function kycVerified(): static
    {
        return $this->state(fn() => ['kyc_verified' => true]);
    }
}
