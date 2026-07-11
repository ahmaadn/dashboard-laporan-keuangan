<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    protected static ?string $password;

    public function definition(): array
    {
        return [
            'nama' => fake()->name(),
            'username' => fake()->unique()->userName(),
            'email' => fake()->unique()->safeEmail(),
            'password' => static::$password ??= Hash::make('password'),
            'peran' => 'pegawai',
            'dapat_melihat_dashboard' => false,
            'is_active' => true,
            'email_verified_at' => now(),
        ];
    }

    public function admin(): static
    {
        return $this->state(fn (array $attributes) => [
            'peran' => 'admin',
            'dapat_melihat_dashboard' => true,
        ]);
    }

    public function pegawai(): static
    {
        return $this->state(fn (array $attributes) => [
            'peran' => 'pegawai',
        ]);
    }

    public function withDashboard(): static
    {
        return $this->state(fn (array $attributes) => [
            'dapat_melihat_dashboard' => true,
        ]);
    }

    public function withoutDashboard(): static
    {
        return $this->state(fn (array $attributes) => [
            'dapat_melihat_dashboard' => false,
        ]);
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    public function softDeleted(): static
    {
        return $this->state(fn (array $attributes) => [
            'deleted_at' => now(),
        ]);
    }
}
