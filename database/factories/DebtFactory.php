<?php

namespace Database\Factories;

use App\Models\Debt;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Debt>
 */
class DebtFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory()->admin(),
            'capital_injection_id' => null,
            'tanggal' => today()->toDateString(),
            'nominal' => fake()->numberBetween(500000, 30000000),
            'keterangan' => fake()->optional()->sentence(),
        ];
    }
}
