<?php

namespace App\Services;

use App\Models\Order;

/**
 * Business-logic layer for electronic invoice (B2C 電子發票) issuance/void/
 * allowance — decides *whether* an order's invoice state should change;
 * EcpayInvoiceGateway only knows *how* to talk to ECPay. See ADR-019.
 *
 * Every method is idempotent by checking orders.invoice_status first, so
 * callers (PaymentService, OrderService) can call these unconditionally at
 * their existing hook points without duplicating the guard.
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

        $result = $this->gateway->issue($order);

        $order->update([
            'invoice_number' => $result['invoice_no'],
            'invoice_random_code' => $result['random_number'],
            'invoice_issued_at' => $result['invoice_date'],
            'invoice_status' => Order::INVOICE_ISSUED,
        ]);
    }

    public function voidForOrder(Order $order, string $reason): void
    {
        if ($order->invoice_status !== Order::INVOICE_ISSUED) {
            return;
        }

        $this->gateway->invalidate($order, $reason);

        $order->update(['invoice_status' => Order::INVOICE_VOIDED]);
    }

    /**
     * @param  array<int, array{name: string, count: int|float, unit_price: float}>  $items
     */
    public function allowanceForOrder(Order $order, float $amount, array $items): void
    {
        if (! in_array($order->invoice_status, [Order::INVOICE_ISSUED, Order::INVOICE_ALLOWANCED], true)) {
            return;
        }

        $this->gateway->allowance($order, $amount, $items);

        $order->update(['invoice_status' => Order::INVOICE_ALLOWANCED]);
    }
}
