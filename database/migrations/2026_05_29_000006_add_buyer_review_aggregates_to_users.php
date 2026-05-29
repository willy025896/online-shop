<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->unsignedInteger('buyer_reviews_count')->default(0)->after('preferences');
            $table->unsignedInteger('buyer_rating_sum')->default(0)->after('buyer_reviews_count');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['buyer_reviews_count', 'buyer_rating_sum']);
        });
    }
};
