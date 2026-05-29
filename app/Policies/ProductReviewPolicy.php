<?php

namespace App\Policies;

use App\Models\ProductReview;
use App\Models\User;

class ProductReviewPolicy
{
    public function update(User $user, ProductReview $review): bool
    {
        return $review->user_id === $user->id
            && $review->orderItem->order->isReviewWindowOpen();
    }

    public function delete(User $user, ProductReview $review): bool
    {
        return $review->user_id === $user->id
            && $review->orderItem->order->isReviewWindowOpen();
    }
}
