<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Shop;
use App\Notifications\ShopStatusChangedNotification;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
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
            'status' => ['required', Rule::in([Shop::STATUS_APPROVED, Shop::STATUS_SUSPENDED])],
        ]);

        $previousStatus = $shop->status;

        $shop->update([
            'status' => $validated['status'],
            'approved_at' => $validated['status'] === Shop::STATUS_APPROVED ? now() : $shop->approved_at,
        ]);

        if ($previousStatus !== $shop->status) {
            $shop->loadMissing('user');
            $shop->user?->notify(new ShopStatusChangedNotification($shop));
        }

        return back()->with('success', 'Shop status updated.');
    }
}
