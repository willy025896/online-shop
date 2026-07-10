<?php

namespace App\Services;

use App\Exceptions\EinvoiceException;
use App\Models\Order;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;

/**
 * Encapsulates ECPay's electronic invoice (B2C 電子發票) wire format — this is
 * a genuinely separate service from EcpayGateway (payment): different
 * credentials (config/ecpay_invoice.php) and a different crypto scheme
 * (AES-128-CBC over the whole JSON payload, not a SHA256 CheckMacValue over
 * plaintext params). See ADR-019. Callers (InvoiceService) never see raw
 * ECPay invoice params.
 */
class EcpayInvoiceGateway
{
    private string $merchantId;

    private string $hashKey;

    private string $hashIv;

    private string $mode;

    public function __construct()
    {
        $this->merchantId = (string) config('ecpay_invoice.merchant_id');
        $this->hashKey = (string) config('ecpay_invoice.hash_key');
        $this->hashIv = (string) config('ecpay_invoice.hash_iv');
        $this->mode = (string) config('ecpay_invoice.mode', 'stage');
    }

    /**
     * @return array{invoice_no: ?string, random_number: ?string, invoice_date: \Carbon\Carbon}
     */
    public function issue(Order $order): array
    {
        $order->loadMissing('items', 'user');

        $salesAmount = (int) round((float) $order->total);

        // buildIssueItems()'s line amounts are exact (unrounded) floats that
        // sum to $order->total; SalesAmount must be a whole TWD amount, so
        // any rounding drift (e.g. a percentage coupon producing a
        // fractional total) is reconciled as its own line rather than
        // silently sending a mismatched total to ECPay.
        $items = $this->reconcileItemsToTotal($this->buildIssueItems($order), $salesAmount, '零頭調整');

        $data = [
            'MerchantID' => $this->merchantId,
            'RelateNumber' => 'INV'.$order->id,
            'CustomerID' => '',
            'CustomerIdentifier' => '',
            'CustomerName' => (string) $order->shipping_name,
            'CustomerAddr' => (string) $order->shipping_address,
            'CustomerPhone' => (string) $order->shipping_phone,
            'CustomerEmail' => (string) ($order->user?->email ?? ''),
            'Print' => '0',
            'Donation' => '0',
            'TaxType' => '1',
            'InvType' => '07',
            'SalesAmount' => $salesAmount,
            'InvoiceRemark' => '',
            'Items' => $items,
        ];

        $result = $this->request('issue', $data);

        return [
            'invoice_no' => $result['InvoiceNo'] ?? null,
            'random_number' => $result['RandomNumber'] ?? null,
            'invoice_date' => isset($result['InvoiceDate']) ? Carbon::parse($result['InvoiceDate']) : now(),
        ];
    }

    public function invalidate(Order $order, string $reason): void
    {
        if (empty($order->invoice_number) || $order->invoice_issued_at === null) {
            throw new EinvoiceException('missing_invoice_number', 'Order has no e-invoice recorded; cannot invalidate.');
        }

        $this->request('invalid', [
            'MerchantID' => $this->merchantId,
            'InvoiceNo' => $order->invoice_number,
            'InvoiceDate' => $order->invoice_issued_at->format('Y-m-d'),
            'Reason' => $reason,
        ]);
    }

    /**
     * @param  array<int, array{name: string, count: int|float, unit_price: float}>  $items
     * @return array{allowance_no: ?string}
     */
    public function allowance(Order $order, float $amount, array $items): array
    {
        if (empty($order->invoice_number) || $order->invoice_issued_at === null) {
            throw new EinvoiceException('missing_invoice_number', 'Order has no e-invoice recorded; cannot issue an allowance.');
        }

        $allowanceItems = [];
        foreach (array_values($items) as $i => $item) {
            $allowanceItems[] = [
                'ItemSeq' => $i + 1,
                'ItemName' => $item['name'],
                'ItemCount' => $item['count'],
                'ItemWord' => '件',
                'ItemPrice' => $item['unit_price'],
                'ItemTaxType' => '1',
                'ItemAmount' => round($item['unit_price'] * $item['count'], 2),
            ];
        }

        $allowanceAmount = (int) round($amount);

        // $items are always priced at their full, undiscounted unit_price
        // (see OrderService::itemsForInvoice()/finalizeReturn()) — whenever
        // the order used a coupon, $amount (the actual refunded amount) is
        // already discount-adjusted and smaller than the item total. ECPay
        // requires sum(Items.ItemAmount) === AllowanceAmount, so reconcile
        // the difference as its own line rather than pushing the discount
        // math onto every caller (mirrors issue()'s reconciliation line for
        // the same reason). The drift isn't always discount-driven (a
        // non-discounted partial return can still land off by a rounding
        // cent), so the line is labeled as a generic amount adjustment
        // rather than assuming "discount".
        $allowanceItems = $this->reconcileItemsToTotal($allowanceItems, $allowanceAmount, '金額調整');

        $result = $this->request('allowance', [
            'MerchantID' => $this->merchantId,
            'InvoiceNo' => $order->invoice_number,
            'InvoiceDate' => $order->invoice_issued_at->format('Y-m-d'),
            'AllowanceNotify' => 'N',
            'CustomerName' => (string) $order->shipping_name,
            'AllowanceAmount' => $allowanceAmount,
            'Reason' => 'Order return',
            'Items' => $allowanceItems,
        ]);

        return ['allowance_no' => $result['IA_Allow_No'] ?? null];
    }

    /**
     * @return array<int, array{ItemSeq: int, ItemName: string, ItemCount: int|float, ItemWord: string, ItemPrice: float, ItemTaxType: string, ItemAmount: float}>
     */
    private function buildIssueItems(Order $order): array
    {
        $items = [];
        $seq = 1;

        foreach ($order->items as $item) {
            $items[] = [
                'ItemSeq' => $seq++,
                'ItemName' => $item->product_name,
                'ItemCount' => $item->quantity,
                'ItemWord' => '件',
                'ItemPrice' => (float) $item->unit_price,
                'ItemTaxType' => '1',
                'ItemAmount' => (float) $item->subtotal,
            ];
        }

        // Items must sum to SalesAmount (order->total) — shipping and the
        // coupon discount each need their own line so the totals reconcile.
        if ((float) $order->shipping_fee > 0) {
            $items[] = $this->buildAdjustmentLineItem($seq++, '運費', (float) $order->shipping_fee);
        }

        if ((float) $order->discount > 0) {
            $items[] = $this->buildAdjustmentLineItem($seq++, '折扣', -(float) $order->discount);
        }

        return $items;
    }

    /**
     * Appends a single adjustment line (if needed) so `sum(Items.ItemAmount)`
     * matches `$target` exactly — used by both issue() and allowance(), whose
     * item totals can drift from their respective SalesAmount/AllowanceAmount
     * due to a coupon discount or plain rounding.
     *
     * @param  array<int, array{ItemSeq: int, ItemName: string, ItemCount: int|float, ItemWord: string, ItemPrice: float, ItemTaxType: string, ItemAmount: float}>  $items
     * @return array<int, array{ItemSeq: int, ItemName: string, ItemCount: int|float, ItemWord: string, ItemPrice: float, ItemTaxType: string, ItemAmount: float}>
     */
    private function reconcileItemsToTotal(array $items, float $target, string $adjustmentLabel): array
    {
        $itemsSum = round((float) array_sum(array_column($items, 'ItemAmount')), 2);
        $adjustment = round($target - $itemsSum, 2);

        if ($adjustment !== 0.0) {
            $items[] = $this->buildAdjustmentLineItem(count($items) + 1, $adjustmentLabel, $adjustment);
        }

        return $items;
    }

    /**
     * A single-count, whole-line adjustment item (used for shipping,
     * discount, and the SalesAmount/AllowanceAmount rounding-reconciliation
     * lines) — the item's price and amount are always the same signed value.
     *
     * @return array{ItemSeq: int, ItemName: string, ItemCount: int, ItemWord: string, ItemPrice: float, ItemTaxType: string, ItemAmount: float}
     */
    private function buildAdjustmentLineItem(int $seq, string $name, float $amount): array
    {
        return [
            'ItemSeq' => $seq,
            'ItemName' => $name,
            'ItemCount' => 1,
            'ItemWord' => '式',
            'ItemPrice' => $amount,
            'ItemTaxType' => '1',
            'ItemAmount' => $amount,
        ];
    }

    /**
     * Wraps $data as the encrypted `Data` field, POSTs to the given endpoint,
     * decrypts the response, and throws on any transport/gateway failure.
     */
    private function request(string $endpoint, array $data): array
    {
        $payload = [
            'MerchantID' => $this->merchantId,
            'RqHeader' => ['Timestamp' => now()->timestamp],
            'Data' => $this->encrypt($data),
        ];

        try {
            $response = Http::asJson()->post($this->baseUrl($endpoint), $payload);
        } catch (\Throwable $e) {
            throw new EinvoiceException('network_error', $e->getMessage());
        }

        if (! $response->successful()) {
            throw new EinvoiceException('network_error', "ECPay invoice request failed with HTTP {$response->status()}");
        }

        $result = $this->decrypt((string) ($response->json('Data') ?? ''));

        if ((string) ($result['RtnCode'] ?? '') !== '1') {
            throw new EinvoiceException('gateway_rejected', $result['RtnMsg'] ?? 'ECPay invoice request was rejected');
        }

        return $result;
    }

    /**
     * ECPay's invoice encryption: JSON-encode, urlencode, AES-128-CBC encrypt
     * (HashKey as key, HashIV as iv, PKCS7 padding — OpenSSL's default for
     * AES-CBC), base64 the ciphertext.
     */
    private function encrypt(array $data): string
    {
        $urlEncoded = urlencode(json_encode($data, JSON_UNESCAPED_UNICODE));

        $cipher = openssl_encrypt($urlEncoded, 'AES-128-CBC', $this->hashKey, OPENSSL_RAW_DATA, $this->hashIv);

        return base64_encode($cipher);
    }

    /**
     * Symmetric inverse of encrypt(): base64 decode, AES-128-CBC decrypt,
     * urldecode, JSON decode. Throws its own `decrypt_failed` reason (rather
     * than returning an empty array that `request()` would report as
     * `gateway_rejected`) so a misconfigured HashKey/HashIV — a local
     * config bug — is distinguishable in logs from ECPay genuinely rejecting
     * the business request.
     */
    private function decrypt(string $data): array
    {
        if ($data === '') {
            throw new EinvoiceException('decrypt_failed', 'ECPay invoice response had no Data field.');
        }

        $plain = openssl_decrypt(base64_decode($data), 'AES-128-CBC', $this->hashKey, OPENSSL_RAW_DATA, $this->hashIv);

        if ($plain === false) {
            throw new EinvoiceException('decrypt_failed', 'Failed to AES-decrypt the ECPay invoice response.');
        }

        $decoded = json_decode(urldecode($plain), true);

        if (! is_array($decoded)) {
            throw new EinvoiceException('decrypt_failed', 'ECPay invoice response was not valid JSON after decryption.');
        }

        return $decoded;
    }

    private function baseUrl(string $key): string
    {
        return config("ecpay_invoice.base_urls.{$this->mode}.{$key}");
    }
}
