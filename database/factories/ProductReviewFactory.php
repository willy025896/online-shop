<?php

namespace Database\Factories;

use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductReview;
use App\Models\Shop;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ProductReview>
 */
class ProductReviewFactory extends Factory
{
    public function definition(): array
    {
        return [
            'product_id' => Product::factory(),
            'shop_id' => Shop::factory(),
            'user_id' => User::factory(),
            'order_item_id' => OrderItem::factory(),
            'rating' => fake()->numberBetween(1, 5),
            'comment' => fake()->optional(0.8)->paragraph(),
            'seller_reply' => null,
            'seller_replied_at' => null,
            'status' => ProductReview::STATUS_PUBLISHED,
        ];
    }

    public function withReply(): static
    {
        return $this->state(fn () => [
            'seller_reply' => fake()->sentence(),
            'seller_replied_at' => now(),
        ]);
    }

    public function hidden(): static
    {
        return $this->state(fn () => ['status' => ProductReview::STATUS_HIDDEN]);
    }
}
