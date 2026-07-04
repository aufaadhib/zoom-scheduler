<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('telegraph_chats', function (Blueprint $table) {
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
        });

        // Migrate existing relations
        $usersWithChats = DB::table('users')->whereNotNull('telegraph_chat_id')->get();
        foreach ($usersWithChats as $user) {
            DB::table('telegraph_chats')
                ->where('id', $user->telegraph_chat_id)
                ->update(['user_id' => $user->id]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Copy back if needed (optional)
        $chatsWithUsers = DB::table('telegraph_chats')->whereNotNull('user_id')->get();
        foreach ($chatsWithUsers as $chat) {
            DB::table('users')
                ->where('id', $chat->user_id)
                ->update(['telegraph_chat_id' => $chat->id]);
        }

        Schema::table('telegraph_chats', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->dropColumn('user_id');
        });
    }
};
