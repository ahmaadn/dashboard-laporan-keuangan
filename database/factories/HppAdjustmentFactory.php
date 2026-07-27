<?php

namespace Database\Factories;

use App\Models\HppAdjustment;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<HppAdjustment>
 */
class HppAdjustmentFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory()->admin(),
            'tanggal' => fake()->dateTimeBetween('-6 months', 'now')->format('Y-m-d'),
            'nominal' => fake()->numberBetween(-100000, 100000),
            'keterangan' => fake()->optional()->sentence(),
        ];
    }
}
