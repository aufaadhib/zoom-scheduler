<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;

class TelegramController extends Controller
{
    /**
     * Generate a new link code for the user
     */
    public function generateLinkCode(Request $request)
    {
        $user = Auth::user();
        
        // Generate random 6 characters uppercase code
        $code = strtoupper(Str::random(6));
        
        // Ensure uniqueness (rare chance of collision, but good practice)
        while(\App\Models\User::where('telegram_link_code', $code)->exists()) {
            $code = strtoupper(Str::random(6));
        }

        $user->telegram_link_code = $code;
        $user->save();

        return back()->with('success', 'Kode tautan Telegram berhasil dibuat! Silakan kirimkan kode ini ke Bot Telegram.');
    }

    /**
     * Unlink a specific Telegram account
     */
    public function unlink(Request $request)
    {
        $request->validate([
            'chat_id' => 'required|integer|exists:telegraph_chats,id'
        ]);

        $user = Auth::user();
        
        $chat = \DefStudio\Telegraph\Models\TelegraphChat::where('id', $request->chat_id)
            ->where('user_id', $user->id)
            ->first();

        if ($chat) {
            $chat->user_id = null;
            $chat->save();

            if ((int) $user->telegraph_chat_id === (int) $chat->id) {
                $user->telegraph_chat_id = $user->telegraphChats()
                    ->where('id', '!=', $chat->id)
                    ->value('id');
                $user->save();
            }

            return back()->with('success', 'Akun Telegram ' . ($chat->name ? "({$chat->name})" : "") . ' berhasil diputuskan.');
        }

        return back()->with('error', 'Gagal memutuskan tautan. Akun tidak ditemukan.');
    }
}
