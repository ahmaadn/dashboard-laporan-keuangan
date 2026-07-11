<?php

namespace Database\Factories;

use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Expense>
 */
class ExpenseFactory extends Factory
{
    public function definition(): array
    {
        return [
            'category_id' => ExpenseCategory::factory(),
            'user_id' => User::factory(),
            'tanggal_transaksi' => fake()->dateTimeBetween('-1 year', 'now')->format('Y-m-d'),
            'nominal' => fake()->numberBetween(30000, 1500000),
            'keterangan' => fake()->optional()->sentence(),
        ];
    }

    public function softDeleted(): static
    {
        return $this->state(fn (array $attributes) => ['deleted_at' => now()]);
    }
}
