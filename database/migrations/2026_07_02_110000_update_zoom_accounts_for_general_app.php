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
            // Add refresh_token back (General App OAuth has refresh tokens)
            $table->text('refresh_token')->nullable()->after('access_token');

            // Remove S2S-only column
            $table->dropColumn('s2s_account_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('zoom_accounts', function (Blueprint $table) {
            $table->text('s2s_account_id')->nullable()->after('account_name');
            $table->dropColumn('refresh_token');
        });
    }
};
