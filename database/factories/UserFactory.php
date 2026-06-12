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
        static $senegaleseNames = [
            'Amadou Diallo', 'Fatou Ndiaye', 'Moussa Sarr', 'Aissatou Fall',
            'Ibrahima Diop', 'Mariama Mbaye', 'Ousmane Sow', 'Rokhaya Gueye',
            'Cheikh Diouf', 'Ndéye Faye', 'Abdoulaye Ba', 'Khady Thiam',
            'Mamadou Cissé', 'Sokhna Sy', 'Lamine Koné', 'Astou Traoré',
            'Pape Diagne', 'Binta Camara', 'Serigne Toure', 'Yacine Badji',
        ];

        return [
            'name' => fake()->randomElement($senegaleseNames),
            'email' => fake()->unique()->safeEmail(),
            'phone_number' => '+221 7'.fake()->randomElement(['7', '6', '0', '8']).fake()->numerify(' ### ## ##'),
            'password' => bcrypt('password'),
            'role' => 'member',

            'email_verified_at' => now(),
            'kyc_verified' => false,
            'is_active' => true,
            'remember_token' => Str::random(10),
        ];
    }

    public function admin(): static
    {
        return $this->state(fn () => ['role' => 'admin']);
    }

    public function kycVerified(): static
    {
        return $this->state(fn () => ['kyc_verified' => true]);
    }

    public function inactive(): static
    {
        return $this->state(fn () => ['is_active' => false]);
    }

    public function unverified(): static
    {
        return $this->state(fn () => ['kyc_verified' => false, 'kyc_document' => null]);
    }
}
