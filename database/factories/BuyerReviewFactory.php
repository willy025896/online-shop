<?php

namespace Database\Factories;

use App\Models\BuyerReview;
use App\Models\Order;
use App\Models\Shop;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\BuyerReview>
 */
class BuyerReviewFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'shop_id' => Shop::factory(),
            'order_id' => Order::factory(),
            'rating' => fake()->numberBetween(1, 5),
            'comment' => fake()->optional(0.8)->paragraph(),
            'status' => BuyerReview::STATUS_PUBLISHED,
        ];
    }

    public function hidden(): static
    {
        return $this->state(fn () => ['status' => BuyerReview::STATUS_HIDDEN]);
    }
}
