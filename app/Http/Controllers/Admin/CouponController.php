<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Concerns\ValidatesCouponRequest;
use App\Http\Controllers\Controller;
use App\Models\Coupon;
use App\Services\AdminAuditLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Inertia\Inertia;

/**
 * Platform-wide coupons only (shop_id === null). Seller-owned coupons stay
 * on Seller\CouponController — this controller intentionally 404s on any
 * shop-scoped coupon to keep the two management paths from overlapping.
 */
class CouponController extends Controller
{
    use ValidatesCouponRequest;

    public function __construct(private AdminAuditLogger $auditLogger) {}

    public function index()
    {
        $coupons = Coupon::whereNull('shop_id')
            ->withCount('redemptions')
            ->latest()
            ->paginate(10);

        return Inertia::render('Admin/Coupons/Index', [
            'coupons' => $coupons,
        ]);
    }

    public function create()
    {
        return Inertia::render('Admin/Coupons/Create');
    }

    public function store(Request $request)
    {
        $validated = $this->validatedCouponFields($request);
        $validated['shop_id'] = null;

        $coupon = Coupon::create($validated);

        $this->auditLogger->log($request->user(), 'coupon.created', $coupon, [
            'code' => $coupon->code,
        ]);

        return redirect()->route('admin.coupons.index')
            ->with('success', 'Coupon created.');
    }

    public function edit(Coupon $coupon)
    {
        abort_unless($coupon->shop_id === null, 404);

        $this->authorize('update', $coupon);

        return Inertia::render('Admin/Coupons/Edit', [
            'coupon' => $coupon,
        ]);
    }

    public function update(Request $request, Coupon $coupon)
    {
        abort_unless($coupon->shop_id === null, 404);

        $this->authorize('update', $coupon);

        $coupon->update($this->validatedCouponFields($request, $coupon));

        $this->auditLogger->log($request->user(), 'coupon.updated', $coupon, Arr::except($coupon->getChanges(), 'updated_at'));

        return back()->with('success', 'Coupon updated.');
    }

    public function destroy(Request $request, Coupon $coupon)
    {
        abort_unless($coupon->shop_id === null, 404);

        $this->authorize('delete', $coupon);

        $code = $coupon->code;
        $coupon->delete();

        $this->auditLogger->log($request->user(), 'coupon.deleted', $coupon, [
            'code' => $code,
        ]);

        return redirect()->route('admin.coupons.index')
            ->with('success', 'Coupon deleted.');
    }
}
