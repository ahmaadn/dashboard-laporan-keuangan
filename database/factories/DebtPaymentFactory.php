<?php

namespace Database\Factories;

use App\Models\Debt;
use App\Models\DebtPayment;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DebtPayment>
 */
class DebtPaymentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'debt_id' => Debt::factory(),
            'user_id' => User::factory()->admin(),
            'tanggal' => today()->toDateString(),
            'nominal' => fake()->numberBetween(100000, 5000000),
            'sumber' => 'kas_usaha',
            'keterangan' => fake()->optional()->sentence(),
        ];
    }
}
