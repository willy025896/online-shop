<?php

namespace App\Policies;

use App\Models\Order;
use App\Models\User;

class OrderPolicy
{
    public function view(User $user, Order $order): bool
    {
        return $user->id === $order->user_id
            || ($user->isSeller() && $user->shop?->id === $order->shop_id)
            || $user->isAdmin();
    }

    public function cancel(User $user, Order $order): bool
    {
        return $user->id === $order->user_id && $order->canBeCancelled();
    }

    public function updateStatus(User $user, Order $order): bool
    {
        return ($user->isSeller() && $user->shop?->id === $order->shop_id)
            || $user->isAdmin();
    }
}
