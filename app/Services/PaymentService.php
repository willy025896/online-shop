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

    /**
     * Simulated refund — mirrors simulatePayment()'s shape. Does not change
     * `orders.status` (a refunded order stays `completed`); it only records
     * how much has been refunded so far. MUST run inside the caller's
     * DB::transaction (OrderService::finalizeReturn) so it commits atomically
     * with the OrderReturn/stock/coupon changes. Swap this internals for a
     * real gateway refund call once real payments are integrated.
     */
    public function simulateRefund(Order $order, float $amount): bool
    {
        $order->increment('refunded_amount', $amount);

        return true;
    }
}
