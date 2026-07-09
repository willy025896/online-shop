<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            // Electronic invoice (電子發票) fields — see ADR-019. One invoice per
            // order; `invoice_status` tracks issued/voided/allowanced via Order's
            // INVOICE_* constants (never raw strings).
            $table->string('invoice_number')->nullable()->after('refunded_amount');
            $table->string('invoice_random_code', 4)->nullable()->after('invoice_number');
            $table->timestamp('invoice_issued_at')->nullable()->after('invoice_random_code');
            $table->string('invoice_status')->nullable()->after('invoice_issued_at');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['invoice_number', 'invoice_random_code', 'invoice_issued_at', 'invoice_status']);
        });
    }
};
