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
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('telegraph_chat_id')->nullable()->constrained('telegraph_chats')->nullOnDelete();
            $table->string('telegram_link_code')->nullable()->unique();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['telegraph_chat_id']);
            $table->dropColumn(['telegraph_chat_id', 'telegram_link_code']);
        });
    }
};
