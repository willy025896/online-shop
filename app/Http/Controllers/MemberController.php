<?php

namespace App\Http\Controllers;

use Inertia\Inertia;

class MemberController extends Controller
{
    public function index()
    {
        $user = auth()->user();

        $orders = $user->orders()->with('shop', 'items')->latest();

        $stats = [
            'total'      => (clone $orders)->count(),
            'pending'    => (clone $orders)->where('status', 'pending')->count(),
            'in_progress'=> (clone $orders)->whereIn('status', ['processing', 'shipped'])->count(),
            'completed'  => (clone $orders)->where('status', 'completed')->count(),
        ];

        $recentOrders = (clone $orders)->limit(5)->get();

        return Inertia::render('Members', [
            'stats'        => $stats,
            'recentOrders' => $recentOrders,
        ]);
    }
}
