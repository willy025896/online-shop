<?php

namespace App\Services;

use App\Models\Order;
use App\Notifications\OrderPaidNotification;
use Illuminate\Support\Facades\DB;

class PaymentService
{
    public function __construct(
        private EcpayGateway $gateway,
        private InvoiceService $invoiceService,
    ) {}

    /**
     * Builds the ECPay AioCheckOut redirect data (target URL + signed form
     * fields) for OrderController::pay() to render as an auto-submitting form.
     * The buyer's browser POSTs this straight to ECPay's hosted checkout page.
     */
    public function checkoutRedirectData(Order $order): array
    {
        return [
            'url' => $this->gateway->checkoutUrl(),
            'fields' => $this->gateway->checkoutFormFields(
                $order,
                route('api.payments.ecpay.notify'),
                route('orders.pay.return', $order)
            ),
        ];
    }

    /**
     * Handles ECPay's server-to-server payment notify callback — the only
     * source of truth for "did this order actually get paid". Verifies the
     * CheckMacValue signature and RtnCode before marking anything paid, and
     * is idempotent (ECPay retries notify until it gets "1|OK", so a replay
     * for an already-paid order is a no-op success, not a re-notify).
     */
    public function handleGatewayNotification(array $payload): bool
    {
        $merchantTradeNo = $payload['MerchantTradeNo'] ?? null;
        $orderId = is_string($merchantTradeNo) ? EcpayGateway::tradeNoToOrderId($merchantTradeNo) : null;

        // verify()/RtnCode only depend on $payload, not on the order row, so
        // both are checked before ever touching the database.
        if ($orderId === null || ! $this->gateway->verify($payload)) {
            return false;
        }

        if ((string) ($payload['RtnCode'] ?? '') !== '1') {
            return false;
        }

        $tradeNo = $payload['TradeNo'] ?? null;
        $tradeNo = is_string($tradeNo) ? $tradeNo : null;

        // Lock the row for the whole decide-and-mutate step — ECPay retries
        // this callback until it gets "1|OK", so two near-simultaneous
        // retries must not both pass the isPaid() idempotency check. This is
        // also the only fetch of the order — nothing above needs it loaded.
        // The closure returns [result, order-just-marked-paid-or-null] so the
        // e-invoice call below can run after the transaction commits without
        // a captured-by-reference variable.
        [$result, $paidOrder] = DB::transaction(function () use ($orderId, $tradeNo) {
            $locked = Order::lockForUpdate()->find($orderId);

            if ($locked === null) {
                return [false, null];
            }

            if ($locked->isPaid()) {
                return [true, null];
            }

            if (! $locked->isPending()) {
                // The order left the payable state (e.g. the buyer cancelled
                // it while this payment was in flight at ECPay) before this
                // notify arrived. Resurrecting it to `paid` would undo the
                // stock restore / coupon release finalizeCancellation already
                // did. Refund the payment ECPay just confirmed instead of
                // marking it paid — using the full charged amount (shipping
                // included), since nothing was fulfilled.
                if ($tradeNo !== null) {
                    $locked->update(['gateway_trade_no' => $tradeNo]);
                    $this->refund($locked, (float) $locked->total);
                }

                return [true, null];
            }

            $this->markAsPaidWithoutInvoice($locked, $tradeNo);

            return [true, $locked];
        });

        // Issue the e-invoice only after the transaction above has committed
        // and the order row lock released — ECPay's invoice API is a real
        // outbound HTTP call, and must never extend how long this
        // webhook-triggered row lock is held (see ADR-019).
        if ($paidOrder !== null) {
            $this->invoiceService->issueForOrder($paidOrder);
        }

        return $result;
    }

    public function markAsPaid(Order $order, ?string $gatewayTradeNo = null): bool
    {
        $this->markAsPaidWithoutInvoice($order, $gatewayTradeNo);

        // Best-effort: e-invoice issuance is a side effect of "payment
        // confirmed", not part of it — InvoiceService never rolls back an
        // already real ECPay payment over a transient invoice-API failure
        // (it catches and logs internally). See ADR-019.
        $this->invoiceService->issueForOrder($order);

        return true;
    }

    /**
     * The actual "mark paid" DB mutation, split out so handleGatewayNotification
     * can call it from inside its locked transaction while deferring the
     * (potentially slow) e-invoice HTTP call until after that lock releases.
     * Every caller of this method must follow it with `issueForOrder()` once
     * its own transaction/lock has released — see both call sites above.
     */
    private function markAsPaidWithoutInvoice(Order $order, ?string $gatewayTradeNo): void
    {
        // Wrap in a transaction so the status-log insert fired by the Order
        // `updated` event commits atomically with the status change.
        DB::transaction(function () use ($order, $gatewayTradeNo) {
            $order->update([
                'status' => Order::STATUS_PAID,
                'paid_at' => now(),
                'gateway_trade_no' => $gatewayTradeNo ?? $order->gateway_trade_no,
            ]);

            $order->loadMissing('shop.user');
            $order->shop?->user?->notify(new OrderPaidNotification($order));
        });
    }

    /**
     * Refunds via ECPay's credit card refund API. Does not change
     * `orders.status` (a refunded order stays `completed`); it only records
     * how much has been refunded so far. MUST run inside the caller's
     * DB::transaction (OrderService::finalizeReturn / finalizeCancellation) —
     * EcpayGateway::refund() throws EcpayException on any gateway/network
     * failure, which rolls back the whole transaction rather than leaving a
     * half-applied refund.
     */
    public function refund(Order $order, float $amount): bool
    {
        // This call is only safe to roll back (see the docblock above) inside
        // a transaction — enforce it rather than relying solely on callers
        // remembering the comment.
        throw_unless(DB::transactionLevel() > 0, new \LogicException(
            'PaymentService::refund() must be called inside a DB::transaction().'
        ));

        // ECPay only accepts whole-number TWD amounts (EcpayGateway::refund()
        // rounds internally for the request payload) — round once here so the
        // local ledger records the exact amount actually refunded at the
        // gateway, instead of drifting from it by the rounding remainder.
        $roundedAmount = round($amount);

        $this->gateway->refund($order, $roundedAmount);

        $order->increment('refunded_amount', $roundedAmount);

        return true;
    }
}
