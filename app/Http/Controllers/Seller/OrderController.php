<?php

namespace App\Http\Controllers\Seller;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\OrderService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class OrderController extends Controller
{
    public function __construct(
        private OrderService $orderService,
    ) {}

    public function index()
    {
        $orders = auth()->user()->shop->orders()
            ->with('user', 'items')
            ->latest()
            ->paginate(10);

        return Inertia::render('Seller/Orders/Index', [
            'orders' => $orders,
        ]);
    }

    public function show(Order $order)
    {
        $this->authorize('view', $order);

        $order->load('user', 'items.product', 'latestCancellation.responder');

        return Inertia::render('Seller/Orders/Show', [
            'order' => $order,
        ]);
    }

    public function updateStatus(Request $request, Order $order)
    {
        $this->authorize('updateStatus', $order);

        $validated = $request->validate([
            'status' => ['required', Rule::in([Order::STATUS_PROCESSING, Order::STATUS_SHIPPED, Order::STATUS_COMPLETED])],
        ]);

        $order->update($validated);

        return back()->with('success', 'Order status updated.');
    }

    public function cancel(Request $request, Order $order)
    {
        $this->authorize('cancelAsSeller', $order);

        $validated = $request->validate([
            'reason' => 'required|string|max:1000',
        ]);

        $this->orderService->cancelBySeller($order, $request->user(), $validated['reason']);

        return back()->with('success', 'Order cancelled.');
    }

    public function approveCancellation(Order $order)
    {
        $this->authorize('manageCancellation', $order);

        $this->orderService->approveCancellation($order->pendingCancellation(), request()->user());

        return back()->with('success', 'Cancellation approved.');
    }

    public function rejectCancellation(Request $request, Order $order)
    {
        $this->authorize('manageCancellation', $order);

        $validated = $request->validate([
            'response_reason' => 'required|string|max:1000',
        ]);

        $this->orderService->rejectCancellation($order->pendingCancellation(), $request->user(), $validated['response_reason']);

        return back()->with('success', 'Cancellation rejected.');
    }
}
