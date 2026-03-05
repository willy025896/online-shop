<?php

namespace App\Http\Controllers\Seller;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\Request;
use Inertia\Inertia;

class OrderController extends Controller
{
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

        $order->load('user', 'items.product');

        return Inertia::render('Seller/Orders/Show', [
            'order' => $order,
        ]);
    }

    public function updateStatus(Request $request, Order $order)
    {
        $this->authorize('updateStatus', $order);

        $validated = $request->validate([
            'status' => 'required|in:processing,shipped,completed',
        ]);

        $order->update($validated);

        return back()->with('success', 'Order status updated.');
    }
}
