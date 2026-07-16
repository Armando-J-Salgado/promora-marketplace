<?php

namespace Database\Factories;

use App\Models\Order;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Order>
 */
class OrderFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'status'      => 'pending',
            'subtotal'    => fake()->randomFloat(2, 50, 500),
            'total'       => fake()->randomFloat(2, 50, 500),
            'customer_id' => \App\Models\Customer::factory(),
        ];
    }
}
