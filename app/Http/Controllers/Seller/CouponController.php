<?php

namespace App\Http\Controllers\Seller;

use App\Http\Controllers\Concerns\ValidatesCouponRequest;
use App\Http\Controllers\Controller;
use App\Models\Coupon;
use Illuminate\Http\Request;
use Inertia\Inertia;

class CouponController extends Controller
{
    use ValidatesCouponRequest;

    public function index()
    {
        $coupons = auth()->user()->shop->coupons()
            ->withCount('redemptions')
            ->latest()
            ->paginate(10);

        return Inertia::render('Seller/Coupons/Index', [
            'coupons' => $coupons,
        ]);
    }

    public function create()
    {
        return Inertia::render('Seller/Coupons/Create');
    }

    public function store(Request $request)
    {
        $validated = $this->validatedCouponFields($request);
        $validated['shop_id'] = auth()->user()->shop->id;

        Coupon::create($validated);

        return redirect()->route('seller.coupons.index')
            ->with('success', 'Coupon created.');
    }

    public function edit(Coupon $coupon)
    {
        $this->authorize('update', $coupon);

        return Inertia::render('Seller/Coupons/Edit', [
            'coupon' => $coupon,
        ]);
    }

    public function update(Request $request, Coupon $coupon)
    {
        $this->authorize('update', $coupon);

        $coupon->update($this->validatedCouponFields($request, $coupon));

        return back()->with('success', 'Coupon updated.');
    }

    public function destroy(Coupon $coupon)
    {
        $this->authorize('delete', $coupon);

        $coupon->delete();

        return redirect()->route('seller.coupons.index')
            ->with('success', 'Coupon deleted.');
    }
}
