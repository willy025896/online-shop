<?php

namespace Database\Factories;

use App\Models\Order;
use App\Models\OrderReturn;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\OrderReturn>
 */
class OrderReturnFactory extends Factory
{
    public function definition(): array
    {
        return [
            'order_id' => Order::factory(),
            'status' => OrderReturn::STATUS_REQUESTED,
            'reason' => fake()->sentence(),
            'responder_id' => null,
            'response_reason' => null,
            'responded_at' => null,
            'refund_amount' => null,
        ];
    }

    public function requested(): static
    {
        return $this->state(fn () => ['status' => OrderReturn::STATUS_REQUESTED]);
    }

    public function approved(): static
    {
        return $this->state(fn () => [
            'status' => OrderReturn::STATUS_APPROVED,
            'responded_at' => now(),
        ]);
    }

    public function rejected(): static
    {
        return $this->state(fn () => [
            'status' => OrderReturn::STATUS_REJECTED,
            'response_reason' => fake()->sentence(),
            'responded_at' => now(),
        ]);
    }
}
