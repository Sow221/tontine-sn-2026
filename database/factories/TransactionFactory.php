<?php

namespace Database\Factories;

use App\Models\Cycle;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class TransactionFactory extends Factory
{
    public function definition(): array
    {
        return [
            'cycle_id' => Cycle::factory(),
            'user_id'  => User::factory(),
            'amount'   => 50000,
            'method'   => 'cash',
            'status'   => 'pending',
        ];
    }
}
