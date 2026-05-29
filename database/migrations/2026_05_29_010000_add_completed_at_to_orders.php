<?php

use App\Models\Order;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->timestamp('completed_at')->nullable()->after('paid_at');
        });

        // Backfill: for orders already in completed state, derive from the latest
        // OrderStatusLog row where to_status=completed; fall back to updated_at.
        DB::table('orders')
            ->where('status', Order::STATUS_COMPLETED)
            ->whereNull('completed_at')
            ->orderBy('id')
            ->chunkById(500, function ($orders) {
                foreach ($orders as $order) {
                    $logTime = DB::table('order_status_logs')
                        ->where('order_id', $order->id)
                        ->where('to_status', Order::STATUS_COMPLETED)
                        ->latest('id')
                        ->value('created_at');

                    DB::table('orders')->where('id', $order->id)->update([
                        'completed_at' => $logTime ?? $order->updated_at,
                    ]);
                }
            });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn('completed_at');
        });
    }
};
