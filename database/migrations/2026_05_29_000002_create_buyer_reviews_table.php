<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('buyer_reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('shop_id')->constrained()->cascadeOnDelete();
            $table->foreignId('order_id')->unique()->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('rating');
            $table->text('comment')->nullable();
            $table->string('status')->default('published');
            $table->softDeletes();
            $table->timestamps();

            $table->index(['user_id', 'status']);
            $table->index('shop_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('buyer_reviews');
    }
};
