<x-layouts.app :title="'Dashboard — Zoom Scheduler'">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">

        {{-- Header --}}
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-8">
            <div>
                <h1 class="text-2xl sm:text-3xl font-bold bg-gradient-to-r from-white to-white/60 bg-clip-text text-transparent">
                    Akun Zoom Anda
                </h1>
                <p class="text-white/40 text-sm mt-1">
                    Kelola akun Zoom S2S OAuth untuk menjadwalkan meeting
                </p>
            </div>
            <a href="{{ route('zoom.create') }}" class="btn-primary inline-flex items-center gap-2 shrink-0 self-start sm:self-auto" id="connect-zoom-btn">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                </svg>
                Tambah Akun Zoom
            </a>
        </div>

        {{-- Zoom Accounts Grid --}}
        @if($zoomAccounts->count() > 0)
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                @foreach($zoomAccounts as $index => $account)
                    <div class="glass-card rounded-2xl p-6 animate-fade-in-up group hover:border-white/15 transition-all duration-300"
                         style="animation-delay: {{ $index * 80 }}ms">
                        {{-- Account Header --}}
                        <div class="flex items-center justify-between gap-4 mb-5">
                            <a href="{{ route('zoom.profile.edit', $account) }}" class="flex items-center gap-4 min-w-0 group/header flex-1">
                                {{-- Avatar Initial --}}
                                <div class="shrink-0">
                                    <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-[#2D8CFF]/20 to-[#0E71EB]/20 border border-[#2D8CFF]/20 flex items-center justify-center text-lg font-bold text-[#2D8CFF] group-hover/header:from-[#2D8CFF]/30 group-hover/header:to-[#0E71EB]/30 transition-all duration-300">
                                        {{ strtoupper(substr($account->account_name ?? $account->display_name ?? '?', 0, 1)) }}
                                    </div>
                                </div>

                                {{-- Info --}}
                                <div class="min-w-0 flex-1">
                                    <h3 class="text-white font-semibold truncate group-hover/header:text-[#2D8CFF] transition-colors flex items-center gap-1.5">
                                        {{ $account->account_name ?? 'Zoom Account' }}
                                        <svg class="w-3.5 h-3.5 text-[#2D8CFF] opacity-0 group-hover/header:opacity-100 transition-opacity" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0115.75 21H5.25A2.25 2.25 0 013 18.75V8.25A2.25 2.25 0 015.25 6H10" />
                                        </svg>
                                    </h3>
                                    <p class="text-white/40 text-sm truncate">{{ $account->email ?? $account->display_name ?? 'No info' }}</p>
                                </div>
                            </a>

                            {{-- Status Badge --}}
                            @if($account->isTokenValid())
                                <span class="shrink-0 inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-medium bg-emerald-500/10 text-emerald-400 border border-emerald-500/20">
                                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 animate-pulse"></span>
                                    Active
                                </span>
                            @else
                                <span class="shrink-0 inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-medium bg-red-500/10 text-red-400 border border-red-500/20">
                                    <span class="w-1.5 h-1.5 rounded-full bg-red-400"></span>
                                    Expired
                                </span>
                            @endif
                        </div>

                        {{-- Token Info --}}
                        <div class="space-y-2 mb-5">
                            <div class="flex items-center justify-between text-sm">
                                <span class="text-white/30 flex items-center gap-1.5">
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12c0 1.268-.63 2.39-1.593 3.068a3.745 3.745 0 01-1.043 3.296 3.745 3.745 0 01-3.296 1.043A3.745 3.745 0 0112 21c-1.268 0-2.39-.63-3.068-1.593a3.746 3.746 0 01-3.296-1.043 3.745 3.745 0 01-1.043-3.296A3.745 3.745 0 013 12c0-1.268.63-2.39 1.593-3.068a3.745 3.745 0 011.043-3.296 3.746 3.746 0 013.296-1.043A3.746 3.746 0 0112 3c1.268 0 2.39.63 3.068 1.593a3.746 3.746 0 013.296 1.043 3.746 3.746 0 011.043 3.296A3.745 3.745 0 0121 12z" />
                                    </svg>
                                    Tipe Paket
                                </span>
                                <span class="text-white/50 font-medium">
                                    @if($account->plan_type)
                                        @if(str_contains($account->plan_type, 'Licensed'))
                                            <span class="text-emerald-400">{{ $account->plan_type }}</span>
                                        @else
                                            <span class="text-amber-400">{{ $account->plan_type }}</span>
                                        @endif
                                    @else
                                        <span class="text-white/30">Belum terhubung</span>
                                    @endif
                                </span>
                            </div>
                            <div class="flex items-center justify-between text-sm">
                                <span class="text-white/30 flex items-center gap-1.5">
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.109A11.386 11.386 0 0112.5 20c-1.708 0-3.32-.375-4.772-1.046a11.38 11.38 0 00-2.478 2.372 4.125 4.125 0 017.25-3.33M8.284 15.632A9.37 9.37 0 0110.5 15c1.113 0 2.16.285 3.07.786m-7.07-7.07a3 3 0 115.656 0M9 9.75a3 3 0 11-6 0c0-1.657 1.343-3 3-3zm9.75 3a3 3 0 11-6 0 3 3 0 016 0z" />
                                    </svg>
                                    Kapasitas
                                </span>
                                <span class="text-white/50 font-medium">
                                    {{ $account->meeting_capacity ?? 100 }} Peserta
                                </span>
                            </div>
                            <div class="flex items-center justify-between text-sm">
                                <span class="text-white/30 flex items-center gap-1.5">
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                    Token
                                </span>
                                <span class="text-white/50 font-medium">{{ $account->token_expires_in }}</span>
                            </div>
                            <div class="flex items-center justify-between text-sm">
                                <span class="text-white/30 flex items-center gap-1.5">
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5" />
                                    </svg>
                                    Terhubung
                                </span>
                                <span class="text-white/50 font-medium">{{ $account->created_at->diffForHumans() }}</span>
                            </div>
                        </div>

                        {{-- Actions --}}
                        <div class="flex items-center gap-2 pt-4 border-t border-white/5">
                            <form method="POST" action="{{ route('zoom.refresh', $account) }}" class="flex-1">
                                @csrf
                                <button type="submit" class="cursor-pointer w-full flex items-center justify-center gap-2 px-3 py-2.5 rounded-xl text-sm font-medium text-[#2D8CFF] bg-[#2D8CFF]/10 hover:bg-[#2D8CFF]/20 border border-[#2D8CFF]/10 hover:border-[#2D8CFF]/20 transition-all duration-200 active:scale-[0.97]">
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0l3.181 3.183a8.25 8.25 0 0013.803-3.7M4.031 9.865a8.25 8.25 0 0113.803-3.7l3.181 3.182" />
                                    </svg>
                                    Refresh Token
                                </button>
                            </form>

                            <button type="button"
                                onclick="confirmDelete({{ $account->id }}, '{{ addslashes($account->label) }}')"
                                class="cursor-pointer flex items-center justify-center w-10 h-10 rounded-xl text-red-400/50 hover:text-red-400 hover:bg-red-500/10 border border-transparent hover:border-red-500/20 transition-all duration-200 active:scale-[0.95]"
                                aria-label="Hapus akun {{ $account->label }}">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0" />
                                </svg>
                            </button>
                        </div>
                    </div>
                @endforeach
            </div>

            {{-- Stats Footer --}}
            <div class="mt-8 flex items-center justify-center gap-6 text-sm text-white/20">
                <span class="flex items-center gap-1.5">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M18 18.72a9.094 9.094 0 003.741-.479 3 3 0 00-4.682-2.72m.94 3.198l.001.031c0 .225-.012.447-.037.666A11.944 11.944 0 0112 21c-2.17 0-4.207-.576-5.963-1.584A6.062 6.062 0 016 18.719m12 0a5.971 5.971 0 00-.941-3.197m0 0A5.995 5.995 0 0012 12.75a5.995 5.995 0 00-5.058 2.772m0 0a3 3 0 00-4.681 2.72 8.986 8.986 0 003.74.477m.94-3.197a5.971 5.971 0 00-.94 3.197M15 6.75a3 3 0 11-6 0 3 3 0 016 0zm6 3a2.25 2.25 0 11-4.5 0 2.25 2.25 0 014.5 0zm-13.5 0a2.25 2.25 0 11-4.5 0 2.25 2.25 0 014.5 0z" />
                    </svg>
                    {{ $zoomAccounts->count() }} akun terhubung
                </span>
                <span class="flex items-center gap-1.5">
                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-400"></span>
                    {{ $zoomAccounts->filter(fn($a) => $a->isTokenValid())->count() }} active
                </span>
                <span class="flex items-center gap-1.5">
                    <span class="w-1.5 h-1.5 rounded-full bg-red-400"></span>
                    {{ $zoomAccounts->filter(fn($a) => $a->isTokenExpired())->count() }} expired
                </span>
            </div>
        @else
            {{-- Empty State --}}
            <div class="glass-card rounded-2xl p-12 text-center animate-fade-in-up max-w-lg mx-auto">
                <div class="inline-flex items-center justify-center w-20 h-20 rounded-2xl bg-gradient-to-br from-[#2D8CFF]/10 to-[#0E71EB]/10 border border-[#2D8CFF]/20 mb-6">
                    <svg class="w-10 h-10 text-[#2D8CFF]/60" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 10.5l4.72-4.72a.75.75 0 011.28.53v11.38a.75.75 0 01-1.28.53l-4.72-4.72M4.5 18.75h9a2.25 2.25 0 002.25-2.25v-9a2.25 2.25 0 00-2.25-2.25h-9A2.25 2.25 0 002.25 7.5v9a2.25 2.25 0 002.25 2.25z" />
                    </svg>
                </div>
                <h2 class="text-xl font-bold text-white mb-2">Belum ada akun Zoom</h2>
                <p class="text-white/40 text-sm mb-6 leading-relaxed">
                    Hubungkan akun Zoom Anda untuk mulai menjadwalkan meeting secara otomatis.
                    Anda bisa menambahkan beberapa akun sekaligus.
                </p>
                <a href="{{ route('zoom.create') }}" class="btn-primary inline-flex items-center gap-2" id="connect-zoom-empty-btn">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                    </svg>
                    Tambah Akun Pertama
                </a>
            </div>
        @endif
    </div>

    {{-- Delete Confirmation Modal --}}
    <div id="delete-modal" class="fixed inset-0 z-[100] hidden items-center justify-center p-4" role="dialog" aria-modal="true">
        {{-- Backdrop --}}
        <div class="absolute inset-0 bg-black/60 backdrop-blur-sm" onclick="closeDeleteModal()"></div>

        {{-- Modal --}}
        <div class="relative glass-card rounded-2xl p-6 w-full max-w-sm animate-scale-in">
            <div class="text-center">
                <div class="inline-flex items-center justify-center w-14 h-14 rounded-2xl bg-red-500/10 border border-red-500/20 mb-4">
                    <svg class="w-7 h-7 text-red-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" />
                    </svg>
                </div>
                <h3 class="text-lg font-bold text-white mb-1">Hapus Akun Zoom?</h3>
                <p class="text-white/40 text-sm mb-6">
                    Akun <span id="delete-account-name" class="text-white/60 font-medium"></span> akan dihapus beserta semua token dan kredensialnya. Tindakan ini tidak bisa dibatalkan.
                </p>

                <div class="flex items-center gap-3">
                    <button type="button" onclick="closeDeleteModal()" class="cursor-pointer flex-1 px-4 py-2.5 rounded-xl text-sm font-medium text-white/60 bg-white/5 hover:bg-white/10 border border-white/10 transition-all duration-200">
                        Batal
                    </button>
                    <form id="delete-form" method="POST" class="flex-1">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="cursor-pointer w-full px-4 py-2.5 rounded-xl text-sm font-medium text-white bg-red-500/80 hover:bg-red-500 transition-all duration-200 active:scale-[0.97]">
                            Hapus
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
        function confirmDelete(accountId, accountName) {
            const modal = document.getElementById('delete-modal');
            const form = document.getElementById('delete-form');
            const nameEl = document.getElementById('delete-account-name');

            form.action = `/zoom/${accountId}`;
            nameEl.textContent = `"${accountName}"`;
            modal.classList.remove('hidden');
            modal.classList.add('flex');
        }

        function closeDeleteModal() {
            const modal = document.getElementById('delete-modal');
            modal.classList.remove('flex');
            modal.classList.add('hidden');
        }

        // Close modal on Escape key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') closeDeleteModal();
        });

        function submitTelegramBtn(form, btnId, textId, loadingId) {
            const btn = document.getElementById(btnId);
            const text = document.getElementById(textId);
            const loading = document.getElementById(loadingId);

            btn.disabled = true;
            btn.classList.add('opacity-75', 'cursor-not-allowed');
            text.classList.add('hidden');
            loading.classList.remove('hidden');
        }
    </script>
</x-layouts.app>
