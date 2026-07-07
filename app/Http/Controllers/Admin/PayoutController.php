<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Payout;
use App\Services\AdminAuditLogger;
use App\Services\PayoutService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class PayoutController extends Controller
{
    public function __construct(
        private PayoutService $payoutService,
        private AdminAuditLogger $auditLogger,
    ) {}

    public function index()
    {
        $payouts = Payout::with('shop:id,name')
            ->latest('paid_at')
            ->paginate(20);

        return Inertia::render('Admin/Payouts/Index', [
            'payouts' => $payouts,
            'pendingPreview' => $this->payoutService->pendingPreview(),
        ]);
    }

    public function run(Request $request)
    {
        $payouts = $this->payoutService->generateForAllShops();

        foreach ($payouts as $payout) {
            $this->auditLogger->log($request->user(), 'payout.generated', $payout, [
                'shop_id' => $payout->shop_id,
                'net_amount' => (float) $payout->net_amount,
            ]);
        }

        return back()->with('success', $payouts->isEmpty()
            ? 'No eligible orders to pay out.'
            : "Generated {$payouts->count()} payout(s).");
    }
}
