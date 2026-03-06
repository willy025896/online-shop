<?php

namespace Database\Factories;

use App\Models\Category;
use App\Models\Shop;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Product>
 */
class ProductFactory extends Factory
{
    public function definition(): array
    {
        $name = fake()->words(3, true);
        $price = fake()->randomFloat(2, 5, 500);

        return [
            'shop_id' => Shop::factory(),
            'category_id' => null,
            'name' => ucfirst($name),
            'slug' => Str::slug($name) . '-' . Str::random(6),
            'description' => fake()->optional()->paragraphs(2, true),
            'price' => $price,
            'compare_price' => fake()->optional(0.3)->randomFloat(2, $price + 5, $price + 100),
            'stock' => fake()->numberBetween(0, 200),
            'status' => 'active',
            'is_featured' => fake()->boolean(20),
        ];
    }

    public function draft(): static
    {
        return $this->state(fn () => ['status' => 'draft']);
    }

    public function inactive(): static
    {
        return $this->state(fn () => ['status' => 'inactive']);
    }

    public function outOfStock(): static
    {
        return $this->state(fn () => ['stock' => 0]);
    }
}