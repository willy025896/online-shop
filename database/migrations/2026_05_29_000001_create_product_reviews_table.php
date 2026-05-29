<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('shop_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('order_item_id')->unique()->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('rating');
            $table->text('comment')->nullable();
            $table->text('seller_reply')->nullable();
            $table->timestamp('seller_replied_at')->nullable();
            $table->string('status')->default('published');
            $table->softDeletes();
            $table->timestamps();

            $table->index(['product_id', 'status']);
            $table->index(['shop_id', 'status']);
            $table->index('user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_reviews');
    }
};
