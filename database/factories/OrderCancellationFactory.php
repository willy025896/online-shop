<?php

namespace Database\Factories;

use App\Models\Order;
use App\Models\OrderCancellation;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\OrderCancellation>
 */
class OrderCancellationFactory extends Factory
{
    public function definition(): array
    {
        return [
            'order_id' => Order::factory(),
            'initiated_by' => OrderCancellation::INITIATED_BY_BUYER,
            'status' => OrderCancellation::STATUS_REQUESTED,
            'reason' => fake()->sentence(),
            'responder_id' => null,
            'response_reason' => null,
            'responded_at' => null,
        ];
    }

    public function requested(): static
    {
        return $this->state(fn () => ['status' => OrderCancellation::STATUS_REQUESTED]);
    }

    public function approved(): static
    {
        return $this->state(fn () => [
            'status' => OrderCancellation::STATUS_APPROVED,
            'responded_at' => now(),
        ]);
    }

    public function rejected(): static
    {
        return $this->state(fn () => [
            'status' => OrderCancellation::STATUS_REJECTED,
            'response_reason' => fake()->sentence(),
            'responded_at' => now(),
        ]);
    }

    public function bySeller(): static
    {
        return $this->state(fn () => [
            'initiated_by' => OrderCancellation::INITIATED_BY_SELLER,
            'status' => OrderCancellation::STATUS_APPROVED,
            'responded_at' => now(),
        ]);
    }
}
