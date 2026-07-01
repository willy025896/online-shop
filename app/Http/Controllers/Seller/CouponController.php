<?php

namespace App\Http\Controllers\Seller;

use App\Http\Controllers\Controller;
use App\Models\Coupon;
use App\Services\CouponService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class CouponController extends Controller
{
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
        $validated = $this->validated($request);
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

        $coupon->update($this->validated($request, $coupon));

        return back()->with('success', 'Coupon updated.');
    }

    public function destroy(Coupon $coupon)
    {
        $this->authorize('delete', $coupon);

        $coupon->delete();

        return redirect()->route('seller.coupons.index')
            ->with('success', 'Coupon deleted.');
    }

    private function validated(Request $request, ?Coupon $coupon = null): array
    {
        $validated = $request->validate([
            'code' => [
                'required', 'string', 'max:50',
                Rule::unique('coupons', 'code')->whereNull('deleted_at')->ignore($coupon?->id),
            ],
            'type' => ['required', Rule::in([Coupon::TYPE_PERCENTAGE, Coupon::TYPE_FIXED])],
            'value' => [
                'required', 'numeric', 'min:0.01',
                // A percentage discount can never exceed 100%.
                Rule::when($request->input('type') === Coupon::TYPE_PERCENTAGE, ['max:100']),
            ],
            'min_spend' => ['nullable', 'numeric', 'min:0'],
            'max_discount' => ['nullable', 'numeric', 'min:0'],
            'usage_limit' => ['nullable', 'integer', 'min:1'],
            'per_user_limit' => ['nullable', 'integer', 'min:1'],
            'starts_at' => ['nullable', 'date'],
            'expires_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
            'is_active' => ['boolean'],
        ]);

        $validated['code'] = CouponService::normalize($validated['code']);
        $validated['min_spend'] = $validated['min_spend'] ?? 0;

        return $validated;
    }
}
