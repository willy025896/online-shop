<?php

namespace Database\Factories;

use App\Models\Conversation;
use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class MessageFactory extends Factory
{
    public function definition(): array
    {
        return [
            'conversation_id' => Conversation::factory(),
            'sender_id' => User::factory(),
            'product_id' => null,
            'body' => fake()->sentence(),
            'image_path' => null,
            'read_at' => null,
        ];
    }

    public function read(): static
    {
        return $this->state(fn () => ['read_at' => now()]);
    }

    public function image(): static
    {
        return $this->state(fn () => [
            'body' => null,
            'image_path' => 'messages/test/'.fake()->uuid().'.jpg',
        ]);
    }

    public function productCard(): static
    {
        return $this->state(fn () => [
            'body' => null,
            'product_id' => Product::factory(),
        ]);
    }
}
