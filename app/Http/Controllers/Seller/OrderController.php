<?php

namespace App\Http\Controllers\Seller;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\OrderService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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
            'canSellerCancel' => $order->isActive() && $order->pendingCancellation() === null,
            'nextStatuses' => [
                'paid' => 'processing',
                'processing' => 'shipped',
                'shipped' => 'completed',
            ],
        ]);
    }

    public function updateStatus(Request $request, Order $order)
    {
        $this->authorize('updateStatus', $order);

        $validated = $request->validate([
            'status' => ['required', Rule::in([Order::STATUS_PROCESSING, Order::STATUS_SHIPPED, Order::STATUS_COMPLETED])],
        ]);

        abort_unless($order->canTransitionStatusTo($validated['status']), 422, 'Invalid status transition.');

        // Wrap in a transaction so the status-log insert fired by the Order
        // `updated` event commits atomically with the status change.
        DB::transaction(fn () => $order->update($validated));

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

    public function approveCancellation(Order $order): RedirectResponse
    {
        $this->authorize('manageCancellation', $order);

        $cancellation = $order->pendingCancellation();
        abort_if($cancellation === null, 409, 'No pending cancellation request.');

        $this->orderService->approveCancellation($cancellation, request()->user());

        return back()->with('success', 'Cancellation approved.');
    }

    public function rejectCancellation(Request $request, Order $order): RedirectResponse
    {
        $this->authorize('manageCancellation', $order);

        $validated = $request->validate([
            'response_reason' => 'required|string|max:1000',
        ]);

        $cancellation = $order->pendingCancellation();
        abort_if($cancellation === null, 409, 'No pending cancellation request.');

        $this->orderService->rejectCancellation($cancellation, $request->user(), $validated['response_reason']);

        return back()->with('success', 'Cancellation rejected.');
    }
}
