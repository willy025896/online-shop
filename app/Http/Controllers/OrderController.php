<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Services\OrderService;
use App\Services\PaymentService;
use Inertia\Inertia;

class OrderController extends Controller
{
    public function __construct(
        private OrderService $orderService,
        private PaymentService $paymentService,
    ) {}

    public function index()
    {
        $orders = auth()->user()->orders()
            ->with('shop', 'items')
            ->latest()
            ->paginate(10);

        return Inertia::render('Orders/Index', [
            'orders' => $orders,
        ]);
    }

    public function show(Order $order)
    {
        $this->authorize('view', $order);

        $order->load('shop', 'items.product');

        return Inertia::render('Orders/Show', [
            'order' => $order,
        ]);
    }

    public function simulatePayment(Order $order)
    {
        $this->authorize('view', $order);

        if ($order->isPaid()) {
            return back()->with('error', 'Order is already paid.');
        }

        $this->paymentService->simulatePayment($order);

        return back()->with('success', 'Payment successful!');
    }

    public function cancel(Order $order)
    {
        $this->authorize('cancel', $order);

        $this->orderService->cancelOrder($order);

        return back()->with('success', 'Order cancelled.');
    }
}
