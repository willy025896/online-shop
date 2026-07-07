<?php

namespace App\Http\Controllers\Seller;

use App\Http\Controllers\Controller;
use Inertia\Inertia;

class PayoutController extends Controller
{
    public function index()
    {
        $shop = auth()->user()->shop;

        $payouts = $shop->payouts()
            ->withCount('items')
            ->latest('paid_at')
            ->paginate(15);

        return Inertia::render('Seller/Payouts/Index', [
            'payouts' => $payouts,
        ]);
    }
}
