<?php

namespace App\Services;

use App\Models\Order;
use Illuminate\Support\Facades\DB;

class PaymentService
{
    public function simulatePayment(Order $order): bool
    {
        // Wrap in a transaction so the status-log insert fired by the Order
        // `updated` event commits atomically with the status change.
        DB::transaction(fn () => $order->update([
            'status' => Order::STATUS_PAID,
            'paid_at' => now(),
        ]));

        return true;
    }
}
