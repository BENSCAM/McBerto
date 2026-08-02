<?php

namespace Database\Factories;

use App\Enums\ServiceArea;
use App\Models\Category;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Product>
 */
class ProductFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'category_id' => Category::factory(),
            'name' => fake()->unique()->words(2, true),
            'price' => fake()->numberBetween(500, 5000),
            'service_area' => ServiceArea::Standard,
            'stock_quantity' => null,
            'is_active' => true,
        ];
    }
}
