<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\AdminActionLog>
 */
class AdminActionLogFactory extends Factory
{
    public function definition(): array
    {
        return [
            'admin_id' => User::factory()->admin(),
            'action' => 'user.role_updated',
            'subject_type' => User::class,
            'subject_id' => User::factory(),
            'changes' => ['from' => 'customer', 'to' => 'seller'],
        ];
    }
}
