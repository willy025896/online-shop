<?php

namespace App\Services;

use App\Models\Order;
use App\Notifications\OrderPaidNotification;
use Illuminate\Support\Facades\DB;

class PaymentService
{
    public function simulatePayment(Order $order): bool
    {
        // Wrap in a transaction so the status-log insert fired by the Order
        // `updated` event commits atomically with the status change.
        DB::transaction(function () use ($order) {
            $order->update([
                'status' => Order::STATUS_PAID,
                'paid_at' => now(),
            ]);

            $order->loadMissing('shop.user');
            $order->shop?->user?->notify(new OrderPaidNotification($order));
        });

        return true;
    }
}
