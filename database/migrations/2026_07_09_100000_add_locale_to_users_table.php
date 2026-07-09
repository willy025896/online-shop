<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Nullable — no value means "fall back to the current session locale /
            // app default". Only set once the user explicitly switches locale via
            // LocaleController. Queued notifications (mail/database/broadcast) read
            // this via User::preferredLocale() since a queue worker has no HTTP
            // session to read the locale from.
            $table->string('locale', 10)->nullable()->after('phone');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('locale');
        });
    }
};
