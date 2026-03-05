<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Shop;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ShopController extends Controller
{
    public function index()
    {
        $shops = Shop::with('user')
            ->latest()
            ->paginate(15);

        return Inertia::render('Admin/Shops/Index', [
            'shops' => $shops,
        ]);
    }

    public function updateStatus(Request $request, Shop $shop)
    {
        $validated = $request->validate([
            'status' => 'required|in:approved,suspended',
        ]);

        $shop->update([
            'status' => $validated['status'],
            'approved_at' => $validated['status'] === 'approved' ? now() : $shop->approved_at,
        ]);

        return back()->with('success', 'Shop status updated.');
    }
}
