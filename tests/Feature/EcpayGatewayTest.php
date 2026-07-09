<?php

use App\Exceptions\EcpayException;
use App\Models\Order;
use App\Models\Product;
use App\Models\Shop;
use App\Models\User;
use App\Notifications\OrderPaidNotification;
use App\Services\EcpayGateway;
use App\Services\OrderService;
use App\Services\PaymentService;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;

function fakeEcpayRefund(string $rtnCode = '1', string $rtnMsg = 'Succeeded'): void
{
    Http::fake([
        'payment-stage.ecpay.com.tw/*' => Http::response("RtnCode={$rtnCode}&RtnMsg={$rtnMsg}"),
    ]);
}

// ---- CheckMacValue algorithm ----

test('generateCheckMacValue is deterministic regardless of input param order', function () {
    $gateway = app(EcpayGateway::class);

    $params = ['MerchantID' => '2000132', 'MerchantTradeNo' => 'ORD1', 'TotalAmount' => 1000];

    expect($gateway->generateCheckMacValue($params))
        ->toBe($gateway->generateCheckMacValue(array_reverse($params, true)))
        ->toMatch('/^[A-F0-9]{64}$/');
});

test('generateCheckMacValue changes when any param value changes', function () {
    $gateway = app(EcpayGateway::class);

    $base = ['MerchantID' => '2000132', 'MerchantTradeNo' => 'ORD1', 'TotalAmount' => 1000];
    $changed = ['MerchantID' => '2000132', 'MerchantTradeNo' => 'ORD1', 'TotalAmount' => 1001];

    expect($gateway->generateCheckMacValue($base))->not->toBe($gateway->generateCheckMacValue($changed));
});

test('verify accepts a correctly signed payload and rejects a tampered one', function () {
    $gateway = app(EcpayGateway::class);

    $params = ['MerchantID' => '2000132', 'MerchantTradeNo' => 'ORD1', 'RtnCode' => 1];
    $params['CheckMacValue'] = $gateway->generateCheckMacValue($params);

    expect($gateway->verify($params))->toBeTrue();

    $tampered = array_merge($params, ['RtnCode' => 0]);
    expect($gateway->verify($tampered))->toBeFalse();
});

test('checkoutFormFields builds a self-consistent signed payload', function () {
    $order = Order::factory()->create(['total' => 500]);
    $gateway = app(EcpayGateway::class);

    $fields = $gateway->checkoutFormFields($order, 'https://example.test/notify', 'https://example.test/return');

    expect($fields['MerchantTradeNo'])->toBe('ORD'.$order->id);
    expect($fields['TotalAmount'])->toBe(500);
    expect($gateway->verify($fields))->toBeTrue();
});

// ---- Notify webhook ----

test('notify marks the order paid on a valid signed RtnCode=1 payload and is idempotent', function () {
    Notification::fake();

    $seller = User::factory()->seller()->create();
    $shop = Shop::factory()->create(['user_id' => $seller->id]);
    $order = Order::factory()->create(['shop_id' => $shop->id, 'status' => Order::STATUS_PENDING]);

    $gateway = app(EcpayGateway::class);
    $payload = [
        'MerchantID' => config('ecpay.merchant_id'),
        'MerchantTradeNo' => 'ORD'.$order->id,
        'RtnCode' => 1,
        'TradeNo' => 'ECPAYTEST123',
    ];
    $payload['CheckMacValue'] = $gateway->generateCheckMacValue($payload);

    $this->post(route('api.payments.ecpay.notify'), $payload)->assertSeeText('1|OK');

    $order->refresh();
    expect($order->isPaid())->toBeTrue();
    expect($order->gateway_trade_no)->toBe('ECPAYTEST123');
    Notification::assertSentToTimes($seller, OrderPaidNotification::class, 1);

    // Replay (ECPay retries until it gets "1|OK") must not re-notify.
    $this->post(route('api.payments.ecpay.notify'), $payload)->assertSeeText('1|OK');
    Notification::assertSentToTimes($seller, OrderPaidNotification::class, 1);
});

test('notify rejects an invalid signature and does not mark the order paid', function () {
    $order = Order::factory()->create(['status' => Order::STATUS_PENDING]);

    $payload = [
        'MerchantID' => config('ecpay.merchant_id'),
        'MerchantTradeNo' => 'ORD'.$order->id,
        'RtnCode' => 1,
        'CheckMacValue' => 'NOT-THE-REAL-SIGNATURE',
    ];

    $this->post(route('api.payments.ecpay.notify'), $payload)->assertSeeText('0|Fail');

    expect($order->fresh()->isPaid())->toBeFalse();
});

test('notify for an unknown order is rejected without error', function () {
    $gateway = app(EcpayGateway::class);
    $payload = ['MerchantID' => config('ecpay.merchant_id'), 'MerchantTradeNo' => 'ORD999999', 'RtnCode' => 1];
    $payload['CheckMacValue'] = $gateway->generateCheckMacValue($payload);

    $this->post(route('api.payments.ecpay.notify'), $payload)->assertSeeText('0|Fail');
});

test('a delayed notify for an order cancelled in the meantime refunds instead of resurrecting it to paid', function () {
    fakeEcpayRefund();

    $order = Order::factory()->create([
        'status' => Order::STATUS_CANCELLED,
        'paid_at' => null,
        'subtotal' => 300,
        'shipping_fee' => 20,
        'total' => 320,
    ]);

    $gateway = app(EcpayGateway::class);
    $payload = [
        'MerchantID' => config('ecpay.merchant_id'),
        'MerchantTradeNo' => 'ORD'.$order->id,
        'RtnCode' => 1,
        'TradeNo' => 'ECPAYLATE1',
    ];
    $payload['CheckMacValue'] = $gateway->generateCheckMacValue($payload);

    $this->post(route('api.payments.ecpay.notify'), $payload)->assertSeeText('1|OK');

    $order->refresh();
    expect($order->status)->toBe(Order::STATUS_CANCELLED);
    expect($order->isPaid())->toBeFalse();
    expect($order->gateway_trade_no)->toBe('ECPAYLATE1');
    expect((float) $order->refunded_amount)->toBe(320.0);
});

test('pay() rejects a non-pending order and does not render the checkout redirect', function () {
    $buyer = User::factory()->create();
    $order = Order::factory()->create(['user_id' => $buyer->id, 'status' => Order::STATUS_CANCELLED]);

    $this->actingAs($buyer)
        ->get(route('orders.pay', $order))
        ->assertRedirect(route('orders.show', $order));

    expect(session('error'))->not->toBeNull();
});

// ---- Refund ----

test('refund succeeds and increments refunded_amount', function () {
    fakeEcpayRefund();

    $order = Order::factory()->completed()->create(['gateway_trade_no' => 'ECPAYTEST1', 'refunded_amount' => 0]);

    expect(app(PaymentService::class)->refund($order, 100.0))->toBeTrue();
    expect((float) $order->fresh()->refunded_amount)->toBe(100.0);
});

test('refund throws and leaves refunded_amount unchanged when the gateway rejects it', function () {
    fakeEcpayRefund('10200052', 'Failed');

    $order = Order::factory()->completed()->create(['gateway_trade_no' => 'ECPAYTEST2', 'refunded_amount' => 0]);

    expect(fn () => app(PaymentService::class)->refund($order, 100.0))->toThrow(EcpayException::class);
    expect((float) $order->fresh()->refunded_amount)->toBe(0.0);
});

test('refund throws when the order has no recorded gateway trade number', function () {
    $order = Order::factory()->completed()->create(['gateway_trade_no' => null]);

    expect(fn () => app(PaymentService::class)->refund($order, 50.0))->toThrow(EcpayException::class);
});

test('refund rounds the amount consistently between the gateway call and the local ledger', function () {
    fakeEcpayRefund();

    $order = Order::factory()->completed()->create(['gateway_trade_no' => 'ECPAYTEST5', 'refunded_amount' => 0]);

    app(PaymentService::class)->refund($order, 66.66);

    // ECPay only accepts whole TWD amounts — the local ledger must record the
    // same rounded figure that was actually sent, not the raw fractional one.
    expect((float) $order->fresh()->refunded_amount)->toBe(67.0);
    Http::assertSent(function ($request) {
        parse_str($request->body(), $sent);

        return (int) $sent['TotalAmount'] === 67;
    });
});

// ---- Cancelling a paid order now refunds (closes the ADR-013 gap) ----

test('cancelling a paid order refunds the goods amount via the gateway', function () {
    fakeEcpayRefund();

    $seller = User::factory()->seller()->create();
    $shop = Shop::factory()->create(['user_id' => $seller->id]);
    $buyer = User::factory()->create();
    $order = Order::factory()->create([
        'user_id' => $buyer->id,
        'shop_id' => $shop->id,
        'status' => Order::STATUS_PAID,
        'paid_at' => now(),
        'gateway_trade_no' => 'ECPAYTEST3',
        'subtotal' => 200,
        'discount' => 0,
        'total' => 200,
    ]);

    app(OrderService::class)->directCancelByBuyer($order, 'changed my mind');

    expect($order->fresh()->status)->toBe(Order::STATUS_CANCELLED);
    expect((float) $order->fresh()->refunded_amount)->toBe(200.0);
});

test('a failed gateway refund rolls back the whole cancellation (stock, coupon, status)', function () {
    fakeEcpayRefund('10200052', 'Failed');

    $seller = User::factory()->seller()->create();
    $shop = Shop::factory()->create(['user_id' => $seller->id]);
    $buyer = User::factory()->create();
    $product = Product::factory()->create(['shop_id' => $shop->id, 'stock' => 3]);
    $order = Order::factory()->create([
        'user_id' => $buyer->id,
        'shop_id' => $shop->id,
        'status' => Order::STATUS_PAID,
        'paid_at' => now(),
        'gateway_trade_no' => 'ECPAYTEST4',
        'subtotal' => 100,
        'discount' => 0,
        'total' => 100,
    ]);
    $order->items()->create([
        'product_id' => $product->id,
        'product_name' => $product->name,
        'quantity' => 1,
        'unit_price' => 100,
        'subtotal' => 100,
    ]);

    expect(fn () => app(OrderService::class)->directCancelByBuyer($order, 'changed my mind'))
        ->toThrow(EcpayException::class);

    expect($order->fresh()->status)->toBe(Order::STATUS_PAID);
    expect((float) $order->fresh()->refunded_amount)->toBe(0.0);
    expect($product->fresh()->stock)->toBe(3);
});
