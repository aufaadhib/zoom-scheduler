<x-layouts.app :title="'Tambah Akun Zoom — Zoom Scheduler'">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        {{-- Back button --}}
        <a href="{{ route('dashboard') }}" class="inline-flex items-center gap-2 text-white/40 hover:text-white/70 text-sm mb-6 transition-colors duration-200">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18" />
            </svg>
            Kembali ke Dashboard
        </a>

        {{-- Page Header --}}
        <div class="mb-8">
            <h1 class="text-2xl sm:text-3xl font-bold bg-gradient-to-r from-white to-white/60 bg-clip-text text-transparent">
                Hubungkan Akun Zoom
            </h1>
            <p class="text-white/40 text-sm mt-1">
                Ikuti tutorial di bawah, lalu masukkan kredensial OAuth Anda
            </p>
        </div>

        {{-- Tutorial Section --}}
        <div class="glass-card rounded-2xl p-6 sm:p-8 mb-8 animate-fade-in-up">
            <div class="flex items-center gap-3 mb-6">
                <div class="w-10 h-10 rounded-xl bg-[#2D8CFF]/10 border border-[#2D8CFF]/20 flex items-center justify-center">
                    <svg class="w-5 h-5 text-[#2D8CFF]" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 6.042A8.967 8.967 0 006 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 016 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 016-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0018 18a8.967 8.967 0 00-6 2.292m0-14.25v14.25" />
                    </svg>
                </div>
                <div>
                    <h2 class="text-lg font-bold text-white">Tutorial: Membuat General App di Zoom</h2>
                    <p class="text-white/30 text-xs">Ikuti langkah-langkah berikut untuk mendapatkan kredensial</p>
                </div>
            </div>

            <div class="space-y-5">
                {{-- Step 1 --}}
                <div class="flex gap-4">
                    <div class="shrink-0 w-8 h-8 rounded-full bg-[#2D8CFF]/10 border border-[#2D8CFF]/20 flex items-center justify-center text-[#2D8CFF] text-sm font-bold">1</div>
                    <div class="flex-1 pt-1">
                        <p class="text-white/80 text-sm font-medium mb-1">Buka Zoom App Marketplace</p>
                        <p class="text-white/40 text-sm">
                            Login ke <a href="https://marketplace.zoom.us/" target="_blank" rel="noopener" class="text-[#2D8CFF] hover:underline">marketplace.zoom.us</a>
                            menggunakan akun Zoom yang ingin Anda hubungkan.
                        </p>
                    </div>
                </div>

                {{-- Step 2 --}}
                <div class="flex gap-4">
                    <div class="shrink-0 w-8 h-8 rounded-full bg-[#2D8CFF]/10 border border-[#2D8CFF]/20 flex items-center justify-center text-[#2D8CFF] text-sm font-bold">2</div>
                    <div class="flex-1 pt-1">
                        <p class="text-white/80 text-sm font-medium mb-1">Buat Aplikasi Baru</p>
                        <p class="text-white/40 text-sm">
                            Klik menu <strong class="text-white/60">Develop</strong> → <strong class="text-white/60">Build App</strong>. Pilih tipe <strong class="text-[#2D8CFF]">General App</strong>, lalu berikan nama aplikasi (contoh: "Zoom Scheduler").
                        </p>
                    </div>
                </div>

                {{-- Step 3 --}}
                <div class="flex gap-4">
                    <div class="shrink-0 w-8 h-8 rounded-full bg-[#2D8CFF]/10 border border-[#2D8CFF]/20 flex items-center justify-center text-[#2D8CFF] text-sm font-bold">3</div>
                    <div class="flex-1 pt-1">
                        <p class="text-white/80 text-sm font-medium mb-1">Atur Redirect URL</p>
                        <p class="text-white/40 text-sm">
                            Di tab <strong class="text-white/60">App Credentials</strong> atau <strong class="text-white/60">Local Test</strong>, isi bagian <strong class="text-white/60">Redirect URL for OAuth</strong> dengan:
                        </p>
                        <div class="mt-2 flex items-center gap-2">
                            <code class="px-3 py-1.5 rounded-lg bg-white/5 border border-white/10 text-xs text-emerald-400 font-mono select-all" id="redirect-url">{{ url('/zoom/callback') }}</code>
                            <button type="button" onclick="copyUrl()" class="cursor-pointer px-2 py-1.5 rounded-lg bg-white/5 hover:bg-white/10 border border-white/10 text-white/40 hover:text-white/60 text-xs transition-all" id="copy-btn">
                                Salin
                            </button>
                        </div>
                        <p class="text-white/30 text-xs mt-2">Tambahkan URL yang sama juga ke <strong class="text-white/50">OAuth allow list</strong>.</p>
                    </div>
                </div>

                {{-- Step 4 --}}
                <div class="flex gap-4">
                    <div class="shrink-0 w-8 h-8 rounded-full bg-[#2D8CFF]/10 border border-[#2D8CFF]/20 flex items-center justify-center text-[#2D8CFF] text-sm font-bold">4</div>
                    <div class="flex-1 pt-1">
                        <p class="text-white/80 text-sm font-medium mb-1">Tambahkan Scopes</p>
                        <p class="text-white/40 text-sm mb-3">
                            Di tab <strong class="text-white/60">Scopes</strong>, klik <strong class="text-white/60">+ Add Scopes</strong>. Cari dan centang scope berikut sesuai tipe aplikasi Anda:
                        </p>
                        
                        <div class="space-y-3">
                            <div>
                                <p class="text-[10px] font-bold text-[#2D8CFF] uppercase tracking-wider mb-1.5">Pilihan 1: Server-to-Server OAuth (Account-level)</p>
                                <div class="flex flex-wrap gap-2">
                                    <span class="px-2 py-0.5 rounded bg-white/5 border border-white/10 text-xs text-white/60 font-mono">user:write:admin</span>
                                    <span class="px-2 py-0.5 rounded bg-white/5 border border-white/10 text-xs text-white/60 font-mono">user:read:admin</span>
                                    <span class="px-2 py-0.5 rounded bg-white/5 border border-white/10 text-xs text-white/60 font-mono">meeting:write:admin</span>
                                    <span class="px-2 py-0.5 rounded bg-white/5 border border-white/10 text-xs text-white/60 font-mono">meeting:read:admin</span>
                                </div>
                            </div>
                            
                            <div>
                                <p class="text-[10px] font-bold text-[#2D8CFF] uppercase tracking-wider mb-1.5">Pilihan 2: OAuth App Biasa (User-level)</p>
                                <div class="flex flex-wrap gap-2">
                                    <span class="px-2 py-0.5 rounded bg-white/5 border border-white/10 text-xs text-white/60 font-mono">user:write</span>
                                    <span class="px-2 py-0.5 rounded bg-white/5 border border-white/10 text-xs text-white/60 font-mono">user:read</span>
                                    <span class="px-2 py-0.5 rounded bg-white/5 border border-white/10 text-xs text-white/60 font-mono">meeting:write</span>
                                    <span class="px-2 py-0.5 rounded bg-white/5 border border-white/10 text-xs text-white/60 font-mono">meeting:read</span>
                                </div>
                            </div>
                        </div>

                        <p class="text-white/30 text-xs mt-3">Klik <strong class="text-white/50">Done</strong> lalu <strong class="text-white/50">Save</strong>.</p>
                    </div>
                </div>

                {{-- Step 5 --}}
                <div class="flex gap-4">
                    <div class="shrink-0 w-8 h-8 rounded-full bg-emerald-500/10 border border-emerald-500/20 flex items-center justify-center text-emerald-400 text-sm font-bold">5</div>
                    <div class="flex-1 pt-1">
                        <p class="text-white/80 text-sm font-medium mb-1">Salin Client ID & Client Secret</p>
                        <p class="text-white/40 text-sm">
                            Kembali ke tab <strong class="text-white/60">App Credentials</strong>. Salin
                            <strong class="text-white/60">Client ID</strong> dan
                            <strong class="text-white/60">Client Secret</strong>,
                            lalu masukkan ke form di bawah.
                        </p>
                    </div>
                </div>
            </div>
        </div>



        {{-- Credential Input Form --}}
        <div class="glass-card rounded-2xl p-6 sm:p-8 animate-fade-in-up" style="animation-delay: 100ms">
            <div class="flex items-center gap-3 mb-6">
                <div class="w-10 h-10 rounded-xl bg-emerald-500/10 border border-emerald-500/20 flex items-center justify-center">
                    <svg class="w-5 h-5 text-emerald-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 5.25a3 3 0 013 3m3 0a6 6 0 01-7.029 5.912c-.563-.097-1.159.026-1.563.43L10.5 17.25H8.25v2.25H6v2.25H2.25v-2.818c0-.597.237-1.17.659-1.591l6.499-6.499c.404-.404.527-1 .43-1.563A6 6 0 1121.75 8.25z" />
                    </svg>
                </div>
                <div>
                    <h2 class="text-lg font-bold text-white">Masukkan Kredensial</h2>
                    <p class="text-white/30 text-xs">Setelah submit, Anda akan diarahkan ke Zoom untuk login</p>
                </div>
            </div>

            <form method="POST" action="{{ route('zoom.store') }}" id="zoom-form">
                @csrf

                <div class="space-y-5">
                    {{-- Account Name --}}
                    <div>
                        <label for="account_name" class="block text-sm font-medium text-white/60 mb-2">
                            Nama Akun <span class="text-white/20">(label untuk membedakan akun)</span>
                        </label>
                        <input type="text" id="account_name" name="account_name"
                            value="{{ old('account_name') }}"
                            placeholder="contoh: Zoom Kantor, Zoom Pribadi, dst."
                            class="input-field"
                            required>
                        @error('account_name')
                            <p class="text-red-400 text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Client ID --}}
                    <div>
                        <label for="client_id" class="block text-sm font-medium text-white/60 mb-2">
                            Client ID
                        </label>
                        <input type="text" id="client_id" name="client_id"
                            value="{{ old('client_id') }}"
                            placeholder="Masukkan Client ID dari Zoom"
                            class="input-field font-mono"
                            required>
                        @error('client_id')
                            <p class="text-red-400 text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Client Secret --}}
                    <div>
                        <label for="client_secret" class="block text-sm font-medium text-white/60 mb-2">
                            Client Secret
                        </label>
                        <div class="relative">
                            <input type="password" id="client_secret" name="client_secret"
                                value="{{ old('client_secret') }}"
                                placeholder="Masukkan Client Secret dari Zoom"
                                class="input-field font-mono pr-12"
                                required>
                            <button type="button" onclick="toggleSecret()" class="cursor-pointer absolute right-3 top-1/2 -translate-y-1/2 text-white/30 hover:text-white/60 transition-colors">
                                <svg id="eye-icon" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                </svg>
                                <svg id="eye-off-icon" class="w-5 h-5 hidden" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 001.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.45 10.45 0 0112 4.5c4.756 0 8.773 3.162 10.065 7.498a10.523 10.523 0 01-4.293 5.774M6.228 6.228L3 3m3.228 3.228l3.65 3.65m7.894 7.894L21 21m-3.228-3.228l-3.65-3.65m0 0a3 3 0 10-4.243-4.243m4.242 4.242L9.88 9.88" />
                                </svg>
                            </button>
                        </div>
                        @error('client_secret')
                            <p class="text-red-400 text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                {{-- Info Notice --}}
                <div class="mt-6 flex items-start gap-2.5 px-4 py-3 rounded-xl bg-[#2D8CFF]/5 border border-[#2D8CFF]/10">
                    <svg class="w-4 h-4 text-[#2D8CFF]/60 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M11.25 11.25l.041-.02a.75.75 0 011.063.852l-.708 2.836a.75.75 0 001.063.853l.041-.021M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-9-3.75h.008v.008H12V8.25z" />
                    </svg>
                    <p class="text-white/30 text-xs leading-relaxed">
                        Setelah Anda klik <strong class="text-white/50">"Hubungkan"</strong>, Anda akan diarahkan ke halaman login Zoom untuk memberikan izin akses. Kredensial Anda akan <strong class="text-white/50">dienkripsi</strong> sebelum disimpan.
                    </p>
                </div>

                {{-- Submit --}}
                <div class="mt-8 flex items-center gap-3">
                    <a href="{{ route('dashboard') }}" class="px-5 py-2.5 rounded-xl text-sm font-medium text-white/50 hover:text-white/70 hover:bg-white/5 transition-all duration-200">
                        Batal
                    </a>
                    <button type="submit" class="btn-primary flex-1 sm:flex-none relative" id="submit-btn">
                        <span id="submit-text" class="flex items-center justify-center gap-2">
                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M13.19 8.688a4.5 4.5 0 011.242 7.244l-4.5 4.5a4.5 4.5 0 01-6.364-6.364l1.757-1.757m13.35-.622l1.757-1.757a4.5 4.5 0 00-6.364-6.364l-4.5 4.5a4.5 4.5 0 001.242 7.244" />
                            </svg>
                            Hubungkan Akun Zoom
                        </span>
                        <span id="submit-loading" class="hidden flex items-center justify-center gap-2">
                            <svg class="w-5 h-5 animate-spin" viewBox="0 0 24 24" fill="none">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                            </svg>
                            Menghubungkan...
                        </span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function toggleSecret() {
            const input = document.getElementById('client_secret');
            const eyeIcon = document.getElementById('eye-icon');
            const eyeOffIcon = document.getElementById('eye-off-icon');

            if (input.type === 'password') {
                input.type = 'text';
                eyeIcon.classList.add('hidden');
                eyeOffIcon.classList.remove('hidden');
            } else {
                input.type = 'password';
                eyeIcon.classList.remove('hidden');
                eyeOffIcon.classList.add('hidden');
            }
        }

        function copyUrl() {
            const url = document.getElementById('redirect-url').textContent;
            navigator.clipboard.writeText(url).then(() => {
                const btn = document.getElementById('copy-btn');
                btn.textContent = 'Tersalin!';
                btn.classList.add('text-emerald-400');
                setTimeout(() => {
                    btn.textContent = 'Salin';
                    btn.classList.remove('text-emerald-400');
                }, 2000);
            });
        }

        document.getElementById('zoom-form').addEventListener('submit', function () {
            const btn = document.getElementById('submit-btn');
            const text = document.getElementById('submit-text');
            const loading = document.getElementById('submit-loading');

            btn.disabled = true;
            text.classList.add('hidden');
            loading.classList.remove('hidden');
        });
    </script>
</x-layouts.app>
