<?php

namespace App\Services;

use App\Exceptions\EinvoiceException;
use App\Models\Order;
use Illuminate\Support\Facades\Log;

/**
 * Business-logic layer for electronic invoice (B2C 電子發票) issuance/void/
 * allowance — decides *whether* an order's invoice state should change;
 * EcpayInvoiceGateway only knows *how* to talk to ECPay. See ADR-019.
 *
 * Every method is idempotent by checking orders.invoice_status first, and
 * every method catches its own gateway failures and logs them rather than
 * throwing — invoice issuance/void/allowance is always a best-effort side
 * effect of a real, already-happened payment/refund and must never roll one
 * back. Callers (PaymentService, OrderService) can call these unconditionally
 * at their existing hook points without duplicating the guard or the
 * try/catch.
 */
class InvoiceService
{
    public function __construct(
        private EcpayInvoiceGateway $gateway,
    ) {}

    public function issueForOrder(Order $order): void
    {
        if ($order->invoice_status !== null) {
            return;
        }

        $this->bestEffort($order, 'Failed to issue e-invoice for order', function () use ($order) {
            $result = $this->gateway->issue($order);

            if (empty($result['invoice_no'])) {
                // ECPay reported success but returned no invoice number —
                // don't persist a half-valid ISSUED state that the
                // idempotency guard above would then block from ever
                // retrying.
                throw new EinvoiceException('missing_invoice_number', 'ECPay accepted the issue request but returned no invoice number.');
            }

            $order->update([
                'invoice_number' => $result['invoice_no'],
                'invoice_random_code' => $result['random_number'],
                'invoice_issued_at' => $result['invoice_date'],
                'invoice_status' => Order::INVOICE_ISSUED,
            ]);
        });
    }

    public function voidForOrder(Order $order, string $reason): void
    {
        if ($order->invoice_status !== Order::INVOICE_ISSUED) {
            return;
        }

        $this->bestEffort($order, 'Failed to void e-invoice for order', function () use ($order, $reason) {
            $this->gateway->invalidate($order, $reason);

            $order->update(['invoice_status' => Order::INVOICE_VOIDED]);
        });
    }

    /**
     * @param  array<int, array{name: string, count: int|float, unit_price: float}>  $items
     */
    public function allowanceForOrder(Order $order, float $amount, array $items): void
    {
        if (! in_array($order->invoice_status, [Order::INVOICE_ISSUED, Order::INVOICE_ALLOWANCED], true)) {
            return;
        }

        $this->bestEffort($order, 'Failed to allowance e-invoice for order', function () use ($order, $amount, $items) {
            $this->gateway->allowance($order, $amount, $items);

            $order->update(['invoice_status' => Order::INVOICE_ALLOWANCED]);
        });
    }

    /**
     * Cancellation-specific policy: void the invoice if it's still within the
     * same calendar month it was issued (a rough stand-in for ECPay's actual
     * "not yet reported to the tax authority" cutoff — deliberately
     * simplified for this side project, see ADR-019); otherwise it's already
     * past the point a straight void is allowed, so issue a full allowance
     * instead. Centralized here (rather than in each caller) so any future
     * cancellation entry point gets the same rule for free.
     *
     * @param  array<int, array{name: string, count: int|float, unit_price: float}>  $items
     */
    public function voidOrAllowanceForCancellation(Order $order, float $refundAmount, array $items, string $voidReason): void
    {
        if ($order->invoice_issued_at?->isSameMonth(now())) {
            $this->voidForOrder($order, $voidReason);
        } else {
            $this->allowanceForOrder($order, $refundAmount, $items);
        }
    }

    /**
     * Runs $action, logging (rather than rethrowing) any failure — the
     * shared best-effort shape behind all three public methods above. See the
     * class docblock for why these must never throw.
     */
    private function bestEffort(Order $order, string $failureLogMessage, callable $action): void
    {
        try {
            $action();
        } catch (\Throwable $e) {
            Log::warning($failureLogMessage, [
                'order_id' => $order->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
