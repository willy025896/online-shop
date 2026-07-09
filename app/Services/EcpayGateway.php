<?php

namespace App\Services;

use App\Exceptions\EcpayException;
use App\Models\Order;
use Illuminate\Support\Facades\Http;

/**
 * Encapsulates ECPay (綠界) wire-format details — CheckMacValue signing/
 * verification and the two HTTP touchpoints (checkout redirect fields, credit
 * card refund/void). Callers (PaymentService) never see raw ECPay params.
 */
class EcpayGateway
{
    private string $merchantId;

    private string $hashKey;

    private string $hashIv;

    private string $mode;

    public function __construct()
    {
        $this->merchantId = (string) config('ecpay.merchant_id');
        $this->hashKey = (string) config('ecpay.hash_key');
        $this->hashIv = (string) config('ecpay.hash_iv');
        $this->mode = (string) config('ecpay.mode', 'stage');
    }

    /**
     * ECPay's MerchantTradeNo must be alnum only, <=20 chars. Our order_number
     * (e.g. "ORD-AB3XK9ZQ-1751900000") doesn't qualify, so derive a stable
     * alnum id from the order's primary key instead. The notify callback
     * reverses this with tradeNoToOrderId().
     */
    public static function merchantTradeNoFor(Order $order): string
    {
        return 'ORD'.$order->id;
    }

    public static function tradeNoToOrderId(string $merchantTradeNo): ?int
    {
        if (! str_starts_with($merchantTradeNo, 'ORD')) {
            return null;
        }

        $id = substr($merchantTradeNo, 3);

        return ctype_digit($id) ? (int) $id : null;
    }

    /**
     * @return array<string, mixed> form fields (including CheckMacValue) for
     *                              ECPay's AioCheckOut hosted checkout page.
     */
    public function checkoutFormFields(Order $order, string $returnUrl, string $clientBackUrl): array
    {
        $order->loadMissing('items');

        $itemName = $order->items->pluck('product_name')->implode('#');

        $params = [
            'MerchantID' => $this->merchantId,
            'MerchantTradeNo' => self::merchantTradeNoFor($order),
            'MerchantTradeDate' => now()->format('Y/m/d H:i:s'),
            'PaymentType' => 'aio',
            'TotalAmount' => (int) round((float) $order->total),
            'TradeDesc' => 'Online Shop Order',
            'ItemName' => $itemName !== '' ? $itemName : 'Order Items',
            'ReturnURL' => $returnUrl,
            'ClientBackURL' => $clientBackUrl,
            'ChoosePayment' => 'Credit',
            'EncryptType' => 1,
        ];

        $params['CheckMacValue'] = $this->generateCheckMacValue($params);

        return $params;
    }

    public function checkoutUrl(): string
    {
        return $this->baseUrl('checkout');
    }

    /**
     * Recomputes CheckMacValue from every field except CheckMacValue itself
     * and compares with the value ECPay sent, using a timing-safe comparison.
     */
    public function verify(array $payload): bool
    {
        $received = $payload['CheckMacValue'] ?? null;

        if (! is_string($received) || $received === '') {
            return false;
        }

        return hash_equals($this->generateCheckMacValue($payload), $received);
    }

    /**
     * ECPay's CheckMacValue algorithm: case-insensitive sort params A-Z by key
     * (matches ECPay's own SDK, which uses uksort+strcasecmp rather than a
     * plain case-sensitive ksort), join as "HashKey=...&k=v&...&HashIV=...",
     * urlencode the whole string, lowercase it, apply the documented
     * PHP-urlencode -> .NET UrlEncode character fixups, SHA256, uppercase.
     * See ECPay's official CheckMacValue spec.
     */
    public function generateCheckMacValue(array $params): string
    {
        unset($params['CheckMacValue']);
        uksort($params, 'strcasecmp');

        $pairs = [];
        foreach ($params as $key => $value) {
            $pairs[] = "{$key}={$value}";
        }

        $raw = 'HashKey='.$this->hashKey.'&'.implode('&', $pairs).'&HashIV='.$this->hashIv;

        $encoded = strtolower(urlencode($raw));

        $encoded = str_replace(
            ['%2d', '%5f', '%2e', '%21', '%2a', '%28', '%29'],
            ['-', '_', '.', '!', '*', '(', ')'],
            $encoded
        );

        return strtoupper(hash('sha256', $encoded));
    }

    /**
     * Calls ECPay's credit card refund/void API (CreditDetail/DoAction,
     * Action=R). Requires the order's gateway_trade_no (ECPay's own TradeNo,
     * captured from the payment notify payload) — a refund cannot be issued
     * without it. Throws EcpayException on any failure so the caller's
     * DB::transaction rolls back rather than leaving a half-applied refund.
     */
    public function refund(Order $order, float $amount): void
    {
        if (empty($order->gateway_trade_no)) {
            throw new EcpayException('missing_trade_no', 'Order has no ECPay TradeNo recorded; cannot refund.');
        }

        $params = [
            'MerchantID' => $this->merchantId,
            'MerchantTradeNo' => self::merchantTradeNoFor($order),
            'TradeNo' => $order->gateway_trade_no,
            'Action' => 'R',
            'TotalAmount' => (int) round($amount),
        ];
        $params['CheckMacValue'] = $this->generateCheckMacValue($params);

        try {
            $response = Http::asForm()->post($this->baseUrl('credit_card_action'), $params);
        } catch (\Throwable $e) {
            throw new EcpayException('network_error', $e->getMessage());
        }

        if (! $response->successful()) {
            throw new EcpayException('network_error', "ECPay refund request failed with HTTP {$response->status()}");
        }

        parse_str($response->body(), $result);

        if ((string) ($result['RtnCode'] ?? '') !== '1') {
            throw new EcpayException('gateway_rejected', $result['RtnMsg'] ?? 'ECPay refund was rejected');
        }
    }

    private function baseUrl(string $key): string
    {
        return config("ecpay.base_urls.{$this->mode}.{$key}");
    }
}
