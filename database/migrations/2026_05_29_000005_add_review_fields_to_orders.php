<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->timestamp('review_cooling_until')->nullable()->after('notes');
            $table->timestamp('review_released_at')->nullable()->after('review_cooling_until');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['review_cooling_until', 'review_released_at']);
        });
    }
};
