<x-layouts.app :title="'Buat Meeting Baru — Zoom Scheduler'">
    <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        {{-- Back button --}}
        <a href="{{ route('meetings.index') }}" class="inline-flex items-center gap-2 text-white/40 hover:text-white/70 text-sm mb-6 transition-colors duration-200">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18" />
            </svg>
            Kembali ke Daftar Meeting
        </a>

        {{-- Page Header --}}
        <div class="mb-8">
            <h1 class="text-2xl sm:text-3xl font-bold bg-gradient-to-r from-white to-white/60 bg-clip-text text-transparent">
                Buat Meeting Baru
            </h1>
            <p class="text-white/40 text-sm mt-1">
                Jadwalkan meeting instan atau terjadwal melalui akun Zoom Anda
            </p>
        </div>



        {{-- Meeting Form --}}
        <div class="glass-card rounded-2xl p-6 sm:p-8 animate-fade-in-up">
            <form method="POST" action="{{ route('meetings.store') }}" id="meeting-form">
                @csrf

                <div class="space-y-6">
                    {{-- Custom Zoom Account Dropdown --}}
                    <div class="relative" id="custom-select-container">
                        <label for="zoom_account_id_trigger" class="block text-sm font-medium text-white/60 mb-2">
                            Gunakan Akun Zoom
                        </label>
                        <input type="hidden" id="zoom_account_id" name="zoom_account_id" value="{{ old('zoom_account_id') }}" required>
                        
                        {{-- Trigger button --}}
                        <button type="button" onclick="toggleDropdown()" class="input-field text-left flex items-center justify-between cursor-pointer" id="dropdown-trigger">
                            <span id="dropdown-selected-text" class="text-white/30">Pilih Akun Zoom...</span>
                            <svg class="w-5 h-5 text-white/40 transition-transform duration-200" id="dropdown-arrow" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5" />
                            </svg>
                        </button>

                        {{-- Options List --}}
                        <div class="absolute z-[80] w-full mt-2 rounded-xl bg-[#0d0d1e]/95 border border-white/10 backdrop-blur-xl shadow-2xl py-1.5 hidden animate-scale-in" id="dropdown-menu">
                            {{-- Auto Option --}}
                            <button type="button" 
                                onclick="selectOption('auto', '✨ Otomatis Pilih Akun yang Kosong')"
                                class="w-full text-left px-4 py-3 text-sm text-[#2D8CFF] hover:bg-[#2D8CFF]/10 transition-colors flex items-center justify-between cursor-pointer font-medium border-b border-white/5">
                                <span>✨ Otomatis Pilih Akun yang Kosong</span>
                            </button>
                            @foreach($zoomAccounts as $account)
                                @php $inMeeting = $account->isCurrentlyInMeeting(); @endphp
                                <button type="button" 
                                    onclick="selectOption({{ $account->id }}, '{{ addslashes($account->label) }} ({{ $account->email }}) - [{{ $account->plan_type ?? 'Unknown Plan' }} - Max {{ $account->meeting_capacity ?? 100 }} Peserta]{{ $inMeeting ? ' - 🔴 SEDANG RAPAT' : '' }}')"
                                    class="w-full text-left px-4 py-2.5 text-sm text-white/70 hover:text-white hover:bg-white/5 transition-colors flex flex-col md:flex-row items-start md:items-center justify-between gap-2 cursor-pointer">
                                    <div class="flex items-start sm:items-center gap-2 min-w-0 w-full md:w-auto md:flex-1 pr-0 md:pr-4">
                                        <span class="break-words min-w-0">{{ $account->label }} <span class="text-white/40 text-xs">({{ $account->email }})</span></span>
                                    </div>
                                    <div class="flex flex-wrap items-center gap-1.5 shrink-0">
                                        @if($inMeeting)
                                            <span class="shrink-0 whitespace-nowrap text-[10px] px-1.5 py-0.5 rounded-full bg-red-500/10 border border-red-500/20 text-red-400 font-bold flex items-center gap-1">
                                                <span class="w-1.5 h-1.5 shrink-0 rounded-full bg-red-500 animate-pulse"></span>
                                                Sedang Rapat
                                            </span>
                                        @endif
                                        <span class="text-[10px] px-2 py-0.5 rounded bg-white/5 border border-white/5 text-white/40">{{ $account->plan_type ?? 'Unknown' }}</span>
                                        <span class="text-[10px] px-2 py-0.5 rounded bg-[#2D8CFF]/10 border border-[#2D8CFF]/20 text-[#2D8CFF] font-medium">{{ $account->meeting_capacity ?? 100 }} Peserta</span>
                                    </div>
                                </button>
                            @endforeach
                        </div>
                        
                        @error('zoom_account_id')
                            <p class="text-red-400 text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Topic --}}
                    <div>
                        <label for="topic" class="block text-sm font-medium text-white/60 mb-2">
                            Topik Meeting
                        </label>
                        <input type="text" id="topic" name="topic"
                            value="{{ old('topic', 'Rapat RSUD Blambangan') }}"
                            placeholder="Masukkan nama topik rapat"
                            class="input-field"
                            required>
                        @error('topic')
                            <p class="text-red-400 text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Agenda --}}
                    <div>
                        <label for="agenda" class="block text-sm font-medium text-white/60 mb-2">
                            Deskripsi / Agenda <span class="text-white/25">(Opsional)</span>
                        </label>
                        <textarea id="agenda" name="agenda" rows="3"
                            placeholder="Deskripsi singkat mengenai agenda meeting"
                            class="input-field">{{ old('agenda') }}</textarea>
                        @error('agenda')
                            <p class="text-red-400 text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Meeting Type Select --}}
                    <div>
                        <label class="block text-sm font-medium text-white/60 mb-3">
                            Jenis Meeting
                        </label>
                        <div class="grid grid-cols-2 gap-4">
                            {{-- Instant --}}
                            <label class="cursor-pointer relative flex flex-col p-4 rounded-xl border bg-white/[0.01] hover:bg-white/[0.03] transition-all duration-200 select-none" id="label-type-instant">
                                <input type="radio" name="type" value="1" class="sr-only" {{ old('type', '2') == '1' ? 'checked' : '' }} onchange="toggleType(1)">
                                <div class="flex items-start sm:items-center gap-3 mb-1">
                                    <div class="w-4 h-4 mt-0.5 sm:mt-0 shrink-0 rounded-full border border-[#2D8CFF] flex items-center justify-center" id="radio-dot-instant">
                                        <div class="w-2.5 h-2.5 rounded-full bg-[#2D8CFF] scale-0 transition-transform duration-200"></div>
                                    </div>
                                    <span class="text-white font-semibold text-sm">Instant Meeting</span>
                                </div>
                                <span class="text-white/30 text-xs pl-7 mt-1 sm:mt-0">Mulai meeting sekarang juga dengan tautan langsung</span>
                            </label>

                            {{-- Scheduled --}}
                            <label class="cursor-pointer relative flex flex-col p-4 rounded-xl border bg-white/[0.01] hover:bg-white/[0.03] transition-all duration-200 select-none" id="label-type-scheduled">
                                <input type="radio" name="type" value="2" class="sr-only" {{ old('type', '2') == '2' ? 'checked' : '' }} onchange="toggleType(2)">
                                <div class="flex items-start sm:items-center gap-3 mb-1">
                                    <div class="w-4 h-4 mt-0.5 sm:mt-0 shrink-0 rounded-full border border-[#2D8CFF] flex items-center justify-center" id="radio-dot-scheduled">
                                        <div class="w-2.5 h-2.5 rounded-full bg-[#2D8CFF] scale-0 transition-transform duration-200"></div>
                                    </div>
                                    <span class="text-white font-semibold text-sm">Scheduled Meeting</span>
                                </div>
                                <span class="text-white/30 text-xs pl-7">Jadwalkan rapat untuk waktu tertentu di masa mendatang</span>
                            </label>
                        </div>
                    </div>

                    {{-- Scheduled Fields Section --}}
                    <div id="scheduled-fields" class="space-y-6 pt-2 border-t border-white/5 transition-all duration-300">
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            {{-- Date --}}
                            <div>
                                <label for="start_date" class="block text-sm font-medium text-white/60 mb-2">
                                    Tanggal
                                </label>
                                <input type="text" id="start_date" name="start_date"
                                    value="{{ old('start_date', now()->format('Y-m-d')) }}"
                                    class="input-field cursor-pointer bg-white/[0.02]">
                                @error('start_date')
                                    <p class="text-red-400 text-xs mt-1">{{ $message }}</p>
                                @enderror
                            </div>

                            {{-- Time --}}
                            <div>
                                <label for="start_time" class="block text-sm font-medium text-white/60 mb-2">
                                    Waktu Mulai
                                </label>
                                <input type="text" id="start_time" name="start_time"
                                    value="{{ old('start_time', now()->format('H:i')) }}"
                                    class="input-field cursor-pointer bg-white/[0.02]">
                                @error('start_time')
                                    <p class="text-red-400 text-xs mt-1">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            {{-- Duration --}}
                            <div>
                                <label for="duration" class="block text-sm font-medium text-white/60 mb-2">
                                    Durasi Rapat <span class="text-white/20">(Menit)</span>
                                </label>
                                <select id="duration" name="duration" class="input-field cursor-pointer">
                                    <option value="15" {{ old('duration') == '15' ? 'selected' : '' }}>15 menit</option>
                                    <option value="30" {{ old('duration', '45') == '30' ? 'selected' : '' }}>30 menit</option>
                                    <option value="45" {{ old('duration', '45') == '45' ? 'selected' : '' }}>45 menit</option>
                                    <option value="60" {{ old('duration') == '60' ? 'selected' : '' }}>1 jam (60 mnt)</option>
                                    <option value="90" {{ old('duration') == '90' ? 'selected' : '' }}>1.5 jam (90 mnt)</option>
                                    <option value="120" {{ old('duration') == '120' ? 'selected' : '' }}>2 jam (120 mnt)</option>
                                    <option value="180" {{ old('duration') == '180' ? 'selected' : '' }}>3 jam (180 mnt)</option>
                                    <option value="240" {{ old('duration') == '240' ? 'selected' : '' }}>4 jam (240 mnt)</option>
                                    <option value="300" {{ old('duration') == '300' ? 'selected' : '' }}>5 jam (300 mnt)</option>
                                    <option value="360" {{ old('duration') == '360' ? 'selected' : '' }}>6 jam (360 mnt)</option>
                                    <option value="480" {{ old('duration') == '480' ? 'selected' : '' }}>8 jam (480 mnt)</option>
                                    <option value="600" {{ old('duration') == '600' ? 'selected' : '' }}>10 jam (600 mnt)</option>
                                    <option value="720" {{ old('duration') == '720' ? 'selected' : '' }}>12 jam (720 mnt)</option>
                                    <option value="1440" {{ old('duration') == '1440' ? 'selected' : '' }}>24 jam (1440 mnt)</option>
                                </select>
                                @error('duration')
                                    <p class="text-red-400 text-xs mt-1">{{ $message }}</p>
                                @enderror
                            </div>

                            {{-- Timezone --}}
                            <div>
                                <label for="timezone" class="block text-sm font-medium text-white/60 mb-2">
                                    Zona Waktu (Timezone)
                                </label>
                                <select id="timezone" name="timezone" class="input-field cursor-pointer">
                                    <option value="Asia/Jakarta" {{ old('timezone', 'Asia/Jakarta') == 'Asia/Jakarta' ? 'selected' : '' }}>WIB - Jakarta (GMT+7)</option>
                                    <option value="Asia/Makassar" {{ old('timezone') == 'Asia/Makassar' ? 'selected' : '' }}>WITA - Makassar (GMT+8)</option>
                                    <option value="Asia/Jayapura" {{ old('timezone') == 'Asia/Jayapura' ? 'selected' : '' }}>WIT - Jayapura (GMT+9)</option>
                                    <option value="UTC" {{ old('timezone') == 'UTC' ? 'selected' : '' }}>UTC (GMT+0)</option>
                                </select>
                                @error('timezone')
                                    <p class="text-red-400 text-xs mt-1">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>
                    </div>

                    {{-- Security Passcode --}}
                    <div>
                        <label for="password" class="block text-sm font-medium text-white/60 mb-2">
                            Passcode Meeting <span class="text-white/20">(Maks 10 karakter, Opsional)</span>
                        </label>
                        <input type="text" id="password" name="password"
                            value="{{ old('password') }}"
                            placeholder="Tinggalkan kosong untuk passcode otomatis"
                            maxlength="10"
                            class="input-field font-mono">
                        @error('password')
                            <p class="text-red-400 text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                {{-- Action Buttons --}}
                <div class="mt-8 flex items-center gap-3 border-t border-white/5 pt-6">
                    <a href="{{ route('meetings.index') }}" class="px-5 py-2.5 rounded-xl text-sm font-medium text-white/50 hover:text-white/70 hover:bg-white/5 transition-all duration-200">
                        Batal
                    </a>
                    <button type="submit" class="btn-primary flex-1 sm:flex-none relative" id="submit-btn">
                        <span id="submit-text" class="flex items-center justify-center gap-2">
                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v6m3-3H9m12 0a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            Buat Rapat Sekarang
                        </span>
                        <span id="submit-loading" class="hidden flex items-center justify-center gap-2">
                            <svg class="w-5 h-5 animate-spin" viewBox="0 0 24 24" fill="none">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                            </svg>
                            Membuat Rapat...
                        </span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function toggleType(typeId) {
            const scheduledFields = document.getElementById('scheduled-fields');
            const requiredFields = ['start_date', 'start_time', 'duration', 'timezone'];

            const radioInstant = document.getElementById('radio-dot-instant').querySelector('div');
            const radioScheduled = document.getElementById('radio-dot-scheduled').querySelector('div');

            const labelInstant = document.getElementById('label-type-instant');
            const labelScheduled = document.getElementById('label-type-scheduled');

            if (typeId === 1) {
                // Instant
                scheduledFields.classList.add('hidden');
                requiredFields.forEach(id => {
                    document.getElementById(id).removeAttribute('required');
                });

                radioInstant.classList.remove('scale-0');
                radioInstant.classList.add('scale-100');
                radioScheduled.classList.remove('scale-100');
                radioScheduled.classList.add('scale-0');

                labelInstant.classList.add('border-[#2D8CFF]', 'bg-[#2D8CFF]/5');
                labelScheduled.classList.remove('border-[#2D8CFF]', 'bg-[#2D8CFF]/5');
                labelScheduled.classList.add('border-white/10');
            } else {
                // Scheduled
                scheduledFields.classList.remove('hidden');
                requiredFields.forEach(id => {
                    document.getElementById(id).setAttribute('required', 'required');
                });

                radioScheduled.classList.remove('scale-0');
                radioScheduled.classList.add('scale-100');
                radioInstant.classList.remove('scale-100');
                radioInstant.classList.add('scale-0');

                labelScheduled.classList.add('border-[#2D8CFF]', 'bg-[#2D8CFF]/5');
                labelInstant.classList.remove('border-[#2D8CFF]', 'bg-[#2D8CFF]/5');
                labelInstant.classList.add('border-white/10');
            }
        }

        function toggleDropdown() {
            const menu = document.getElementById('dropdown-menu');
            const arrow = document.getElementById('dropdown-arrow');
            
            if (menu.classList.contains('hidden')) {
                menu.classList.remove('hidden');
                menu.classList.add('block');
                arrow.classList.add('rotate-180');
            } else {
                menu.classList.remove('block');
                menu.classList.add('hidden');
                arrow.classList.remove('rotate-180');
            }
        }

        function selectOption(id, text) {
            document.getElementById('zoom_account_id').value = id;
            
            const selectedTextEl = document.getElementById('dropdown-selected-text');
            selectedTextEl.textContent = text;
            selectedTextEl.classList.remove('text-white/30');
            selectedTextEl.classList.add('text-white');
            
            const menu = document.getElementById('dropdown-menu');
            const arrow = document.getElementById('dropdown-arrow');
            menu.classList.remove('block');
            menu.classList.add('hidden');
            arrow.classList.remove('rotate-180');
        }

        // Close dropdown when clicking outside
        document.addEventListener('click', function(e) {
            const container = document.getElementById('custom-select-container');
            const menu = document.getElementById('dropdown-menu');
            const arrow = document.getElementById('dropdown-arrow');
            
            if (container && !container.contains(e.target) && menu && !menu.classList.contains('hidden')) {
                menu.classList.remove('block');
                menu.classList.add('hidden');
                arrow.classList.remove('rotate-180');
            }
        });

        // Initialize state on load
        document.addEventListener('DOMContentLoaded', () => {
            const selectedType = document.querySelector('input[name="type"]:checked').value;
            toggleType(parseInt(selectedType));

            // Restore old selected option if it exists
            const oldId = "{{ old('zoom_account_id') }}";
            if (oldId) {
                if (oldId === 'auto') {
                    selectOption('auto', '✨ Otomatis Pilih Akun yang Kosong');
                } else {
                    const options = @json($zoomAccounts);
                    const account = options.find(a => a.id == oldId);
                    if (account) {
                        selectOption(account.id, `${account.account_name || account.display_name || 'Zoom Account'} (${account.email}) - [${account.plan_type || 'Unknown Plan'} - Max ${account.meeting_capacity || 100} Peserta]`);
                    }
                }
            }
        });

        document.getElementById('meeting-form').addEventListener('submit', function () {
            const btn = document.getElementById('submit-btn');
            const text = document.getElementById('submit-text');
            const loading = document.getElementById('submit-loading');

            btn.disabled = true;
            text.classList.add('hidden');
            loading.classList.remove('hidden');
        });
    </script>

    {{-- Flatpickr for forced 24h format & consistent UI --}}
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/themes/dark.css">
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script>
        // Date Picker
        flatpickr("#start_date", {
            dateFormat: "Y-m-d",
            minDate: "today",
            disableMobile: true, // Fix iOS native picker layout issues
        });

        // Time Picker
        flatpickr("#start_time", {
            enableTime: true,
            noCalendar: true,
            dateFormat: "H:i",
            time_24hr: true,
            defaultDate: "{{ now()->format('H:i') }}",
            minuteIncrement: 5,
            disableMobile: true, // Fix iOS native picker layout issues
        });
    </script>
</x-layouts.app>
