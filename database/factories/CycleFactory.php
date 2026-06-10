<?php

namespace Database\Factories;

use App\Models\Tontine;
use Illuminate\Database\Eloquent\Factories\Factory;

class CycleFactory extends Factory
{
    public function definition(): array
    {
        return [
            'tontine_id' => Tontine::factory(),
            'cycle_number' => 1,
            'due_date' => now()->addDays(7),
            'status' => 'pending',
            'total_collected' => 0,
        ];
    }
}
