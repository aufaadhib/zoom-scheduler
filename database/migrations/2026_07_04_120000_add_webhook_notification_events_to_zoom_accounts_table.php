<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('zoom_accounts', function (Blueprint $table) {
            $table->json('webhook_notification_events')->nullable()->after('webhook_enabled');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('zoom_accounts', function (Blueprint $table) {
            $table->dropColumn('webhook_notification_events');
        });
    }
};
