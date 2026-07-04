<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('zoom_accounts', function (Blueprint $table) {
            $table->text('access_token')->nullable()->change();
            $table->timestamp('token_expires_at')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('zoom_accounts', function (Blueprint $table) {
            $table->text('access_token')->nullable(false)->change();
            $table->timestamp('token_expires_at')->nullable(false)->change();
        });
    }
};
