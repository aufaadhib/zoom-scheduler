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
            // Add S2S credential columns (encrypted at application level)
            $table->string('account_name')->nullable()->after('user_id');
            $table->text('s2s_account_id')->nullable()->after('account_name');
            $table->text('client_id')->nullable()->after('s2s_account_id');
            $table->text('client_secret')->nullable()->after('client_id');
            $table->string('email')->nullable()->change();

            // Drop columns no longer needed for S2S
            $table->dropColumn(['refresh_token', 'avatar_url']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('zoom_accounts', function (Blueprint $table) {
            $table->text('refresh_token')->nullable();
            $table->string('avatar_url')->nullable();
            $table->dropColumn(['account_name', 's2s_account_id', 'client_id', 'client_secret']);
        });
    }
};
