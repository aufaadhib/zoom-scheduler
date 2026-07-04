<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('zoom_accounts', function (Blueprint $table) {
            $table->string('webhook_token')->nullable()->unique()->after('refresh_token');
            $table->text('webhook_secret')->nullable()->after('webhook_token');
            $table->boolean('webhook_enabled')->default(false)->after('webhook_secret');
            $table->timestamp('webhook_verified_at')->nullable()->after('webhook_enabled');
            $table->text('webhook_verified_url')->nullable()->after('webhook_verified_at');
            $table->string('webhook_last_event')->nullable()->after('webhook_verified_url');
            $table->timestamp('webhook_last_received_at')->nullable()->after('webhook_last_event');
            $table->text('webhook_last_received_url')->nullable()->after('webhook_last_received_at');
        });

        DB::table('zoom_accounts')
            ->whereNull('webhook_token')
            ->orderBy('id')
            ->cursor()
            ->each(function (object $account): void {
                DB::table('zoom_accounts')
                    ->where('id', $account->id)
                    ->update(['webhook_token' => Str::random(48)]);
            });
    }

    public function down(): void
    {
        Schema::table('zoom_accounts', function (Blueprint $table) {
            $table->dropColumn([
                'webhook_token',
                'webhook_secret',
                'webhook_enabled',
                'webhook_verified_at',
                'webhook_verified_url',
                'webhook_last_event',
                'webhook_last_received_at',
                'webhook_last_received_url',
            ]);
        });
    }
};
