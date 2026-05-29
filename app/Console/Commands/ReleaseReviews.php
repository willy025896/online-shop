<?php

namespace App\Console\Commands;

use App\Models\Order;
use App\Services\ReviewService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ReleaseReviews extends Command
{
    protected $signature = 'reviews:release';

    protected $description = 'Release reviews whose cooling period has ended or whose 14-day timeout has passed';

    public function handle(ReviewService $reviewService): int
    {
        $released = 0;

        Order::whereNull('review_released_at')
            ->where(function ($q) {
                $q->where(function ($q2) {
                    // Cooling period ended (both sides have submitted reviews)
                    $q2->whereNotNull('review_cooling_until')
                        ->where('review_cooling_until', '<=', now());
                })->orWhere(function ($q2) {
                    // 14-day timeout from order completion: window closes whether
                    // or not reviews exist, to prevent long-tail retaliatory reviews
                    // months after the transaction. The 14-day deadline is surfaced
                    // in the order detail UI when status=completed.
                    $q2->whereNotNull('completed_at')
                        ->where('completed_at', '<=', now()->subDays(14));
                });
            })
            ->chunkById(100, function ($orders) use ($reviewService, &$released) {
                foreach ($orders as $order) {
                    DB::transaction(function () use ($order, $reviewService, &$released) {
                        $locked = Order::lockForUpdate()->find($order->id);

                        if ($locked->review_released_at !== null) {
                            return;
                        }

                        $reviewService->releaseOrder($locked);
                        $released++;
                    });
                }
            });

        $this->info("Released {$released} order review(s).");

        return self::SUCCESS;
    }
}
