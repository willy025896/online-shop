<?php

namespace Database\Factories;

use App\Models\Coupon;
use App\Models\Shop;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class CouponFactory extends Factory
{
    protected $model = Coupon::class;

    public function definition(): array
    {
        return [
            'shop_id' => Shop::factory(),
            'code' => strtoupper(Str::random(8)),
            'type' => Coupon::TYPE_PERCENTAGE,
            'value' => 10,
            'min_spend' => 0,
            'max_discount' => null,
            'usage_limit' => null,
            'used_count' => 0,
            'per_user_limit' => null,
            'starts_at' => null,
            'expires_at' => null,
            'is_active' => true,
        ];
    }

    public function fixed(float $amount): static
    {
        return $this->state(fn () => ['type' => Coupon::TYPE_FIXED, 'value' => $amount]);
    }

    public function percentage(float $percent): static
    {
        return $this->state(fn () => ['type' => Coupon::TYPE_PERCENTAGE, 'value' => $percent]);
    }

    public function inactive(): static
    {
        return $this->state(fn () => ['is_active' => false]);
    }

    public function expired(): static
    {
        return $this->state(fn () => ['expires_at' => now()->subDay()]);
    }
}
