<x-layouts.app :title="'Profil Zoom ' . $zoomAccount->label . ' — Zoom Scheduler'">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        {{-- Back button --}}
        <a href="{{ route('dashboard') }}" class="inline-flex items-center gap-2 text-white/40 hover:text-white/70 text-sm mb-6 transition-colors duration-200">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18" />
            </svg>
            Kembali ke Dashboard
        </a>

        {{-- Profile Header --}}
        <div class="glass-card rounded-2xl p-6 sm:p-8 mb-8 animate-fade-in-up">
            <div class="flex flex-col sm:flex-row items-center gap-6">
                {{-- Avatar --}}
                <div class="relative shrink-0">
                    @if(!empty($profile['pic_url']))
                        <img src="{{ $profile['pic_url'] }}" alt="Profile Picture" class="w-24 h-24 rounded-2xl border border-white/10 shadow-xl object-cover">
                    @else
                        <div class="w-24 h-24 rounded-2xl bg-gradient-to-br from-[#2D8CFF]/20 to-[#0E71EB]/20 border border-[#2D8CFF]/20 flex items-center justify-center text-3xl font-bold text-[#2D8CFF] shadow-xl">
                            {{ strtoupper(substr($profile['first_name'] ?? 'Z', 0, 1)) }}
                        </div>
                    @endif
                    <div class="absolute -bottom-2 -right-2 px-2 py-0.5 rounded bg-emerald-500/90 text-[10px] font-bold text-white uppercase tracking-wider shadow">
                        {{ ($profile['type'] ?? 1) == 2 ? 'Licensed' : 'Basic' }}
                    </div>
                </div>

                {{-- Account Name & Email --}}
                <div class="text-center sm:text-left flex-1">
                    <h1 class="text-2xl sm:text-3xl font-bold text-white mb-1">
                        {{ $profile['display_name'] ?? (($profile['first_name'] ?? '') . ' ' . ($profile['last_name'] ?? '')) }}
                    </h1>
                    <p class="text-white/40 text-sm font-medium mb-3">{{ $profile['email'] ?? 'No email' }}</p>
                    <div class="flex flex-wrap justify-center sm:justify-start gap-2">
                        <span class="px-2.5 py-0.5 rounded-md text-[10px] font-medium bg-white/5 border border-white/10 text-white/50">
                            Dept: {{ $profile['dept'] ?? 'Umum' }}
                        </span>
                        <span class="px-2.5 py-0.5 rounded-md text-[10px] font-medium bg-white/5 border border-white/10 text-white/50">
                            ID: {{ $profile['id'] ?? '-' }}
                        </span>
                    </div>
                </div>
            </div>
        </div>

        {{-- Form Edit Profil --}}
        <form method="POST" action="{{ route('zoom.profile.update', $zoomAccount) }}" id="profile-form">
            @csrf
            @method('PUT')

            <div class="space-y-8 animate-fade-in-up" style="animation-delay: 100ms">
                {{-- Section 1: Pribadi --}}
                <div class="glass-card rounded-2xl overflow-hidden border border-white/5">
                    <div class="bg-white/[0.02] border-b border-white/5 px-6 py-4">
                        <h2 class="text-base font-bold text-white flex items-center gap-2">
                            <svg class="w-4 h-4 text-[#2D8CFF]" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z" />
                            </svg>
                            Pribadi
                        </h2>
                    </div>

                    <div class="p-6 space-y-6">
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                            {{-- First Name --}}
                            <div>
                                <label for="first_name" class="block text-xs font-semibold text-white/40 uppercase tracking-wider mb-2">Nama Depan <span class="text-red-400">*</span></label>
                                <input type="text" id="first_name" name="first_name" value="{{ old('first_name', $profile['first_name'] ?? '') }}" class="input-field" required>
                                @error('first_name')
                                    <p class="text-red-400 text-xs mt-1">{{ $message }}</p>
                                @enderror
                            </div>

                            {{-- Last Name --}}
                            <div>
                                <label for="last_name" class="block text-xs font-semibold text-white/40 uppercase tracking-wider mb-2">Nama Belakang</label>
                                <input type="text" id="last_name" name="last_name" value="{{ old('last_name', $profile['last_name'] ?? '') }}" class="input-field">
                                @error('last_name')
                                    <p class="text-red-400 text-xs mt-1">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                            {{-- Display Name --}}
                            <div>
                                <label for="display_name" class="block text-xs font-semibold text-white/40 uppercase tracking-wider mb-2">Nama Tampilan / Alias <span class="text-red-400">*</span></label>
                                <input type="text" id="display_name" name="display_name" value="{{ old('display_name', $profile['display_name'] ?? '') }}" class="input-field" required>
                                @error('display_name')
                                    <p class="text-red-400 text-xs mt-1">{{ $message }}</p>
                                @enderror
                            </div>

                            {{-- Phone Number --}}
                            <div>
                                <label for="phone_number" class="block text-xs font-semibold text-white/40 uppercase tracking-wider mb-2">Nomor Telepon</label>
                                <input type="text" id="phone_number" name="phone_number" value="{{ old('phone_number', $profile['phone_number'] ?? '') }}" placeholder="Belum diatur" class="input-field">
                                @error('phone_number')
                                    <p class="text-red-400 text-xs mt-1">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>

                        {{-- Timezone --}}
                        <div>
                            <label for="timezone" class="block text-xs font-semibold text-white/40 uppercase tracking-wider mb-2">Zona Waktu <span class="text-red-400">*</span></label>
                            <select id="timezone" name="timezone" class="input-field cursor-pointer" required>
                                <option value="Asia/Jakarta" {{ old('timezone', $profile['timezone'] ?? '') == 'Asia/Jakarta' ? 'selected' : '' }}>(GMT+07:00) Jakarta</option>
                                <option value="Asia/Makassar" {{ old('timezone', $profile['timezone'] ?? '') == 'Asia/Makassar' ? 'selected' : '' }}>(GMT+08:00) Makassar</option>
                                <option value="Asia/Jayapura" {{ old('timezone', $profile['timezone'] ?? '') == 'Asia/Jayapura' ? 'selected' : '' }}>(GMT+09:00) Jayapura</option>
                                <option value="Asia/Singapore" {{ old('timezone', $profile['timezone'] ?? '') == 'Asia/Singapore' ? 'selected' : '' }}>(GMT+08:00) Singapore</option>
                                <option value="UTC" {{ old('timezone', $profile['timezone'] ?? '') == 'UTC' ? 'selected' : '' }}>(GMT+00:00) UTC</option>
                            </select>
                            @error('timezone')
                                <p class="text-red-400 text-xs mt-1">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>
                </div>

                {{-- Section 2: Rapat --}}
                <div class="glass-card rounded-2xl overflow-hidden border border-white/5">
                    <div class="bg-white/[0.02] border-b border-white/5 px-6 py-4">
                        <h2 class="text-base font-bold text-white flex items-center gap-2">
                            <svg class="w-4 h-4 text-[#2D8CFF]" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 8.25v-1.5m0 1.5c-1.355 0-2.697.056-4.024.166C6.845 8.51 6 9.473 6 10.608v2.513m6-4.871c1.355 0 2.697.056 4.024.166C17.155 8.51 18 9.473 18 10.608v2.513M15 8.25v-1.5m-6 1.5v-1.5m12 9.75-1.5-1.5M3 16.5l1.5-1.5M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z" />
                            </svg>
                            Rapat
                        </h2>
                    </div>

                    <div class="p-6 space-y-6">
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                            {{-- Personal Meeting ID (PMI) --}}
                            <div>
                                <label for="pmi" class="block text-xs font-semibold text-white/40 uppercase tracking-wider mb-2">ID Rapat Pribadi (PMI)</label>
                                <input type="text" id="pmi" name="pmi" value="{{ old('pmi', $profile['pmi'] ?? '') }}" placeholder="Belum diatur" class="input-field font-mono">
                                <p class="text-[10px] text-white/30 mt-1.5">PMI harus berupa angka sepanjang 10-11 digit.</p>
                                @error('pmi')
                                    <p class="text-red-400 text-xs mt-1">{{ $message }}</p>
                                @enderror
                            </div>

                            {{-- Host Key --}}
                            <div>
                                <label for="host_key" class="block text-xs font-semibold text-white/40 uppercase tracking-wider mb-2">Kunci Host (Host Key)</label>
                                <div class="relative">
                                    <input type="password" id="host_key" name="host_key" value="" placeholder="•••••• (Disembunyikan oleh Zoom)" class="input-field pr-10 font-mono tracking-widest transition-all duration-200" maxlength="6">
                                    <button type="button" onclick="toggleHostKey()" class="absolute inset-y-0 right-0 pr-3 flex items-center text-white/40 hover:text-white transition-colors" id="toggle-host-key-btn">
                                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" id="eye-icon">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z" />
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                        </svg>
                                    </button>
                                </div>
                                <p class="text-[10px] text-white/30 mt-1.5 leading-relaxed">
                                    <span class="text-amber-400/80 font-medium">Catatan Keamanan:</span> Zoom tidak mengizinkan pengambilan data Kunci Host via API (Write-Only). Kolom ini akan selalu kosong demi keamanan, namun Anda tetap dapat mengubahnya dengan memasukkan 6 digit PIN baru di atas.
                                </p>
                                @error('host_key')
                                    <p class="text-red-400 text-xs mt-1">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Action Buttons --}}
                <div class="flex items-center justify-end gap-4 pt-2">
                    <a href="{{ route('dashboard') }}" class="px-6 py-3 rounded-xl text-sm font-semibold text-white/60 bg-white/5 hover:bg-white/10 border border-white/10 transition-all duration-200">
                        Batal
                    </a>
                    <button type="submit" class="btn-primary px-8 py-3 rounded-xl text-sm font-semibold flex items-center justify-center gap-2" id="submit-btn">
                        <span id="submit-text">Simpan Perubahan</span>
                        <svg class="w-4 h-4 animate-spin hidden" id="submit-loading" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                    </button>
                </div>
            </div>
        </form>
    </div>

    <script>
        function toggleHostKey() {
            const input = document.getElementById('host_key');
            const icon = document.getElementById('eye-icon');
            
            if (input.type === 'password') {
                input.type = 'text';
                icon.innerHTML = '<path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 001.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.45 10.45 0 0112 4.5c4.756 0 8.773 3.162 10.065 7.498a10.523 10.523 0 01-4.293 5.774M6.228 6.228L3 3m3.228 3.228l3.65 3.65m7.815 7.815L21 21m-3.956-3.956l-3.09-3.09m0 0a3 3 0 10-4.243-4.243m4.242 4.242L9.88 9.88" />';
            } else {
                input.type = 'password';
                icon.innerHTML = '<path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z" /><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />';
            }
        }

        document.getElementById('profile-form').addEventListener('submit', function () {
            const btn = document.getElementById('submit-btn');
            const text = document.getElementById('submit-text');
            const loading = document.getElementById('submit-loading');

            btn.disabled = true;
            text.classList.add('hidden');
            loading.classList.remove('hidden');
        });

        document.addEventListener('DOMContentLoaded', function() {
            const hostKeyInput = document.getElementById('host_key');
            if (hostKeyInput) {
                hostKeyInput.addEventListener('keydown', function(e) {
                    if (['Backspace', 'Delete', 'ArrowLeft', 'ArrowRight', 'Tab', 'Enter'].includes(e.key) || e.ctrlKey || e.metaKey) return;

                    if (!/^\d$/.test(e.key) || (this.value.length >= 6 && this.selectionStart === this.selectionEnd)) {
                        e.preventDefault();
                        triggerHostKeyError(this);
                    }
                });

                hostKeyInput.addEventListener('paste', function(e) {
                    e.preventDefault();
                    const paste = (e.clipboardData || window.clipboardData).getData('text');
                    const digitsOnly = paste.replace(/\D/g, '');
                    const newText = this.value.substring(0, this.selectionStart) + digitsOnly + this.value.substring(this.selectionEnd);
                    if (newText.length > 6) {
                        this.value = newText.substring(0, 6);
                        triggerHostKeyError(this);
                    } else {
                        this.value = newText;
                        if (paste !== digitsOnly) triggerHostKeyError(this);
                    }
                });
            }

            function triggerHostKeyError(input) {
                input.classList.remove('input-error-shake');
                void input.offsetWidth;
                input.classList.add('input-error-shake');
                setTimeout(() => {
                    input.classList.remove('input-error-shake');
                }, 400);
            }
        });
    </script>
</x-layouts.app>
