<?php

namespace Database\Factories;

use App\Models\Income;
use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Income>
 */
class IncomeFactory extends Factory
{
    public function definition(): array
    {
        $jumlah = fake()->numberBetween(1, 6);
        $hargaSatuan = fake()->numberBetween(50000, 500000);

        return [
            'product_id' => Product::factory(),
            'user_id' => User::factory(),
            'tanggal_transaksi' => fake()->dateTimeBetween('-1 year', 'now')->format('Y-m-d'),
            'jumlah' => $jumlah,
            'harga_satuan' => $hargaSatuan,
            'total' => $jumlah * $hargaSatuan,
            'keterangan' => fake()->optional()->sentence(),
        ];
    }

    public function softDeleted(): static
    {
        return $this->state(fn (array $attributes) => ['deleted_at' => now()]);
    }
}
