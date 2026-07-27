<?php

namespace Database\Factories;

use App\Models\ExpenseCategory;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ExpenseCategory>
 */
class ExpenseCategoryFactory extends Factory
{
    public function definition(): array
    {
        return [
            'nama' => fake()->unique()->word(),
            'is_bahan_baku' => false,
        ];
    }

    public function bahanBaku(): static
    {
        return $this->state(fn (array $attributes) => [
            'nama' => 'Bahan Baku',
            'is_bahan_baku' => true,
        ]);
    }
}
