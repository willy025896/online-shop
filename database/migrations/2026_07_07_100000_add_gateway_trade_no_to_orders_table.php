<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            // ECPay's own transaction identifier (their `TradeNo`, distinct from
            // our `MerchantTradeNo`/`order_number`), captured from the payment
            // notify callback. Required by ECPay's refund API (CreditDetail/DoAction),
            // which identifies the transaction to refund by TradeNo, not MerchantTradeNo.
            $table->string('gateway_trade_no')->nullable()->after('paid_at');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn('gateway_trade_no');
        });
    }
};
