<?php

namespace Database\Factories;

use App\Models\PromocodeRedemption;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PromocodeRedemption>
 */
class PromocodeRedemptionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'discount_amount' => fake()->randomFloat(2, 5, 100),
            'promocode_id'    => \App\Models\Promocode::factory(),
            'order_id'        => \App\Models\Order::factory(),
        ];
    }
}
