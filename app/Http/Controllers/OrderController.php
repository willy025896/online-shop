<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Services\ConversationService;
use App\Services\OrderService;
use App\Services\PaymentService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class OrderController extends Controller
{
    public function __construct(
        private OrderService $orderService,
        private PaymentService $paymentService,
        private ConversationService $conversationService,
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

        $order->load('shop', 'items.product', 'latestCancellation');

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

    public function cancel(Request $request, Order $order)
    {
        $this->authorize('cancel', $order);

        $validated = $request->validate([
            'reason' => 'required|string|max:1000',
        ]);

        if ($order->canBeCancelledDirectly()) {
            $this->orderService->directCancelByBuyer($order, $validated['reason']);

            return back()->with('success', 'Order cancelled.');
        }

        $this->orderService->requestCancellation($order, $validated['reason']);

        return back()->with('success', 'Cancellation request submitted, awaiting seller review.');
    }

    public function startConversation(Order $order)
    {
        $this->authorize('view', $order);

        $conversation = $this->conversationService->getOrCreateForOrder($order);

        return redirect()->route('messages.show', $conversation);
    }
}
