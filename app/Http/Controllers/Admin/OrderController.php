<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Inertia\Inertia;

class OrderController extends Controller
{
    public function index()
    {
        $orders = Order::with('user', 'shop', 'items')
            ->latest()
            ->paginate(15);

        return Inertia::render('Admin/Orders/Index', [
            'orders' => $orders,
        ]);
    }

    public function show(Order $order)
    {
        $this->authorize('view', $order);

        $order->load([
            'user:id,name,email',
            'shop:id,name',
            'items.product:id,name',
            'cancellations.responder:id,name',
            'statusLogs' => fn ($q) => $q->latest('created_at'),
            'statusLogs.changedBy:id,name,role',
        ]);

        return Inertia::render('Admin/Orders/Show', [
            'order' => $order,
        ]);
    }
}
