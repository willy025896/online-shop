<?php

namespace App\Http\Controllers\Seller;

use App\Http\Controllers\Controller;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function index()
    {
        $shop = auth()->user()->shop;

        $stats = [
            'total_products' => $shop->products()->count(),
            'active_products' => $shop->products()->active()->count(),
            'pending_orders' => $shop->orders()->where('status', 'pending')->count(),
            'total_revenue' => $shop->orders()->where('status', '!=', 'cancelled')->sum('total'),
        ];

        $recentOrders = $shop->orders()
            ->with('items')
            ->latest()
            ->limit(5)
            ->get();

        return Inertia::render('Seller/Dashboard', [
            'shop' => $shop,
            'stats' => $stats,
            'recentOrders' => $recentOrders,
        ]);
    }
}
