<?php

namespace Database\Factories;

use App\Models\Promocode;
use App\Models\Tier;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Tier>
 */
class TierFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'minimum_orders' => $this->faker->randomElement([0, 3, 10]),
            'discount_value' => $this->faker->randomFloat(2, 5, 15),
            'promocode_id' => Promocode::factory(),
        ];
    }

    public function withMinOrders(int $minOrders, float $discountValue): static
    {
        return $this->state([
            'minimum_orders' => $minOrders,
            'discount_value' => $discountValue,
        ]);
    }
}
