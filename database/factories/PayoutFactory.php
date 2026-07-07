<?php

namespace Database\Factories;

use App\Models\Payout;
use App\Models\Shop;
use Illuminate\Database\Eloquent\Factories\Factory;

class PayoutFactory extends Factory
{
    protected $model = Payout::class;

    public function definition(): array
    {
        return [
            'shop_id' => Shop::factory(),
            'gross_amount' => 0,
            'commission_amount' => 0,
            'shipping_amount' => 0,
            'net_amount' => 0,
            'paid_at' => now(),
        ];
    }
}
