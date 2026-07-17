<?php

namespace Database\Factories;

use App\Models\Category;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Category>
 */
class CategoryFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name'      => fake()->word(),
            'category_id' => null,
        ];
    }

    public function withParent(\App\Models\Category $parent): static
    {
        return $this->state(['category_id' => $parent->id]);
    }
}
