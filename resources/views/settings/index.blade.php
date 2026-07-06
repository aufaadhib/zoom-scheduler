<x-layouts.app :title="'Pengaturan — Zoom Scheduler'">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">

        {{-- Page Header --}}
        <div class="mb-8">
            <h1 class="text-2xl sm:text-3xl font-bold bg-gradient-to-r from-white to-white/60 bg-clip-text text-transparent">
                Pengaturan
            </h1>
            <p class="text-white/40 text-sm mt-1">
                Kelola preferensi akun dan integrasi layanan Anda.
            </p>
        </div>

        <div class="flex flex-col lg:flex-row gap-6">

            {{-- Sidebar Tabs (Desktop) --}}
            <aside class="w-full lg:w-64 shrink-0">
                <nav class="glass-card rounded-2xl p-2 flex lg:flex-col gap-1">
                    <a href="{{ route('settings.index', ['tab' => 'integrations']) }}"
                        class="settings-tab-link flex items-center gap-3 px-4 py-3 rounded-xl text-sm font-semibold transition-all duration-200 {{ $tab === 'integrations' ? 'bg-[#2D8CFF]/10 text-[#2D8CFF]' : 'text-white/50 hover:text-white hover:bg-white/5' }}">
                        <svg class="w-5 h-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M13.19 8.688a4.5 4.5 0 011.242 7.244l-4.5 4.5a4.5 4.5 0 01-6.364-6.364l1.757-1.757m13.35-.622l1.757-1.757a4.5 4.5 0 00-6.364-6.364l-4.5 4.5a4.5 4.5 0 001.242 7.244" />
                        </svg>
                        <span class="truncate">Integrasi</span>
                    </a>
                    {{-- Placeholder for future settings tabs --}}
                    {{-- <a href="{{ route('settings.index', ['tab' => 'notifications']) }}" ... > --}}
                    {{-- <a href="{{ route('settings.index', ['tab' => 'profile']) }}" ... > --}}
                </nav>
            </aside>

            {{-- Main Content Panel --}}
            <div class="flex-1 min-w-0">

                {{-- === TAB: Integrations === --}}
                @if($tab === 'integrations')
                    <div class="animate-fade-in-up">
                        <div class="glass-card rounded-2xl p-6 sm:p-8">
                            @php
                                $callbackStatusConfigs = [
                                    'active' => [
                                        'label' => 'Aktif',
                                        'description' => 'Zoom sudah pernah memanggil endpoint callback akun ini.',
                                        'classes' => 'bg-emerald-500/10 text-emerald-300 border-emerald-500/20',
                                        'iconClasses' => 'text-emerald-400',
                                    ],
                                    'unconfigured' => [
                                        'label' => 'Belum lengkap',
                                        'description' => 'Lengkapi URL publik HTTPS dan Secret Token untuk akun ini.',
                                        'classes' => 'bg-amber-500/10 text-amber-300 border-amber-500/20',
                                        'iconClasses' => 'text-amber-300',
                                    ],
                                    'pending_zoom' => [
                                        'label' => 'Belum disetting di Zoom',
                                        'description' => 'Konfigurasi lokal siap, tetapi Zoom belum memvalidasi endpoint akun ini.',
                                        'classes' => 'bg-amber-500/10 text-amber-300 border-amber-500/20',
                                        'iconClasses' => 'text-amber-300',
                                    ],
                                    'disabled' => [
                                        'label' => 'Nonaktif',
                                        'description' => 'Event Zoom untuk akun ini tidak akan diproses.',
                                        'classes' => 'bg-white/5 text-white/60 border-white/10',
                                        'iconClasses' => 'text-white/50',
                                    ],
                                ];
                            @endphp

                            {{-- Zoom Callback Settings --}}
                            <section class="mb-8 pb-8 border-b border-white/5">
                                <div class="flex items-start gap-4 mb-6">
                                    <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-[#2D8CFF]/20 to-[#0E71EB]/10 border border-[#2D8CFF]/20 flex items-center justify-center shrink-0">
                                        <svg class="w-6 h-6 text-[#2D8CFF]" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M7.5 7.5h-.75A2.25 2.25 0 0 0 4.5 9.75v7.5A2.25 2.25 0 0 0 6.75 19.5h7.5a2.25 2.25 0 0 0 2.25-2.25v-.75M15 4.5h4.5m0 0V9m0-4.5L9 15" />
                                        </svg>
                                    </div>
                                    <div>
                                        <h2 class="text-lg font-bold text-white">Zoom Callback per Akun</h2>
                                        <p class="text-white/40 text-sm mt-1 max-w-2xl">Setiap akun Zoom punya endpoint dan Secret Token sendiri dari Zoom Marketplace.</p>
                                    </div>
                                </div>

                                @if($zoomAccounts->isEmpty())
                                    <div class="rounded-xl border border-white/10 bg-white/[0.02] p-6 text-center">
                                        <h3 class="text-white font-semibold">Belum ada akun Zoom</h3>
                                        <p class="text-white/40 text-sm mt-1">Tambahkan akun Zoom dulu agar callback bisa disetting per akun.</p>
                                        <a href="{{ route('zoom.create') }}" class="btn-primary mt-5 inline-flex px-5 py-2.5 rounded-xl text-sm font-semibold">Tambah Akun Zoom</a>
                                    </div>
                                @else
                                    <div class="mb-5 rounded-xl border border-white/10 bg-white/[0.02] p-4">
                                        <div class="grid gap-3 md:grid-cols-[minmax(0,1fr)_auto] md:items-end">
                                            <div>
                                                <label for="zoom-callback-account-select" class="block text-sm font-semibold text-white">Pilih Akun Zoom</label>
                                                <select id="zoom-callback-account-select" class="input-field mt-2" onchange="selectZoomCallbackAccount(this.value)">
                                                    @foreach($zoomAccounts as $zoomAccount)
                                                        @php
                                                            $optionStatus = $callbackStatusConfigs[$zoomAccount->webhook_status]['label'];
                                                        @endphp
                                                        <option value="zoom-callback-panel-{{ $zoomAccount->id }}">
                                                            {{ $zoomAccount->label }} - {{ $optionStatus }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                            </div>
                                            <div class="rounded-xl border border-white/10 bg-black/10 px-4 py-3">
                                                <p class="text-xs font-semibold uppercase text-white/40">Total akun</p>
                                                <p class="mt-1 text-lg font-bold text-white">{{ $zoomAccounts->count() }}</p>
                                            </div>
                                        </div>
                                        <p class="mt-3 text-xs leading-5 text-white/35">Pilih satu akun untuk mengedit Secret Token, callback URL, status, dan tes callback akun tersebut.</p>
                                    </div>

                                    <div class="space-y-5">
                                        @foreach($zoomAccounts as $zoomAccount)
                                            @php
                                                $callbackStatus = $zoomAccount->webhook_status;
                                                $callbackStatusConfig = $callbackStatusConfigs[$callbackStatus];
                                                $callbackUrlId = 'zoom-callback-url-' . $zoomAccount->id;
                                                $copyTextId = 'copy-callback-text-' . $zoomAccount->id;
                                                $saveBtnId = 'callback-save-btn-' . $zoomAccount->id;
                                                $saveTextId = 'callback-save-text-' . $zoomAccount->id;
                                                $saveLoadingId = 'callback-save-loading-' . $zoomAccount->id;
                                                $selectedNotificationEvents = $zoomAccount->webhook_notification_events ?? [];
                                                $selectedNotificationCount = count($selectedNotificationEvents);
                                            @endphp

                                            <div id="zoom-callback-panel-{{ $zoomAccount->id }}" data-zoom-callback-panel class="{{ $loop->first ? '' : 'hidden' }} rounded-xl border border-white/10 bg-white/[0.02] p-5">
                                                <div class="flex flex-col gap-4 xl:flex-row xl:items-start xl:justify-between">
                                                    <div class="min-w-0">
                                                        <div class="flex flex-wrap items-center gap-3">
                                                            <h3 class="text-base font-bold text-white">{{ $zoomAccount->label }}</h3>
                                                            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full border text-xs font-semibold {{ $callbackStatusConfig['classes'] }}">
                                                                @if($callbackStatus === 'active')
                                                                    <svg class="w-3.5 h-3.5 {{ $callbackStatusConfig['iconClasses'] }}" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                                                                    </svg>
                                                                @elseif(in_array($callbackStatus, ['unconfigured', 'pending_zoom'], true))
                                                                    <svg class="w-3.5 h-3.5 {{ $callbackStatusConfig['iconClasses'] }}" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m0 3.75h.008v.008H12v-.008ZM21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                                                                    </svg>
                                                                @else
                                                                    <svg class="w-3.5 h-3.5 {{ $callbackStatusConfig['iconClasses'] }}" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M18.364 18.364A9 9 0 0 0 5.636 5.636m12.728 12.728A9 9 0 0 1 5.636 5.636m12.728 12.728L5.636 5.636" />
                                                                    </svg>
                                                                @endif
                                                                {{ $callbackStatusConfig['label'] }}
                                                            </span>
                                                        </div>
                                                        <p class="text-white/40 text-sm mt-1">{{ $callbackStatusConfig['description'] }}</p>
                                                        <p class="text-white/30 text-xs mt-1">{{ $zoomAccount->email ?? 'Email Zoom belum terbaca' }}</p>
                                                    </div>

                                                    <div class="w-full xl:w-80 space-y-3">
                                                        <form method="POST" action="{{ route('settings.zoom-callback.update', $zoomAccount) }}" onsubmit="submitCallbackBtn('{{ $saveBtnId }}', '{{ $saveTextId }}', '{{ $saveLoadingId }}')">
                                                            @csrf
                                                            @method('PUT')
                                                            <input type="hidden" name="callback_enabled" value="0">
                                                            <label for="webhook-secret-{{ $zoomAccount->id }}" class="block text-sm font-semibold text-white">Secret Token</label>
                                                            <input id="webhook-secret-{{ $zoomAccount->id }}" name="webhook_secret" type="password" class="input-field mt-2" autocomplete="off" placeholder="{{ $zoomAccount->is_webhook_secret_configured ? 'Sudah tersimpan, isi untuk mengganti' : 'Masukkan Secret Token akun ini' }}">

                                                            <div class="mt-3 flex items-center justify-between gap-4 rounded-xl border border-white/10 bg-white/[0.03] px-4 py-3">
                                                                <div>
                                                                    <label for="callback-enabled-{{ $zoomAccount->id }}" class="block text-sm font-semibold text-white">Callback</label>
                                                                    <p class="text-xs text-white/40">{{ $zoomAccount->webhook_enabled ? 'On' : 'Off' }}</p>
                                                                </div>
                                                                <label class="relative inline-flex h-7 w-12 cursor-pointer items-center rounded-full">
                                                                    <input id="callback-enabled-{{ $zoomAccount->id }}" type="checkbox" name="callback_enabled" value="1" class="peer sr-only" @checked($zoomAccount->webhook_enabled)>
                                                                    <span class="absolute inset-0 rounded-full bg-white/10 transition-colors duration-200 peer-checked:bg-[#2D8CFF] peer-focus-visible:outline peer-focus-visible:outline-2 peer-focus-visible:outline-offset-2 peer-focus-visible:outline-[#2D8CFF]"></span>
                                                                    <span class="relative ml-1 h-5 w-5 rounded-full bg-white shadow transition-transform duration-200 peer-checked:translate-x-5"></span>
                                                                </label>
                                                            </div>

                                                            <div class="mt-3 rounded-xl border border-white/10 bg-white/[0.03] p-3">
                                                                <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                                                                    <div class="min-w-0">
                                                                        <div class="flex flex-wrap items-center gap-2">
                                                                            <span class="text-sm font-semibold text-white">Notifikasi Telegram</span>
                                                                            <span id="notification-count-{{ $zoomAccount->id }}" class="rounded-full border border-[#2D8CFF]/20 bg-[#2D8CFF]/10 px-2 py-0.5 text-[11px] font-semibold {{ $selectedNotificationCount > 0 ? 'text-blue-200' : 'text-white/45' }}">{{ $selectedNotificationCount }} aktif</span>
                                                                        </div>
                                                                        <p class="mt-1 text-xs leading-5 text-white/40">Centang event yang perlu dikirim ke Telegram.</p>
                                                                    </div>
                                                                    <div class="flex shrink-0 gap-2">
                                                                        <button type="button" onclick="setNotificationEvents('{{ $zoomAccount->id }}', true)" class="min-h-8 rounded-lg border border-[#2D8CFF]/25 bg-[#2D8CFF]/10 px-2.5 text-[11px] font-semibold text-blue-200 transition-colors duration-200 hover:bg-[#2D8CFF]/15 focus:outline-none focus:ring-2 focus:ring-[#2D8CFF] cursor-pointer">Semua</button>
                                                                        <button type="button" onclick="setNotificationEvents('{{ $zoomAccount->id }}', false)" class="min-h-8 rounded-lg border border-white/10 bg-black/15 px-2.5 text-[11px] font-semibold text-white/60 transition-colors duration-200 hover:bg-white/10 hover:text-white focus:outline-none focus:ring-2 focus:ring-[#2D8CFF] cursor-pointer">Reset</button>
                                                                    </div>
                                                                </div>
                                                                <div class="mt-3 grid grid-cols-2 gap-2">
                                                                    @foreach(\App\Models\ZoomAccount::WEBHOOK_NOTIFICATION_EVENTS as $notificationEvent => $notificationConfig)
                                                                        @php($notificationInputId = 'notification-event-' . $zoomAccount->id . '-' . str_replace('.', '-', $notificationEvent))
                                                                        <label for="{{ $notificationInputId }}" class="group flex min-h-12 cursor-pointer items-center gap-2 rounded-lg border border-white/10 bg-black/15 px-2.5 py-2 transition-all duration-200 hover:border-[#2D8CFF]/30 hover:bg-[#2D8CFF]/5 has-[:checked]:border-[#2D8CFF]/45 has-[:checked]:bg-[#2D8CFF]/10">
                                                                            <input
                                                                                id="{{ $notificationInputId }}"
                                                                                type="checkbox"
                                                                                name="webhook_notification_events[]"
                                                                                value="{{ $notificationEvent }}"
                                                                                data-notification-group="{{ $zoomAccount->id }}"
                                                                                onchange="updateNotificationCount('{{ $zoomAccount->id }}')"
                                                                                class="h-4 w-4 shrink-0 rounded border-white/20 bg-black/20 text-[#2D8CFF] focus:ring-[#2D8CFF]"
                                                                                @checked(in_array($notificationEvent, $selectedNotificationEvents, true))
                                                                            >
                                                                            <span class="min-w-0">
                                                                                <span class="block truncate text-xs font-semibold text-white/75 group-has-[:checked]:text-blue-200">{{ $notificationConfig['label'] }}</span>
                                                                                <code class="block truncate text-[10px] text-white/35">{{ $notificationEvent }}</code>
                                                                            </span>
                                                                        </label>
                                                                    @endforeach
                                                                </div>
                                                                <p class="mt-2 text-[11px] leading-4 text-white/35">Event yang tidak dicentang tetap diproses untuk sinkronisasi data.</p>
                                                            </div>

                                                            <button type="submit" id="{{ $saveBtnId }}" class="mt-3 w-full btn-primary px-5 py-2.5 rounded-xl text-sm font-semibold">
                                                                <span id="{{ $saveTextId }}">Simpan Akun Ini</span>
                                                                <svg class="w-4 h-4 animate-spin hidden" id="{{ $saveLoadingId }}" fill="none" viewBox="0 0 24 24">
                                                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 0 1 8-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 0 1 4 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                                                </svg>
                                                            </button>
                                                        </form>

                                                        <form method="POST" action="{{ route('settings.zoom-callback.test', $zoomAccount) }}" onsubmit="submitCallbackBtn('callback-test-btn-{{ $zoomAccount->id }}', 'callback-test-text-{{ $zoomAccount->id }}', 'callback-test-loading-{{ $zoomAccount->id }}')">
                                                            @csrf
                                                            <button type="submit" id="callback-test-btn-{{ $zoomAccount->id }}" class="w-full inline-flex min-h-11 items-center justify-center gap-2 rounded-xl border border-emerald-500/20 bg-emerald-500/10 px-5 py-2.5 text-sm font-semibold text-emerald-300 transition-all duration-200 hover:bg-emerald-500/15 hover:text-emerald-200 focus:outline-none focus:ring-2 focus:ring-emerald-400 cursor-pointer">
                                                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M5.25 5.25v5.25h5.25M18.75 18.75V13.5H13.5M19.5 9A7.5 7.5 0 0 0 6.08 4.91L5.25 5.75M4.5 15a7.5 7.5 0 0 0 13.42 4.09l.83-.84" />
                                                                </svg>
                                                                <span id="callback-test-text-{{ $zoomAccount->id }}">Tes Callback</span>
                                                                <svg class="w-4 h-4 animate-spin hidden" id="callback-test-loading-{{ $zoomAccount->id }}" fill="none" viewBox="0 0 24 24">
                                                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 0 1 8-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 0 1 4 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                                                </svg>
                                                            </button>
                                                            <p class="mt-2 text-xs leading-5 text-white/35">Tes internal ke Telegram. Status verifikasi Zoom tetap menunggu request dari Zoom Marketplace.</p>
                                                        </form>
                                                    </div>
                                                </div>

                                                <div class="mt-5 grid gap-4 xl:grid-cols-[minmax(0,1fr)_20rem]">
                                                    <div class="rounded-xl border border-white/10 bg-black/10 p-4">
                                                        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                                                            <div class="min-w-0">
                                                                <p class="text-xs font-semibold uppercase text-white/40">Callback URL untuk akun ini</p>
                                                                <code id="{{ $callbackUrlId }}" class="mt-2 block break-all rounded-lg border border-white/10 bg-black/20 px-3 py-2 text-xs text-blue-200">{{ $zoomAccount->webhook_url }}</code>
                                                            </div>
                                                            <button type="button" onclick="copyZoomCallbackUrl('{{ $callbackUrlId }}', '{{ $copyTextId }}')" class="inline-flex min-h-11 items-center justify-center gap-2 rounded-xl border border-white/10 bg-white/5 px-4 py-2 text-sm font-semibold text-white/70 transition-all duration-200 hover:bg-white/10 hover:text-white focus:outline-none focus:ring-2 focus:ring-[#2D8CFF] cursor-pointer">
                                                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 7.5V6A2.25 2.25 0 0 1 10.5 3.75h7.5A2.25 2.25 0 0 1 20.25 6v7.5A2.25 2.25 0 0 1 18 15.75h-1.5M3.75 10.5A2.25 2.25 0 0 1 6 8.25h7.5a2.25 2.25 0 0 1 2.25 2.25V18a2.25 2.25 0 0 1-2.25 2.25H6A2.25 2.25 0 0 1 3.75 18v-7.5Z" />
                                                                </svg>
                                                                <span id="{{ $copyTextId }}">Salin</span>
                                                            </button>
                                                        </div>
                                                    </div>

                                                    <div class="rounded-xl border border-white/10 bg-black/10 p-4">
                                                        <p class="text-xs font-semibold uppercase text-white/40">Checklist akun</p>
                                                        <div class="mt-3 space-y-2">
                                                            <div class="flex items-center gap-2 text-sm {{ $zoomAccount->is_webhook_url_public_https ? 'text-emerald-300' : 'text-amber-300' }}">
                                                                <svg class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                                                    @if($zoomAccount->is_webhook_url_public_https)
                                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75" />
                                                                    @else
                                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m0 3.75h.008v.008H12v-.008Z" />
                                                                    @endif
                                                                </svg>
                                                                <span>URL publik HTTPS</span>
                                                            </div>
                                                            <div class="flex items-center gap-2 text-sm {{ $zoomAccount->is_webhook_secret_configured ? 'text-emerald-300' : 'text-amber-300' }}">
                                                                <svg class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                                                    @if($zoomAccount->is_webhook_secret_configured)
                                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75" />
                                                                    @else
                                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m0 3.75h.008v.008H12v-.008Z" />
                                                                    @endif
                                                                </svg>
                                                                <span>Secret token akun</span>
                                                            </div>
                                                            <div class="flex items-center gap-2 text-sm {{ $zoomAccount->is_webhook_verified ? 'text-emerald-300' : 'text-amber-300' }}">
                                                                <svg class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                                                    @if($zoomAccount->is_webhook_verified)
                                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75" />
                                                                    @else
                                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m0 3.75h.008v.008H12v-.008Z" />
                                                                    @endif
                                                                </svg>
                                                                <span>Diverifikasi Zoom</span>
                                                            </div>
                                                        </div>
                                                        @if($zoomAccount->webhook_last_received_at)
                                                            <p class="mt-3 text-xs text-white/35">
                                                                Terakhir diterima: {{ $zoomAccount->webhook_last_received_at->format('d M Y H:i') }}{{ $zoomAccount->webhook_last_event ? ' - ' . $zoomAccount->webhook_last_event : '' }}
                                                            </p>
                                                        @endif
                                                    </div>
                                                </div>

                                                <div class="mt-4 rounded-xl border border-white/10 bg-white/[0.03] p-4">
                                                    <div class="flex flex-col gap-1 sm:flex-row sm:items-center sm:justify-between">
                                                        <h4 class="text-sm font-bold text-white/80">Event yang dibutuhkan</h4>
                                                        <p class="text-xs text-white/35">Centang semua event ini di Zoom Marketplace.</p>
                                                    </div>
                                                    <div class="mt-3 grid grid-cols-1 gap-2 sm:grid-cols-2 lg:grid-cols-3">
                                                        @foreach([
                                                            'meeting.started' => 'Start meeting',
                                                            'meeting.ended' => 'End meeting',
                                                            'meeting.created' => 'Meeting created',
                                                            'meeting.updated' => 'Meeting update',
                                                            'meeting.deleted' => 'Meeting deleted',
                                                            'recording.completed' => 'Record completed',
                                                        ] as $eventName => $eventLabel)
                                                            <div class="flex min-h-11 items-center gap-2 rounded-lg border border-white/10 bg-black/15 px-3 py-2">
                                                                <svg class="h-4 w-4 shrink-0 text-[#2D8CFF]" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true">
                                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75" />
                                                                </svg>
                                                                <div class="min-w-0">
                                                                    <p class="text-xs font-semibold text-white/70">{{ $eventLabel }}</p>
                                                                    <code class="block truncate text-[11px] text-white/40">{{ $eventName }}</code>
                                                                </div>
                                                            </div>
                                                        @endforeach
                                                    </div>
                                                </div>

                                                @if($callbackStatus !== 'active')
                                                    <div class="mt-4 rounded-xl border border-amber-500/15 bg-amber-500/5 p-4">
                                                        <h4 class="text-sm font-bold text-amber-200">Cara setting akun ini di Zoom</h4>
                                                        <ol class="mt-3 space-y-2 text-sm leading-6 text-white/60">
                                                            <li>1. Pastikan <code class="rounded bg-black/25 px-1.5 py-0.5 text-amber-100">APP_URL</code> memakai URL publik HTTPS aktif.</li>
                                                            <li>2. Di Zoom Marketplace akun ini, buka Event Subscriptions dan salin Secret Token ke field di atas.</li>
                                                            <li>3. Isi Event notification endpoint URL dengan callback URL akun ini, lalu validasi/simpan.</li>
                                                            <li>4. Di bagian Event Types, centang keenam event pada daftar kebutuhan event di atas.</li>
                                                        </ol>
                                                    </div>
                                                @endif
                                            </div>
                                        @endforeach
                                    </div>
                                @endif
                            </section>

                            {{-- Section Header --}}
                            <div class="flex items-center gap-4 mb-8 pb-6 border-b border-white/5">
                                <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-blue-500/20 to-blue-600/10 border border-blue-500/20 flex items-center justify-center shrink-0">
                                    <svg class="w-6 h-6 text-blue-400" fill="currentColor" viewBox="0 0 24 24">
                                        <path d="M11.944 0A12 12 0 0 0 0 12a12 12 0 0 0 12 12 12 12 0 0 0 12-12A12 12 0 0 0 12 0a12 12 0 0 0-.056 0zm4.962 7.224c.1-.002.321.023.465.14a.506.506 0 0 1 .171.325c.016.093.036.306.02.472-.18 1.898-.962 6.502-1.36 8.627-.168.9-.499 1.201-.82 1.23-.696.065-1.225-.46-1.9-.902-1.056-.693-1.653-1.124-2.678-1.8-1.185-.78-.417-1.21.258-1.91.177-.184 3.247-2.977 3.307-3.23.007-.032.014-.15-.056-.212s-.174-.041-.249-.024c-.106.024-1.793 1.14-5.061 3.345-.48.33-.913.49-1.302.48-.428-.008-1.252-.241-1.865-.44-.752-.245-1.349-.374-1.297-.789.027-.216.325-.437.892-.663 3.498-1.524 5.83-2.529 6.998-3.014 3.332-1.386 4.025-1.627 4.476-1.635z"/>
                                    </svg>
                                </div>
                                <div>
                                    <h2 class="text-lg font-bold text-white">Integrasi Telegram</h2>
                                    <p class="text-white/40 text-sm mt-0.5">Terima notifikasi jadwal meeting langsung di Telegram Anda.</p>
                                </div>
                            </div>

                            {{-- Connected State --}}
                            @if(auth()->user()->telegraphChats()->count() > 0)
                                <div class="space-y-4">
                                    @foreach(auth()->user()->telegraphChats as $chat)
                                        <div class="flex flex-col sm:flex-row sm:items-center gap-6 p-5 bg-emerald-500/5 border border-emerald-500/15 rounded-xl">
                                            <div class="flex items-center gap-4 flex-1">
                                                <div class="w-10 h-10 rounded-full bg-emerald-500/10 border border-emerald-500/20 flex items-center justify-center shrink-0">
                                                    <svg class="w-5 h-5 text-emerald-400" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                                    </svg>
                                                </div>
                                                <div>
                                                    <p class="text-emerald-400 font-semibold text-sm">Terhubung ke Telegram ({{ $chat->name ?? 'Akun' }})</p>
                                                    <p class="text-white/40 text-xs mt-0.5">Notifikasi akan dikirim ke akun Telegram ini.</p>
                                                </div>
                                            </div>
                                            <form method="POST" action="{{ route('telegram.unlink') }}" onsubmit="submitTelegramBtn(this, 'unlink-btn-{{ $chat->id }}', 'unlink-text-{{ $chat->id }}', 'unlink-loading-{{ $chat->id }}')">
                                                @csrf
                                                <input type="hidden" name="chat_id" value="{{ $chat->id }}">
                                                <button type="submit" id="unlink-btn-{{ $chat->id }}" class="btn-danger w-full sm:w-auto px-5 py-2.5 rounded-xl text-sm font-semibold flex items-center justify-center gap-2 transition-all duration-200 cursor-pointer">
                                                    <span id="unlink-text-{{ $chat->id }}">Putuskan Tautan</span>
                                                    <svg class="w-4 h-4 animate-spin hidden" id="unlink-loading-{{ $chat->id }}" fill="none" viewBox="0 0 24 24">
                                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                                    </svg>
                                                </button>
                                            </form>
                                        </div>
                                    @endforeach
                                </div>
                            @endif

                            {{-- Always Show Section to Link Accounts --}}
                            <div class="mt-8 pt-8 border-t border-white/5 space-y-6">
                                <h3 class="text-lg font-semibold text-white mb-4">
                                    {{ auth()->user()->telegraphChats()->count() > 0 ? 'Tautkan Akun Telegram Lain' : 'Mulai Tautkan Telegram' }}
                                </h3>

                                @if(auth()->user()->telegram_link_code)
                                    <div class="p-6 bg-blue-500/5 border border-blue-500/15 rounded-xl text-center">
                                        <div class="mb-4">
                                            <div class="w-16 h-16 mx-auto bg-[#2D8CFF]/10 rounded-full flex items-center justify-center mb-3">
                                                <svg class="w-8 h-8 text-[#2D8CFF]" fill="currentColor" viewBox="0 0 24 24">
                                                    <path d="M11.944 0A12 12 0 0 0 0 12a12 12 0 0 0 12 12 12 12 0 0 0 12-12A12 12 0 0 0 12 0a12 12 0 0 0-.056 0zm4.962 7.224c.1-.002.321.023.465.14a.506.506 0 0 1 .171.325c.016.093.036.306.02.472-.18 1.898-.962 6.502-1.36 8.627-.168.9-.499 1.201-.82 1.23-.696.065-1.225-.46-1.9-.902-1.056-.693-1.653-1.124-2.678-1.8-1.185-.78-.417-1.21.258-1.91.177-.184 3.247-2.977 3.307-3.23.007-.032.014-.15-.056-.212s-.174-.041-.249-.024c-.106.024-1.793 1.14-5.061 3.345-.48.33-.913.49-1.302.48-.428-.008-1.252-.241-1.865-.44-.752-.245-1.349-.374-1.297-.789.027-.216.325-.437.892-.663 3.498-1.524 5.83-2.529 6.998-3.014 3.332-1.386 4.025-1.627 4.476-1.635z"/>
                                                </svg>
                                            </div>
                                            <h3 class="text-white font-bold text-lg">Kode Siap Digunakan!</h3>
                                            <p class="text-white/50 text-sm mt-1">Klik tombol di bawah ini untuk membuka Telegram dan menghubungkan akun secara otomatis.</p>
                                        </div>
                                        
                                        <a href="https://t.me/rsudblambanganbot?start={{ auth()->user()->telegram_link_code }}" target="_blank" rel="noopener noreferrer"
                                            class="inline-flex items-center gap-2 px-8 py-3 bg-[#2D8CFF] hover:bg-blue-500 text-white rounded-xl font-bold shadow-lg shadow-[#2D8CFF]/20 transition-all duration-200 cursor-pointer transform hover:-translate-y-0.5">
                                            Buka Telegram & Hubungkan
                                            <svg class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 6H5.25A2.25 2.25 0 003 8.25v10.5A2.25 2.25 0 005.25 21h10.5A2.25 2.25 0 0018 18.75V10.5m-10.5 6L21 3m0 0h-5.25M21 3v5.25"/></svg>
                                        </a>
                                        
                                        <p class="text-white/30 text-xs mt-4">
                                            Atau kirim manual kode ini ke bot: <span class="font-mono text-white/70 bg-black/30 px-2 py-0.5 rounded">/link {{ auth()->user()->telegram_link_code }}</span>
                                        </p>
                                    </div>

                                    <div class="flex items-center justify-between gap-4 pt-2 border-t border-white/5">
                                        <p class="text-white/30 text-xs">Mengalami masalah dengan kode saat ini?</p>
                                        <form method="POST" action="{{ route('telegram.generate-link') }}">
                                            @csrf
                                            <button type="submit" class="text-blue-400 hover:text-blue-300 text-sm font-medium transition-colors cursor-pointer">
                                                Buat Kode Baru
                                            </button>
                                        </form>
                                    </div>
                                @else
                                    <div class="p-6 bg-white/[0.02] border border-white/5 rounded-xl text-center">
                                        <div class="w-12 h-12 mx-auto bg-white/5 border border-white/10 rounded-full flex items-center justify-center mb-3">
                                            <svg class="w-6 h-6 text-white/50" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M13.19 8.688a4.5 4.5 0 011.242 7.244l-4.5 4.5a4.5 4.5 0 01-6.364-6.364l1.757-1.757m13.35-.622l1.757-1.757a4.5 4.5 0 00-6.364-6.364l-4.5 4.5a4.5 4.5 0 001.242 7.244" />
                                            </svg>
                                        </div>
                                        <h3 class="text-white font-semibold mb-1">Dapatkan Kode Tautan</h3>
                                        <p class="text-white/40 text-sm mb-6 max-w-sm mx-auto">Mulai dengan membuat kode tautan yang akan menghubungkan akun ini dengan Telegram Anda.</p>
                                        
                                        <form method="POST" action="{{ route('telegram.generate-link') }}" onsubmit="submitTelegramBtn(this, 'link-btn', 'link-text', 'link-loading')">
                                            @csrf
                                            <button type="submit" id="link-btn" class="btn-primary inline-flex px-6 py-2.5 rounded-xl text-sm font-semibold items-center justify-center gap-2 transition-all duration-200 cursor-pointer">
                                                <span id="link-text">Mulai Hubungkan Telegram</span>
                                                <svg class="w-4 h-4 animate-spin hidden" id="link-loading" fill="none" viewBox="0 0 24 24">
                                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                                </svg>
                                            </button>
                                        </form>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                @endif

            </div>
        </div>
    </div>

    <script>
        function selectZoomCallbackAccount(panelId) {
            document.querySelectorAll('[data-zoom-callback-panel]').forEach((panel) => {
                panel.classList.toggle('hidden', panel.id !== panelId);
            });
        }

        function updateNotificationCount(accountId) {
            const inputs = document.querySelectorAll(`[data-notification-group="${accountId}"]`);
            const count = Array.from(inputs).filter((input) => input.checked).length;
            const text = document.getElementById(`notification-count-${accountId}`);

            if (!text) return;

            text.innerText = `${count} aktif`;
            text.classList.toggle('text-blue-200', count > 0);
            text.classList.toggle('text-white/45', count === 0);
        }

        function setNotificationEvents(accountId, checked) {
            document.querySelectorAll(`[data-notification-group="${accountId}"]`).forEach((input) => {
                input.checked = checked;
            });

            updateNotificationCount(accountId);
        }

        function submitCallbackBtn(btnId, textId, loadingId) {
            const btn = document.getElementById(btnId);
            const text = document.getElementById(textId);
            const loading = document.getElementById(loadingId);

            btn.disabled = true;
            btn.classList.add('opacity-75', 'cursor-not-allowed');
            text.classList.add('hidden');
            loading.classList.remove('hidden');
        }

        async function copyZoomCallbackUrl(urlId, textId) {
            const callbackUrl = document.getElementById(urlId).innerText.trim();
            const copyText = document.getElementById(textId);

            try {
                await navigator.clipboard.writeText(callbackUrl);
                copyText.innerText = 'Tersalin';
                setTimeout(() => copyText.innerText = 'Salin', 1800);
            } catch (error) {
                copyText.innerText = 'Gagal';
                setTimeout(() => copyText.innerText = 'Salin', 1800);
            }
        }

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
