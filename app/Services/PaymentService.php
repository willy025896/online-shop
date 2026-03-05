<?php

namespace App\Services;

use App\Models\Order;

class PaymentService
{
    public function simulatePayment(Order $order): bool
    {
        $order->update([
            'status' => 'paid',
            'paid_at' => now(),
        ]);

        return true;
    }
}
