<?php

namespace Database\Factories;

use App\Models\OrderItem;
use App\Models\OrderReturn;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\OrderReturnItem>
 */
class OrderReturnItemFactory extends Factory
{
    public function definition(): array
    {
        return [
            'order_return_id' => OrderReturn::factory(),
            'order_item_id' => OrderItem::factory(),
            'quantity' => 1,
        ];
    }
}
