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
        return $user->id === $order->user_id
            && ($order->canBeCancelledDirectly() || $order->canRequestCancellation());
    }

    public function updateStatus(User $user, Order $order): bool
    {
        return ($user->isSeller() && $user->shop?->id === $order->shop_id)
            || $user->isAdmin();
    }

    public function manageCancellation(User $user, Order $order): bool
    {
        return ($user->isSeller() && $user->shop?->id === $order->shop_id)
            && $order->pendingCancellation() !== null;
    }

    public function cancelAsSeller(User $user, Order $order): bool
    {
        return ($user->isSeller() && $user->shop?->id === $order->shop_id)
            && $order->canBeCancelledBySeller();
    }

    public function createReview(User $user, Order $order): bool
    {
        return $order->user_id === $user->id
            && $order->status === Order::STATUS_COMPLETED
            && $order->isReviewWindowOpen();
    }

    public function requestReturn(User $user, Order $order): bool
    {
        return $user->id === $order->user_id
            && $order->canRequestReturn();
    }

    public function manageReturn(User $user, Order $order): bool
    {
        return ($user->isSeller() && $user->shop?->id === $order->shop_id)
            && $order->pendingReturn() !== null;
    }

    public function reorder(User $user, Order $order): bool
    {
        return $user->id === $order->user_id;
    }
}
