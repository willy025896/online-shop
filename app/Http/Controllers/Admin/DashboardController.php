<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Product;
use App\Models\Shop;
use App\Models\User;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function index()
    {
        return Inertia::render('Admin/Dashboard', [
            'stats' => [
                'total_users' => User::count(),
                'total_shops' => Shop::count(),
                'pending_shops' => Shop::where('status', Shop::STATUS_PENDING)->count(),
                'total_products' => Product::count(),
                'total_orders' => Order::count(),
                'total_revenue' => Order::where('status', '!=', Order::STATUS_CANCELLED)->sum('total'),
            ],
        ]);
    }
}
