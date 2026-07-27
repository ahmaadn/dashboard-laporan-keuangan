<?php

namespace Database\Factories;

use App\Models\Product;
use App\Models\StockMovement;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<StockMovement>
 */
class StockMovementFactory extends Factory
{
    public function definition(): array
    {
        return [
            'product_id' => Product::factory(),
            'user_id' => User::factory(),
            'tanggal' => fake()->dateTimeBetween('-6 months', 'now')->format('Y-m-d'),
            'jenis' => 'masuk',
            'jumlah' => fake()->numberBetween(1, 20),
            'sumber' => 'restok',
            'ref_id' => null,
            'keterangan' => fake()->optional()->sentence(),
        ];
    }
}
