<?php

namespace Database\Factories;

use App\Models\Income;
use App\Models\Product;
use App\Models\SalesReturn;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SalesReturn>
 */
class SalesReturnFactory extends Factory
{
    public function definition(): array
    {
        return [
            'income_id' => Income::factory(),
            'product_id' => Product::factory(),
            'user_id' => User::factory(),
            'tanggal' => fake()->dateTimeBetween('-6 months', 'now')->format('Y-m-d'),
            'jumlah' => 1,
            'nominal_retur' => fake()->numberBetween(50000, 250000),
            'alasan' => fake()->optional()->sentence(),
        ];
    }
}
