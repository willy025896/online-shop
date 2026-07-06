<?php

namespace Database\Factories;

use App\Models\Order;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class ConversationFactory extends Factory
{
    public function definition(): array
    {
        return [
            'order_id' => Order::factory(),
            'buyer_id' => User::factory(),
            'seller_user_id' => User::factory(),
            'last_message_at' => null,
        ];
    }

    public function productInquiry(): static
    {
        return $this->state(fn () => ['order_id' => null]);
    }
}
