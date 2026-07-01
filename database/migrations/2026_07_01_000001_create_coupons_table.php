<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('coupons', function (Blueprint $table) {
            $table->id();
            // null = platform-wide (future admin coupons); set = seller shop-scoped (v1).
            $table->foreignId('shop_id')->nullable()->constrained()->cascadeOnDelete();
            // Uniqueness among *active* (non-soft-deleted) coupons is enforced in
            // Seller\CouponController via Rule::unique(...)->whereNull('deleted_at').
            // MySQL can't express a partial unique index, and a hard global unique
            // would 500 when a seller reuses a soft-deleted code — so index only.
            $table->string('code')->index();
            $table->string('type'); // Coupon::TYPE_PERCENTAGE | TYPE_FIXED
            $table->decimal('value', 10, 2);
            $table->decimal('min_spend', 10, 2)->default(0);
            $table->decimal('max_discount', 10, 2)->nullable(); // cap for percentage type
            $table->unsignedInteger('usage_limit')->nullable();
            $table->unsignedInteger('used_count')->default(0);
            $table->unsignedInteger('per_user_limit')->nullable();
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('coupons');
    }
};
