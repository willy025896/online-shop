<?php

namespace Database\Factories;

use App\Models\Order;
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
            'initiated_by' => 'buyer',
            'status' => 'requested',
            'reason' => fake()->sentence(),
            'responder_id' => null,
            'response_reason' => null,
            'responded_at' => null,
        ];
    }

    public function requested(): static
    {
        return $this->state(fn () => ['status' => 'requested']);
    }

    public function approved(): static
    {
        return $this->state(fn () => [
            'status' => 'approved',
            'responded_at' => now(),
        ]);
    }

    public function rejected(): static
    {
        return $this->state(fn () => [
            'status' => 'rejected',
            'response_reason' => fake()->sentence(),
            'responded_at' => now(),
        ]);
    }

    public function bySeller(): static
    {
        return $this->state(fn () => [
            'initiated_by' => 'seller',
            'status' => 'approved',
            'responded_at' => now(),
        ]);
    }
}
