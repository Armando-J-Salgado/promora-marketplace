<?php

namespace Database\Factories;

use App\Models\Promocode;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Promocode>
 */
class PromocodeFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'type' => $this->faker->randomElement(['fixed', 'percent', 'tiered']),
            'rules' => [],
            'status' => 'active',
            'value' => $this->faker->randomFloat(2, 5, 50),
            'activation_date' => now()->subDay(),
            'expiration_date' => now()->addMonth(),
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => ['status' => 'paused']);
    }

    public function expired(): static
    {
        return $this->state(fn (array $attributes) => [
            'activation_date' => now()->subMonths(2),
            'expiration_date' => now()->subDay(),
        ]);
    }

    public function draft(): static
    {
        return $this->state(fn (array $attributes) => ['status' => 'draft']);
    }

    public function fixed(float $value = 10.0): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'fixed',
            'value' => $value,
        ]);
    }

    public function percent(float $value = 15.0): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'percent',
            'value' => $value,
        ]);
    }
}