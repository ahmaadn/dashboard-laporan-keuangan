<?php

namespace Database\Factories;

use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Product>
 */
class ProductFactory extends Factory
{
    public function definition(): array
    {
        $harga = fake()->numberBetween(50000, 600000);
        $modal = (int) round($harga * fake()->randomFloat(2, 0.35, 0.6));

        return [
            'category_id' => ProductCategory::factory(),
            'nama' => fake()->unique()->words(2, true),
            'sku' => fake()->unique()->bothify('???-###'),
            'harga' => $harga,
            'harga_modal' => $modal,
            'harga_grosir' => (int) round($harga * 0.9),
            'min_qty_grosir' => 3,
            'stok' => fake()->numberBetween(20, 100),
            'stok_minimum' => 5,
            'deskripsi' => fake()->optional()->sentence(),
            'is_active' => true,
            'created_by' => User::factory()->admin(),
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => ['is_active' => false]);
    }

    public function softDeleted(): static
    {
        return $this->state(fn (array $attributes) => ['deleted_at' => now()]);
    }
}
