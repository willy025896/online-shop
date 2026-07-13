<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Services\CartService;
use App\Services\ConversationService;
use App\Services\OrderService;
use App\Services\PaymentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Inertia\Inertia;

class OrderController extends Controller
{
    public function __construct(
        private OrderService $orderService,
        private PaymentService $paymentService,
        private ConversationService $conversationService,
        private CartService $cartService,
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

        $order->load('shop', 'items.product', 'latestCancellation', 'latestReturn.items');

        $order->items->each(fn ($item) => $item->returnable_quantity = $item->remainingReturnableQuantity());

        return Inertia::render('Orders/Show', [
            'order' => $order,
            'canCancelDirectly' => $order->canBeCancelledDirectly(),
            'canRequestCancellation' => $order->canRequestCancellation(),
            'canRequestReturn' => $order->canRequestReturn(),
        ]);
    }

    public function pay(Order $order)
    {
        $this->authorize('view', $order);

        // Only a still-pending order can start a new checkout session — an
        // already-paid order obviously shouldn't be charged again, and a
        // cancelled/completed order must not be resurrected by generating a
        // fresh (validly signed) checkout for it either.
        if (! $order->isPending()) {
            return redirect()->route('orders.show', $order)->with('error', 'This order can no longer be paid.');
        }

        $redirect = $this->paymentService->checkoutRedirectData($order);

        return view('payments.ecpay-redirect', $redirect);
    }

    public function payReturn(Order $order)
    {
        $this->authorize('view', $order);

        // This is just the buyer's browser bouncing back from ECPay's hosted
        // page (ClientBackURL) — it carries no trustworthy payment result, so
        // the message must stay neutral rather than claiming success.
        return redirect()->route('orders.show', $order)->with('success', 'Returned from the payment gateway — confirming your payment result…');
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

    public function requestReturn(Request $request, Order $order)
    {
        $this->authorize('requestReturn', $order);

        $validated = $request->validate([
            'reason' => 'required|string|max:1000',
            'items' => 'required|array|min:1',
            'items.*.order_item_id' => 'required|integer',
            'items.*.quantity' => 'required|integer|min:1',
        ]);

        $order->loadMissing('items');

        Validator::make($validated, [])->after(function ($validator) use ($validated, $order) {
            $groups = [];

            foreach ($validated['items'] as $index => $row) {
                $item = $order->items->firstWhere('id', $row['order_item_id']);

                if ($item === null) {
                    $validator->errors()->add("items.{$index}.order_item_id", 'Invalid item for this order.');

                    continue;
                }

                $groups[$row['order_item_id']]['item'] ??= $item;
                $groups[$row['order_item_id']]['indices'][] = $index;
                $groups[$row['order_item_id']]['total'] = ($groups[$row['order_item_id']]['total'] ?? 0) + $row['quantity'];
            }

            // Group by order_item_id first — checking each row against the
            // remaining quantity independently would let two rows for the same
            // item each pass individually while their combined total exceeds
            // what's actually left to return.
            foreach ($groups as $group) {
                $remaining = $group['item']->remainingReturnableQuantity();

                if ($group['total'] > $remaining) {
                    foreach ($group['indices'] as $index) {
                        $validator->errors()->add("items.{$index}.quantity", "Only {$remaining} unit(s) left to return for this item.");
                    }
                }
            }
        })->validate();

        $this->orderService->requestReturn($order, $validated['items'], $validated['reason']);

        return back()->with('success', 'Return request submitted, awaiting seller review.');
    }

    public function startConversation(Order $order)
    {
        $this->authorize('view', $order);

        $conversation = $this->conversationService->getOrCreateForOrder($order);

        return redirect()->route('messages.show', $conversation);
    }

    public function reorder(Order $order)
    {
        $this->authorize('reorder', $order);

        ['added' => $added, 'total' => $total] = $this->cartService->reorder($order);

        if ($added === 0) {
            return back()->with('error', 'None of the items in this order are currently available to purchase.');
        }

        $message = $added === $total
            ? 'Added all items to your cart.'
            : "Added {$added} of {$total} item(s) to your cart — the rest are no longer available.";

        return redirect()->route('cart.index')->with('success', $message);
    }
}
