<?php

use App\Exceptions\EinvoiceException;
use App\Models\Order;
use App\Models\OrderReturn;
use App\Models\Product;
use App\Models\Shop;
use App\Models\User;
use App\Services\EcpayInvoiceGateway;
use App\Services\InvoiceService;
use App\Services\OrderService;
use App\Services\PaymentService;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;

function einvoiceEncrypt(array $data): string
{
    $urlEncoded = urlencode(json_encode($data, JSON_UNESCAPED_UNICODE));
    $cipher = openssl_encrypt($urlEncoded, 'AES-128-CBC', config('ecpay_invoice.hash_key'), OPENSSL_RAW_DATA, config('ecpay_invoice.hash_iv'));

    return base64_encode($cipher);
}

function einvoiceDecryptRequestData(array $sentJson): array
{
    $plain = openssl_decrypt(base64_decode($sentJson['Data']), 'AES-128-CBC', config('ecpay_invoice.hash_key'), OPENSSL_RAW_DATA, config('ecpay_invoice.hash_iv'));

    return json_decode(urldecode($plain), true);
}

function fakeEinvoiceIssue(array $overrides = []): void
{
    $result = array_merge([
        'RtnCode' => '1',
        'RtnMsg' => 'Success',
        'InvoiceNo' => 'AB12345678',
        'RandomNumber' => '1234',
        'InvoiceDate' => '2026-07-09 12:00:00',
    ], $overrides);

    Http::fake([
        '*/B2CInvoice/Issue' => Http::response(['Data' => einvoiceEncrypt($result)]),
    ]);
}

function fakeEinvoiceInvalid(array $overrides = []): void
{
    $result = array_merge(['RtnCode' => '1', 'RtnMsg' => 'Success'], $overrides);

    Http::fake([
        '*/B2CInvoice/Invalid' => Http::response(['Data' => einvoiceEncrypt($result)]),
    ]);
}

function fakeEinvoiceAllowance(array $overrides = []): void
{
    $result = array_merge(['RtnCode' => '1', 'RtnMsg' => 'Success', 'IA_Allow_No' => 'AL12345678'], $overrides);

    Http::fake([
        '*/B2CInvoice/Allowance' => Http::response(['Data' => einvoiceEncrypt($result)]),
    ]);
}

function fakeEcpayInvoicePaymentRefund(): void
{
    Http::fake([
        'payment-stage.ecpay.com.tw/*' => Http::response('RtnCode=1&RtnMsg=Succeeded'),
    ]);
}

function makeEinvoiceOrder(array $orderState = [], int $qty = 2, float $unitPrice = 100): Order
{
    $seller = User::factory()->seller()->create();
    $shop = Shop::factory()->create(['user_id' => $seller->id]);
    $product = Product::factory()->create(['shop_id' => $shop->id, 'stock' => 5]);
    $buyer = User::factory()->create();
    $order = Order::factory()->create(array_merge([
        'user_id' => $buyer->id,
        'shop_id' => $shop->id,
        'subtotal' => $qty * $unitPrice,
        'discount' => 0,
        'shipping_fee' => 0,
        'total' => $qty * $unitPrice,
    ], $orderState));
    $order->items()->create([
        'product_id' => $product->id,
        'product_name' => $product->name,
        'quantity' => $qty,
        'unit_price' => $unitPrice,
        'subtotal' => $unitPrice * $qty,
    ]);

    return $order->fresh();
}

// ---- EcpayInvoiceGateway ----

test('issue sends an AES-encrypted payload and parses a successful response', function () {
    $order = makeEinvoiceOrder(qty: 2, unitPrice: 150);
    fakeEinvoiceIssue();

    $result = app(EcpayInvoiceGateway::class)->issue($order);

    expect($result['invoice_no'])->toBe('AB12345678');
    expect($result['random_number'])->toBe('1234');

    Http::assertSent(function ($request) use ($order) {
        $data = einvoiceDecryptRequestData($request->data());

        return $data['RelateNumber'] === 'INV'.$order->id
            && $data['SalesAmount'] === 300
            && count($data['Items']) === 1;
    });
});

test('issue throws EinvoiceException when the gateway rejects the request', function () {
    $order = makeEinvoiceOrder();
    fakeEinvoiceIssue(['RtnCode' => '10200052', 'RtnMsg' => 'Failed']);

    expect(fn () => app(EcpayInvoiceGateway::class)->issue($order))->toThrow(EinvoiceException::class);
});

test('invalidate throws when the order has no recorded invoice number', function () {
    Http::fake();
    $order = makeEinvoiceOrder(['invoice_number' => null, 'invoice_issued_at' => null]);

    expect(fn () => app(EcpayInvoiceGateway::class)->invalidate($order, 'reason'))->toThrow(EinvoiceException::class);
});

test('invalidate sends the invoice number and reason for a recorded invoice', function () {
    fakeEinvoiceInvalid();
    $order = makeEinvoiceOrder([
        'invoice_number' => 'AB12345678',
        'invoice_issued_at' => now(),
        'invoice_status' => Order::INVOICE_ISSUED,
    ]);

    app(EcpayInvoiceGateway::class)->invalidate($order, 'Order cancelled');

    Http::assertSent(function ($request) use ($order) {
        $data = einvoiceDecryptRequestData($request->data());

        return $data['InvoiceNo'] === $order->invoice_number
            && $data['Reason'] === 'Order cancelled';
    });
});

test('allowance throws when the order has no recorded invoice number', function () {
    Http::fake();
    $order = makeEinvoiceOrder(['invoice_number' => null, 'invoice_issued_at' => null]);

    expect(fn () => app(EcpayInvoiceGateway::class)->allowance($order, 50.0, [
        ['name' => 'Widget', 'count' => 1, 'unit_price' => 50.0],
    ]))->toThrow(EinvoiceException::class);
});

test('allowance builds item amounts from the given items and succeeds', function () {
    fakeEinvoiceAllowance();
    $order = makeEinvoiceOrder([
        'invoice_number' => 'AB12345678',
        'invoice_issued_at' => now(),
        'invoice_status' => Order::INVOICE_ISSUED,
    ]);

    $result = app(EcpayInvoiceGateway::class)->allowance($order, 200.0, [
        ['name' => 'Widget', 'count' => 2, 'unit_price' => 100.0],
    ]);

    expect($result['allowance_no'])->toBe('AL12345678');
    Http::assertSent(function ($request) {
        $data = einvoiceDecryptRequestData($request->data());

        return (float) $data['Items'][0]['ItemAmount'] === 200.0 && (int) $data['AllowanceAmount'] === 200;
    });
});

test('allowance adds a reconciliation line when the item total exceeds a coupon-discounted amount', function () {
    fakeEinvoiceAllowance();
    $order = makeEinvoiceOrder([
        'invoice_number' => 'AB12345678',
        'invoice_issued_at' => now(),
        'invoice_status' => Order::INVOICE_ISSUED,
    ]);

    // Items are always priced at their full, undiscounted unit_price — the
    // 180.0 allowance amount simulates a coupon-discounted refund that is
    // smaller than the raw item total (200.0).
    app(EcpayInvoiceGateway::class)->allowance($order, 180.0, [
        ['name' => 'Widget', 'count' => 2, 'unit_price' => 100.0],
    ]);

    Http::assertSent(function ($request) {
        $data = einvoiceDecryptRequestData($request->data());
        $itemSum = round(array_sum(array_column($data['Items'], 'ItemAmount')), 2);

        return count($data['Items']) === 2
            && (float) $itemSum === 180.0
            && (int) $data['AllowanceAmount'] === 180;
    });
});

test('issue reconciles a fractional total (from a percentage coupon) via an adjustment line item', function () {
    fakeEinvoiceIssue();
    $order = makeEinvoiceOrder([
        'discount' => 149.85,
        'shipping_fee' => 60,
        'total' => 909.15,
    ], qty: 1, unitPrice: 999);

    app(EcpayInvoiceGateway::class)->issue($order);

    Http::assertSent(function ($request) {
        $data = einvoiceDecryptRequestData($request->data());
        $itemSum = round(array_sum(array_column($data['Items'], 'ItemAmount')), 2);

        return (float) $itemSum === (float) $data['SalesAmount'];
    });
});

test('a malformed response that fails to decrypt throws a distinct decrypt_failed reason', function () {
    Http::fake([
        '*/B2CInvoice/Issue' => Http::response(['Data' => base64_encode('not valid ciphertext')]),
    ]);
    $order = makeEinvoiceOrder();

    try {
        app(EcpayInvoiceGateway::class)->issue($order);
        expect(false)->toBeTrue('Expected EinvoiceException to be thrown');
    } catch (EinvoiceException $e) {
        expect($e->reason)->toBe('decrypt_failed');
    }
});

// ---- InvoiceService ----

test('issueForOrder issues and persists invoice fields when not yet issued', function () {
    fakeEinvoiceIssue();
    $order = makeEinvoiceOrder(['invoice_status' => null]);

    app(InvoiceService::class)->issueForOrder($order);

    $order->refresh();
    expect($order->invoice_status)->toBe(Order::INVOICE_ISSUED);
    expect($order->invoice_number)->toBe('AB12345678');
    expect($order->invoice_random_code)->toBe('1234');
    expect($order->invoice_issued_at)->not->toBeNull();
});

test('issueForOrder does not persist ISSUED when the gateway omits the invoice number', function () {
    fakeEinvoiceIssue(['InvoiceNo' => null]);
    $order = makeEinvoiceOrder(['invoice_status' => null]);

    app(InvoiceService::class)->issueForOrder($order);

    $order->refresh();
    expect($order->invoice_status)->toBeNull();
    expect($order->invoice_number)->toBeNull();
});

test('issueForOrder is idempotent and does not call the gateway again once issued', function () {
    $order = makeEinvoiceOrder([
        'invoice_status' => Order::INVOICE_ISSUED,
        'invoice_number' => 'ALREADY123',
        'invoice_issued_at' => now(),
    ]);
    Http::fake();

    app(InvoiceService::class)->issueForOrder($order);

    Http::assertNothingSent();
    expect($order->fresh()->invoice_number)->toBe('ALREADY123');
});

test('voidForOrder is a no-op unless the invoice is currently issued', function () {
    $order = makeEinvoiceOrder(['invoice_status' => null]);
    Http::fake();

    app(InvoiceService::class)->voidForOrder($order, 'reason');

    Http::assertNothingSent();
    expect($order->fresh()->invoice_status)->toBeNull();
});

test('voidForOrder voids an issued invoice', function () {
    fakeEinvoiceInvalid();
    $order = makeEinvoiceOrder([
        'invoice_status' => Order::INVOICE_ISSUED,
        'invoice_number' => 'AB12345678',
        'invoice_issued_at' => now(),
    ]);

    app(InvoiceService::class)->voidForOrder($order, 'Order cancelled');

    expect($order->fresh()->invoice_status)->toBe(Order::INVOICE_VOIDED);
});

test('allowanceForOrder is a no-op unless the invoice has been issued', function () {
    $order = makeEinvoiceOrder(['invoice_status' => null]);
    Http::fake();

    app(InvoiceService::class)->allowanceForOrder($order, 50.0, [
        ['name' => 'Widget', 'count' => 1, 'unit_price' => 50.0],
    ]);

    Http::assertNothingSent();
    expect($order->fresh()->invoice_status)->toBeNull();
});

test('allowanceForOrder moves an issued invoice to allowanced', function () {
    fakeEinvoiceAllowance();
    $order = makeEinvoiceOrder([
        'invoice_status' => Order::INVOICE_ISSUED,
        'invoice_number' => 'AB12345678',
        'invoice_issued_at' => now(),
    ]);

    app(InvoiceService::class)->allowanceForOrder($order, 100.0, [
        ['name' => 'Widget', 'count' => 1, 'unit_price' => 100.0],
    ]);

    expect($order->fresh()->invoice_status)->toBe(Order::INVOICE_ALLOWANCED);
});

test('allowanceForOrder can be issued again on an already-allowanced invoice for a second partial return', function () {
    fakeEinvoiceAllowance();
    $order = makeEinvoiceOrder([
        'invoice_status' => Order::INVOICE_ALLOWANCED,
        'invoice_number' => 'AB12345678',
        'invoice_issued_at' => now(),
    ]);

    app(InvoiceService::class)->allowanceForOrder($order, 50.0, [
        ['name' => 'Widget', 'count' => 1, 'unit_price' => 50.0],
    ]);

    Http::assertSentCount(1);
    expect($order->fresh()->invoice_status)->toBe(Order::INVOICE_ALLOWANCED);
});

// ---- PaymentService::markAsPaid issues the invoice as a side effect ----

test('markAsPaid issues an e-invoice for the order', function () {
    Notification::fake();
    fakeEinvoiceIssue();

    $order = makeEinvoiceOrder(['status' => Order::STATUS_PENDING]);

    app(PaymentService::class)->markAsPaid($order, 'TRADE1');

    $order->refresh();
    expect($order->status)->toBe(Order::STATUS_PAID);
    expect($order->invoice_status)->toBe(Order::INVOICE_ISSUED);
});

test('markAsPaid still marks the order paid even when e-invoice issuance fails', function () {
    Notification::fake();
    fakeEinvoiceIssue(['RtnCode' => '10200052', 'RtnMsg' => 'Failed']);

    $order = makeEinvoiceOrder(['status' => Order::STATUS_PENDING]);

    app(PaymentService::class)->markAsPaid($order, 'TRADE2');

    $order->refresh();
    expect($order->status)->toBe(Order::STATUS_PAID);
    expect($order->invoice_status)->toBeNull();
});

test('the ECPay payment notify webhook still issues the e-invoice after the lock-duration refactor', function () {
    Notification::fake();
    fakeEinvoiceIssue();

    $order = makeEinvoiceOrder(['status' => Order::STATUS_PENDING]);

    $gateway = app(\App\Services\EcpayGateway::class);
    $payload = [
        'MerchantID' => config('ecpay.merchant_id'),
        'MerchantTradeNo' => 'ORD'.$order->id,
        'RtnCode' => 1,
        'TradeNo' => 'ECPAYINV1',
    ];
    $payload['CheckMacValue'] = $gateway->generateCheckMacValue($payload);

    $this->post(route('api.payments.ecpay.notify'), $payload)->assertSeeText('1|OK');

    $order->refresh();
    expect($order->status)->toBe(Order::STATUS_PAID);
    expect($order->invoice_status)->toBe(Order::INVOICE_ISSUED);
});

// ---- Cancellation: void within the same month, allowance otherwise, best-effort ----

test('cancelling a paid order voids the invoice when it was issued in the same calendar month', function () {
    fakeEcpayInvoicePaymentRefund();
    fakeEinvoiceInvalid();

    $order = makeEinvoiceOrder([
        'status' => Order::STATUS_PAID,
        'paid_at' => now(),
        'gateway_trade_no' => 'TRADE3',
        'invoice_status' => Order::INVOICE_ISSUED,
        'invoice_number' => 'AB12345678',
        'invoice_issued_at' => now(),
    ]);

    app(OrderService::class)->directCancelByBuyer($order, 'changed my mind');

    $order->refresh();
    expect($order->status)->toBe(Order::STATUS_CANCELLED);
    expect($order->invoice_status)->toBe(Order::INVOICE_VOIDED);
});

test('cancelling a paid order allowances the invoice when it was issued in a previous calendar month', function () {
    fakeEcpayInvoicePaymentRefund();
    fakeEinvoiceAllowance();

    $order = makeEinvoiceOrder([
        'status' => Order::STATUS_PAID,
        'paid_at' => now(),
        'gateway_trade_no' => 'TRADE4',
        'invoice_status' => Order::INVOICE_ISSUED,
        'invoice_number' => 'AB12345678',
        'invoice_issued_at' => now()->subMonthNoOverflow(),
    ]);

    app(OrderService::class)->directCancelByBuyer($order, 'changed my mind');

    $order->refresh();
    expect($order->status)->toBe(Order::STATUS_CANCELLED);
    expect($order->invoice_status)->toBe(Order::INVOICE_ALLOWANCED);
});

test('cancelling a discounted paid order reconciles the allowance item total against the discounted refund amount', function () {
    fakeEcpayInvoicePaymentRefund();
    fakeEinvoiceAllowance();

    $order = makeEinvoiceOrder([
        'status' => Order::STATUS_PAID,
        'paid_at' => now(),
        'gateway_trade_no' => 'TRADE_DISCOUNT',
        'discount' => 100,
        'total' => 900,
        'invoice_status' => Order::INVOICE_ISSUED,
        'invoice_number' => 'AB12345678',
        'invoice_issued_at' => now()->subMonthNoOverflow(),
    ], qty: 1, unitPrice: 1000);

    app(OrderService::class)->directCancelByBuyer($order, 'changed my mind');

    $order->refresh();
    expect($order->status)->toBe(Order::STATUS_CANCELLED);
    expect($order->invoice_status)->toBe(Order::INVOICE_ALLOWANCED);
    Http::assertSent(function ($request) {
        if (! str_contains($request->url(), 'B2CInvoice/Allowance')) {
            return false;
        }

        $data = einvoiceDecryptRequestData($request->data());
        $itemSum = round(array_sum(array_column($data['Items'], 'ItemAmount')), 2);

        return (float) $itemSum === (float) $data['AllowanceAmount'];
    });
});

test('a failed invoice void during cancellation does not roll back the refund or the cancellation', function () {
    fakeEcpayInvoicePaymentRefund();
    fakeEinvoiceInvalid(['RtnCode' => '10200052', 'RtnMsg' => 'Failed']);

    $order = makeEinvoiceOrder([
        'status' => Order::STATUS_PAID,
        'paid_at' => now(),
        'gateway_trade_no' => 'TRADE5',
        'invoice_status' => Order::INVOICE_ISSUED,
        'invoice_number' => 'AB12345678',
        'invoice_issued_at' => now(),
    ]);

    app(OrderService::class)->directCancelByBuyer($order, 'changed my mind');

    $order->refresh();
    expect($order->status)->toBe(Order::STATUS_CANCELLED);
    expect((float) $order->refunded_amount)->toBeGreaterThan(0.0);
    expect($order->invoice_status)->toBe(Order::INVOICE_ISSUED);
});

// ---- Return: always allowance, regardless of month ----

test('approving a return always allowances the invoice regardless of the invoice month', function () {
    fakeEcpayInvoicePaymentRefund();
    fakeEinvoiceAllowance();

    $order = makeEinvoiceOrder([
        'status' => Order::STATUS_COMPLETED,
        'paid_at' => now()->subDays(10),
        'completed_at' => now()->subDay(),
        'gateway_trade_no' => 'TRADE6',
        'invoice_status' => Order::INVOICE_ISSUED,
        'invoice_number' => 'AB12345678',
        'invoice_issued_at' => now()->subMonthsNoOverflow(3),
    ]);
    $seller = $order->shop->user;
    $item = $order->items->first();
    $return = OrderReturn::factory()->requested()->create(['order_id' => $order->id]);
    $return->items()->create(['order_item_id' => $item->id, 'quantity' => 1]);

    app(OrderService::class)->approveReturn($return, $seller);

    expect($order->fresh()->invoice_status)->toBe(Order::INVOICE_ALLOWANCED);
});
