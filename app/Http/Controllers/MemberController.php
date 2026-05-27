<?php

namespace App\Http\Controllers;

use App\Models\Order;
use Inertia\Inertia;

class MemberController extends Controller
{
    public function index()
    {
        $user = auth()->user();

        $stats = [
            'total' => $user->orders()->count(),
            'pending' => $user->orders()->where('status', Order::STATUS_PENDING)->count(),
            'in_progress' => $user->orders()->whereIn('status', [Order::STATUS_PAID, Order::STATUS_PROCESSING, Order::STATUS_SHIPPED])->count(),
            'completed' => $user->orders()->where('status', Order::STATUS_COMPLETED)->count(),
        ];

        $recentOrders = $user->orders()->with('shop', 'items')->latest()->limit(5)->get();

        return Inertia::render('Members', [
            'stats' => $stats,
            'recentOrders' => $recentOrders,
        ]);
    }
}
