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
            'rules' => ['validity' => true, 'state' => true],
            'status' => 'active',
            'value' => $this->faker->randomFloat(2, 5, 50),
            'activation_date' => now()->subDay(),
            'expiration_date' => now()->addMonth(),
        ];
    }

    public function notYetActive(): static
    {
        return $this->state([
            'activation_date' => now()->addDays(5),
            'expiration_date' => now()->addDays(35),
        ]);
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

    public function paused(): static
    {
        return $this->state([
            'status' => 'paused',
        ]);
    }

    public function withMinPurchase(float $amount): static
    {
        return $this->state(fn ($a) => ['rules' => array_merge($a['rules'] ?? [], ['min_purchase_amount' => $amount])]);
    }

    public function withGlobalUsageLimit(int $limit): static
    {
        return $this->state(fn ($a) => ['rules' => array_merge($a['rules'] ?? [], ['global_usage_limit' => $limit])]);
    }

    public function withUserUsageLimit(int $limit): static
    {
        return $this->state(fn ($a) => ['rules' => array_merge($a['rules'] ?? [], ['user_usage_limit' => $limit])]);
    }

    public function withGlobalAmountLimit(float $limit): static
    {
        return $this->state(fn ($a) => ['rules' => array_merge($a['rules'] ?? [], ['global_amount_limit' => $limit])]);
    }

    public function withMaxDiscount(float $max): static
    {
        return $this->state(fn ($a) => ['rules' => array_merge($a['rules'] ?? [], ['max_discount_amount' => $max])]);
    }

    public function withEligibleCategories(array $categoryIds): static
    {
        return $this->state(fn ($a) => ['rules' => array_merge($a['rules'] ?? [], ['elegible_categories' => $categoryIds])]);
    }

    public function firstOrderOnly(): static
    {
        return $this->state(fn ($a) => ['rules' => array_merge($a['rules'] ?? [], ['first_order_only' => true])]);
    }

    public function restrictedUsage(): static
    {
        return $this->state(fn ($a) => ['rules' => array_merge($a['rules'] ?? [], ['restricted_usage' => true])]);
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

    public function tiered(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'tiered',
            'value' => 0,
        ]);
    }
}
