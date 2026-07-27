<?php

namespace Database\Factories;

use App\Models\CapitalInjection;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CapitalInjection>
 */
class CapitalInjectionFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory()->admin(),
            'tanggal' => fake()->dateTimeBetween('-6 months', 'now')->format('Y-m-d'),
            'nominal' => fake()->numberBetween(500000, 30000000),
            'keterangan' => fake()->optional()->sentence(),
        ];
    }
}
