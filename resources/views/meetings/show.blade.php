<x-layouts.app :title="'Detail Meeting — Zoom Scheduler'">
    @php
        $isFinished = $meeting->isFinished();
        $linksGridClass = $isFinished ? 'sm:grid-cols-1' : ($meeting->hasRecording() ? 'lg:grid-cols-3' : 'sm:grid-cols-2');
    @endphp

    <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        {{-- Back button --}}
        <a href="{{ route('meetings.index') }}" class="inline-flex items-center gap-2 text-white/40 hover:text-white/70 text-sm mb-6 transition-colors duration-200">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18" />
            </svg>
            Kembali ke Daftar Meeting
        </a>



        {{-- Meeting Card --}}
        <div class="glass-card rounded-2xl p-6 sm:p-8 animate-fade-in-up">
            {{-- Header --}}
            <div class="flex flex-col sm:flex-row justify-between items-start gap-4 pb-6 border-b border-white/5 mb-6">
                <div>
                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold border {{ $meeting->isOngoing() ? 'bg-emerald-500/10 text-emerald-300 border-emerald-500/20' : ($meeting->isFinished() ? 'bg-white/5 text-white/45 border-white/10' : ($meeting->isInstant() ? 'bg-amber-500/10 text-amber-400 border-amber-500/20' : 'bg-[#2D8CFF]/10 text-[#8EC5FF] border-[#2D8CFF]/20')) }}">
                        <span class="w-1.5 h-1.5 rounded-full bg-current {{ $meeting->isOngoing() ? 'animate-pulse' : 'opacity-70' }}"></span>
                        @if($meeting->isOngoing())
                            Sedang Berlangsung
                        @elseif($meeting->isFinished())
                            Selesai
                        @else
                            {{ $meeting->isInstant() ? 'Instant Meeting' : 'Scheduled Meeting' }}
                        @endif
                    </span>
                    <h1 class="text-2xl font-bold text-white mt-3">{{ $meeting->topic }}</h1>
                    @if($meeting->agenda)
                        <p class="text-white/40 text-sm mt-1 leading-relaxed">{{ $meeting->agenda }}</p>
                    @endif
                </div>

                {{-- Host Account --}}
                <div class="shrink-0 flex items-center gap-2 px-3 py-1.5 rounded-xl bg-white/[0.02] border border-white/5 text-xs text-white/50">
                    <div class="w-2.5 h-2.5 rounded-full bg-[#2D8CFF]"></div>
                    <span>Host: <strong>{{ $meeting->zoomAccount->account_name }}</strong></span>
                </div>
            </div>

            {{-- Detail Fields --}}
            <div class="space-y-4 mb-8">
                @if($meeting->isScheduled() && $meeting->start_time)
                    {{-- Time --}}
                    <div class="flex flex-col sm:flex-row sm:items-center py-3 border-b border-white/5 gap-2 sm:gap-0">
                        <span class="sm:w-1/3 text-sm text-white/30">Waktu Pelaksanaan</span>
                        <span class="sm:w-2/3 text-sm text-white font-medium">
                            {{ $meeting->start_time->copy()->setTimezone($meeting->timezone)->locale('id')->translatedFormat('l, d F Y - H:i') }} ({{ $meeting->timezone }})
                        </span>
                    </div>

                    {{-- Duration --}}
                    <div class="flex flex-col sm:flex-row sm:items-center py-3 border-b border-white/5 gap-2 sm:gap-0">
                        <span class="sm:w-1/3 text-sm text-white/30">Durasi</span>
                        <span class="sm:w-2/3 text-sm text-white font-medium">{{ $meeting->duration }} Menit</span>
                    </div>
                @endif

                {{-- Meeting ID --}}
                @if($meeting->started_at)
                    <div class="flex flex-col sm:flex-row sm:items-center py-3 border-b border-white/5 gap-2 sm:gap-0">
                        <span class="sm:w-1/3 text-sm text-white/30">Mulai Aktual</span>
                        <span class="sm:w-2/3 text-sm text-white font-medium">
                            {{ $meeting->started_at->copy()->setTimezone('Asia/Jakarta')->locale('id')->translatedFormat('l, d F Y - H:i:s') }} WIB
                        </span>
                    </div>
                @endif

                @if($meeting->ended_at)
                    <div class="flex flex-col sm:flex-row sm:items-center py-3 border-b border-white/5 gap-2 sm:gap-0">
                        <span class="sm:w-1/3 text-sm text-white/30">Selesai Aktual</span>
                        <span class="sm:w-2/3 text-sm text-white font-medium">
                            {{ $meeting->ended_at->copy()->setTimezone('Asia/Jakarta')->locale('id')->translatedFormat('l, d F Y - H:i:s') }} WIB
                        </span>
                    </div>
                @endif

                {{-- Meeting ID --}}
                <div class="flex flex-col sm:flex-row sm:items-center py-3 border-b border-white/5 gap-2 sm:gap-0">
                    <span class="sm:w-1/3 text-sm text-white/30">Meeting ID</span>
                    <span class="sm:w-2/3 text-sm text-white font-mono font-medium tracking-wide">
                        {{ implode(' ', str_split($meeting->zoom_meeting_id, 4)) }}
                    </span>
                </div>

                {{-- Passcode --}}
                <div class="flex flex-col sm:flex-row sm:items-center py-3 border-b border-white/5 gap-2 sm:gap-0">
                    <span class="sm:w-1/3 text-sm text-white/30">Passcode</span>
                    <span class="sm:w-2/3 text-sm text-white font-mono font-medium">
                        {{ $meeting->password ?? '-' }}
                    </span>
                </div>

                {{-- Created At --}}
                <div class="flex flex-col sm:flex-row sm:items-center py-3 border-b border-white/5 gap-2 sm:gap-0">
                    <span class="sm:w-1/3 text-sm text-white/30">Dibuat Pada</span>
                    <span class="sm:w-2/3 text-sm text-white font-medium">
                        {{ $meeting->created_at->copy()->setTimezone('Asia/Jakarta')->locale('id')->translatedFormat('l, d F Y - H:i:s') }} WIB
                    </span>
                </div>
            </div>

            {{-- Links Panel --}}
            <div class="grid grid-cols-1 {{ $linksGridClass }} gap-4 mb-8">
                @if(!$isFinished)
                    {{-- Start Link (Host Only) --}}
                    <div class="p-4 rounded-xl bg-[#2D8CFF]/5 border border-[#2D8CFF]/20 flex flex-col justify-between gap-3">
                    <div>
                        <h4 class="text-sm font-semibold text-[#2D8CFF] flex items-center gap-1.5">
                            <span class="w-1.5 h-1.5 rounded-full bg-[#2D8CFF]"></span>
                            Mulai Meeting (Host Only)
                        </h4>
                        <p class="text-white/30 text-xs mt-1">Gunakan link ini untuk langsung memulai meeting sebagai host.</p>
                    </div>
                    @if(filled($meeting->start_url))
                        <a href="{{ $meeting->start_url }}" target="_blank" rel="noopener" class="btn-primary py-2 w-full text-center text-xs flex items-center justify-center gap-1.5">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M5.25 5.653c0-.856.917-1.398 1.667-.986l11.54 6.348a1.125 1.125 0 010 1.971l-11.54 6.347a1.125 1.125 0 01-1.667-.985V5.653z" />
                            </svg>
                            Mulai Meeting
                        </a>
                    @else
                        <div class="w-full py-2 rounded-xl text-xs font-semibold text-white/30 bg-white/5 border border-white/10 text-center">
                            Link host belum tersedia
                        </div>
                    @endif
                    </div>

                    {{-- Join Link (Peserta) --}}
                    <div class="p-4 rounded-xl bg-white/[0.02] border border-white/10 flex flex-col justify-between gap-3">
                    <div>
                        <h4 class="text-sm font-semibold text-white/80 flex items-center gap-1.5">
                            <span class="w-1.5 h-1.5 rounded-full bg-white/40"></span>
                            Gabung Meeting (Peserta)
                        </h4>
                        <p class="text-white/30 text-xs mt-1">Bagikan link ini kepada peserta yang ingin bergabung.</p>
                    </div>
                    @if(filled($meeting->join_url))
                        <a href="{{ $meeting->join_url }}" target="_blank" rel="noopener" class="w-full py-2 rounded-xl text-xs font-semibold text-white/70 hover:text-white bg-white/5 hover:bg-white/10 border border-white/10 hover:border-white/20 transition-all text-center flex items-center justify-center gap-1.5">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z" />
                            </svg>
                            Gabung Rapat
                        </a>
                    @else
                        <div class="w-full py-2 rounded-xl text-xs font-semibold text-white/30 bg-white/5 border border-white/10 text-center">
                            Link peserta belum tersedia
                        </div>
                    @endif
                    </div>
                @endif

                @if($meeting->hasRecording())
                    <div class="p-4 rounded-xl bg-emerald-500/5 border border-emerald-500/20 flex flex-col justify-between gap-3">
                        <div>
                            <h4 class="text-sm font-semibold text-emerald-300 flex items-center gap-1.5">
                                <span class="w-1.5 h-1.5 rounded-full bg-emerald-300"></span>
                                Rekaman Meeting
                            </h4>
                            <p class="text-white/30 text-xs mt-1">
                                Rekaman tersedia dari event Zoom recording.completed.
                            </p>
                            @if($meeting->recording_passcode)
                                <p class="text-white/45 text-xs mt-2 leading-5">
                                    Passcode:
                                    <span class="block break-all font-mono text-white/70">{{ $meeting->recording_passcode }}</span>
                                </p>
                            @endif
                        </div>
                        <a href="{{ $meeting->recording_share_url }}" target="_blank" rel="noopener" class="w-full py-2 rounded-xl text-xs font-semibold text-emerald-200 hover:text-white bg-emerald-500/10 hover:bg-emerald-500/20 border border-emerald-500/20 transition-all text-center flex items-center justify-center gap-1.5">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 10.5l4.72-4.72a.75.75 0 011.28.53v11.38a.75.75 0 01-1.28.53l-4.72-4.72M4.5 18.75h9a2.25 2.25 0 002.25-2.25v-9a2.25 2.25 0 00-2.25-2.25h-9A2.25 2.25 0 002.25 7.5v9a2.25 2.25 0 002.25 2.25z" />
                            </svg>
                            Cek Rekaman
                        </a>
                    </div>
                @endif
            </div>

            {{-- Actions and Invitation Copy --}}
            <div class="flex flex-col sm:flex-row items-center gap-3 border-t border-white/5 pt-6">
                {{-- Copy invitation --}}
                <button type="button" onclick="copyInvitation()" class="cursor-pointer w-full sm:w-auto px-5 py-3 rounded-xl text-sm font-semibold text-[#2D8CFF] bg-[#2D8CFF]/10 hover:bg-[#2D8CFF]/20 border border-[#2D8CFF]/10 hover:border-[#2D8CFF]/20 transition-all flex items-center justify-center gap-2" id="invitation-btn">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 7.5V6.108c0-1.135.845-2.098 1.976-2.192.373-.03.748-.057 1.123-.08M15.75 18H18a2.25 2.25 0 002.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 00-1.123-.08M15.75 18.75v-1.875a3.375 3.375 0 00-3.375-3.375h-1.5a1.125 1.125 0 01-1.125-1.125v-1.5A3.375 3.375 0 006.375 7.5H5.25m11.9-3.664A2.251 2.251 0 0015 2.25h-1.5a2.251 2.251 0 00-2.15 1.586m5.8 0c.065.21.1.433.1.664v.75h-6V4.5c0-.231.035-.454.1-.664M6.75 7.5H4.875c-.621 0-1.125.504-1.125 1.125v12c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V16.5a9 9 0 00-9-9z" />
                    </svg>
                    <span id="invitation-btn-text">Salin Undangan Rapat</span>
                </button>

                <div class="sm:ml-auto w-full sm:w-auto flex items-center justify-end gap-3">
                    <button type="button" onclick="confirmDelete()" class="cursor-pointer w-full sm:w-auto px-5 py-3 rounded-xl text-sm font-medium text-red-400 bg-red-500/10 hover:bg-red-500/20 border border-transparent hover:border-red-500/20 transition-all flex items-center justify-center gap-2">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                        </svg>
                        {{ $isFinished ? 'Hapus Meeting' : 'Batalkan Meeting' }}
                    </button>
                </div>
            </div>
        </div>
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
                <h3 class="text-lg font-bold text-white mb-1">{{ $isFinished ? 'Hapus Meeting?' : 'Batalkan Meeting Zoom?' }}</h3>
                <p class="text-white/40 text-sm mb-6">
                    @if($isFinished)
                        Rapat ini akan dihapus dari aplikasi dan server Zoom jika masih tersedia.
                    @else
                        Rapat ini akan dihapus dari aplikasi dan dibatalkan di server Zoom.
                    @endif
                </p>

                <div class="flex items-center gap-3">
                    <button type="button" onclick="closeDeleteModal()" class="cursor-pointer flex-1 px-4 py-2.5 rounded-xl text-sm font-medium text-white/60 bg-white/5 hover:bg-white/10 border border-white/10 transition-all duration-200">
                        Batal
                    </button>
                    <form id="delete-form" action="{{ route('meetings.destroy', $meeting) }}" method="POST" class="flex-1">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="cursor-pointer w-full px-4 py-2.5 rounded-xl text-sm font-medium text-white bg-red-500/80 hover:bg-red-500 transition-all duration-200 active:scale-[0.97]">
                            Ya, Hapus
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    {{-- Invisible copy textarea --}}
    <textarea id="invitation-text" class="sr-only" readonly>Topik: {{ $meeting->topic }}
@if($meeting->agenda)Agenda: {{ $meeting->agenda }}
@endif
@if($meeting->isScheduled() && $meeting->start_time)Waktu: {{ $meeting->start_time->copy()->setTimezone($meeting->timezone)->locale('id')->translatedFormat('l, d F Y - H:i') }} ({{ $meeting->timezone }})
@elseWaktu: Instant Meeting (Mulai Sekarang)
@endif

Gabung Zoom Meeting:
{{ $meeting->join_url }}

Meeting ID: {{ $meeting->zoom_meeting_id }}
@if($meeting->password)Passcode: {{ $meeting->password }}
@endif
</textarea>

    <script>
        function copyInvitation() {
            const copyText = document.getElementById("invitation-text");
            copyText.select();
            copyText.setSelectionRange(0, 99999); // For mobile

            // Use Clipboard API if available, fallback to execCommand
            if (navigator.clipboard) {
                navigator.clipboard.writeText(copyText.value).then(showSuccessCopy);
            } else {
                document.execCommand("copy");
                showSuccessCopy();
            }
        }

        function showSuccessCopy() {
            const btn = document.getElementById("invitation-btn");
            const btnText = document.getElementById("invitation-btn-text");

            btnText.textContent = "Undangan Tersalin!";
            btn.classList.add("text-emerald-400", "bg-emerald-500/10", "border-emerald-500/20");
            btn.classList.remove("text-[#2D8CFF]", "bg-[#2D8CFF]/10", "border-[#2D8CFF]/10");

            setTimeout(() => {
                btnText.textContent = "Salin Undangan Rapat";
                btn.classList.remove("text-emerald-400", "bg-emerald-500/10", "border-emerald-500/20");
                btn.classList.add("text-[#2D8CFF]", "bg-[#2D8CFF]/10", "border-[#2D8CFF]/10");
            }, 2000);
        }

        function confirmDelete() {
            const modal = document.getElementById('delete-modal');
            modal.classList.remove('hidden');
            modal.classList.add('flex');
        }

        function closeDeleteModal() {
            const modal = document.getElementById('delete-modal');
            modal.classList.remove('flex');
            modal.classList.add('hidden');
        }

        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') closeDeleteModal();
        });
    </script>
</x-layouts.app>
