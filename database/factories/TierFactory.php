<?php

namespace Database\Factories;

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
            'minimum_orders' => 0,
            'discount_value' => 5.00,
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
