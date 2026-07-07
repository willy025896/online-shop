<?php

namespace Database\Factories;

use App\Models\Order;
use App\Models\Payout;
use App\Models\PayoutItem;
use Illuminate\Database\Eloquent\Factories\Factory;

class PayoutItemFactory extends Factory
{
    protected $model = PayoutItem::class;

    public function definition(): array
    {
        return [
            'payout_id' => Payout::factory(),
            'order_id' => Order::factory(),
            'gross_amount' => 0,
            'commission_amount' => 0,
            'shipping_amount' => 0,
            'net_amount' => 0,
        ];
    }
}
