<?php

namespace App\Http\Controllers;

use Inertia\Inertia;

class MemberController extends Controller
{
    public function index()
    {
        $user = auth()->user();

        $stats = [
            'total' => $user->orders()->count(),
            'pending' => $user->orders()->where('status', 'pending')->count(),
            'in_progress' => $user->orders()->whereIn('status', ['paid', 'processing', 'shipped'])->count(),
            'completed' => $user->orders()->where('status', 'completed')->count(),
        ];

        $recentOrders = $user->orders()->with('shop', 'items')->latest()->limit(5)->get();

        return Inertia::render('Members', [
            'stats' => $stats,
            'recentOrders' => $recentOrders,
        ]);
    }
}
