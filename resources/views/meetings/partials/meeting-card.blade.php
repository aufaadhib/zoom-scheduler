@php
    $variant = $variant ?? 'upcoming';
    $isFinished = $meeting->isFinished();
    $isOngoing = $meeting->isOngoing();
    $statusClasses = match (true) {
        $isOngoing => 'bg-emerald-500/10 text-emerald-300 border-emerald-500/20',
        $isFinished => 'bg-white/5 text-white/35 border-white/10',
        $meeting->isInstant() => 'bg-amber-500/10 text-amber-300 border-amber-500/20',
        default => 'bg-[#2D8CFF]/10 text-[#8EC5FF] border-[#2D8CFF]/20',
    };
    $statusLabel = match (true) {
        $isOngoing => 'Sedang berlangsung',
        $isFinished => 'Selesai',
        $meeting->isInstant() => 'Instant',
        default => 'Terjadwal',
    };
    $muted = $isFinished && !$meeting->hasRecording();
@endphp

<div class="meeting-card glass-card rounded-2xl p-5 sm:p-6 flex flex-col justify-between transition-all duration-300 animate-fade-in-up {{ $muted ? 'opacity-60 hover:opacity-90' : 'hover:border-white/15' }}"
     data-type="{{ $meeting->isInstant() ? 'instant' : 'scheduled' }}"
     data-state="{{ $isOngoing ? 'ongoing' : ($isFinished ? 'finished' : 'upcoming') }}"
     style="animation-delay: {{ ($index ?? 0) * 50 }}ms">
    <div>
        <div class="flex items-start justify-between gap-3 mb-4">
            <span class="inline-flex items-center gap-2 px-2.5 py-1 rounded-full text-[10px] font-semibold tracking-wide uppercase border {{ $statusClasses }}">
                @if($isOngoing)
                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-300 animate-pulse"></span>
                @else
                    <span class="w-1.5 h-1.5 rounded-full bg-current opacity-70"></span>
                @endif
                {{ $statusLabel }}
            </span>

            @if($meeting->hasRecording())
                <a href="{{ $meeting->recording_share_url }}" target="_blank" rel="noopener"
                   class="cursor-pointer inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-xl text-emerald-300 bg-emerald-500/10 hover:bg-emerald-500/20 border border-emerald-500/20 transition-all"
                   aria-label="Buka rekaman {{ $meeting->topic }}"
                   title="Buka rekaman">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 10.5l4.72-4.72a.75.75 0 011.28.53v11.38a.75.75 0 01-1.28.53l-4.72-4.72M4.5 18.75h9a2.25 2.25 0 002.25-2.25v-9a2.25 2.25 0 00-2.25-2.25h-9A2.25 2.25 0 002.25 7.5v9a2.25 2.25 0 002.25 2.25z" />
                    </svg>
                </a>
            @endif
        </div>

        <h3 class="text-white font-bold text-lg mb-1 leading-snug truncate {{ $isFinished ? 'text-white/70' : '' }}">
            <a href="{{ route('meetings.show', $meeting) }}" class="hover:text-[#2D8CFF] transition-colors">
                {{ $meeting->topic }}
            </a>
        </h3>
        <p class="text-white/30 text-xs line-clamp-2 mb-4 leading-relaxed">
            {{ $meeting->agenda ?? 'Tidak ada deskripsi.' }}
        </p>
    </div>

    <div>
        <div class="space-y-2 border-t border-white/5 pt-4 mb-5">
            <div class="flex items-center justify-between gap-4 text-xs">
                <span class="text-white/30">Akun Host</span>
                <span class="text-white/60 font-medium truncate max-w-[160px]">
                    {{ $meeting->zoomAccount->account_name }}
                </span>
            </div>

            <div class="flex items-center justify-between gap-4 text-xs">
                <span class="text-white/30">Waktu</span>
                <span class="text-white/60 font-medium text-right">
                    @if($meeting->start_time)
                        {{ $meeting->start_time->copy()->setTimezone($meeting->timezone)->format('d/m/y - H:i') }}
                    @elseif($meeting->started_at)
                        {{ $meeting->started_at->copy()->setTimezone('Asia/Jakarta')->format('d/m/y - H:i') }}
                    @else
                        Mulai Sekarang
                    @endif
                </span>
            </div>

            <div class="flex items-center justify-between gap-4 text-xs">
                <span class="text-white/30">{{ $isFinished ? 'Selesai' : 'Durasi' }}</span>
                <span class="text-white/60 font-medium text-right">
                    @if($isFinished && $meeting->ended_at)
                        {{ $meeting->ended_at->copy()->setTimezone('Asia/Jakarta')->format('d/m/y - H:i') }}
                    @else
                        {{ $meeting->duration }} Menit
                    @endif
                </span>
            </div>

            @if($meeting->hasRecording() && $meeting->recording_passcode)
                <div class="flex items-center justify-between gap-4 text-xs">
                    <span class="text-white/30">Passcode Rekaman</span>
                    <span class="text-white/60 font-mono font-medium truncate max-w-[140px]">{{ $meeting->recording_passcode }}</span>
                </div>
            @endif
        </div>

        <div class="flex items-center gap-2">
            @if(!$isFinished && filled($meeting->start_url))
                <a href="{{ $meeting->start_url }}" target="_blank" rel="noopener"
                   class="flex-1 btn-primary min-h-11 py-2.5 text-center text-xs font-semibold flex items-center justify-center gap-1.5">
                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M5.25 5.653c0-.856.917-1.398 1.667-.986l11.54 6.348a1.125 1.125 0 010 1.971l-11.54 6.347a1.125 1.125 0 01-1.667-.985V5.653z" />
                    </svg>
                    Mulai
                </a>
            @else
                <a href="{{ route('meetings.show', $meeting) }}"
                   class="flex-1 min-h-11 py-2.5 rounded-xl text-xs font-semibold text-white/60 hover:text-white bg-white/5 hover:bg-white/10 border border-white/10 hover:border-white/20 transition-all flex items-center justify-center gap-1.5">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="1.6" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z" />
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                    </svg>
                    Detail
                </a>
            @endif

            @if(filled($meeting->join_url) && !$isFinished)
                <a href="{{ $meeting->join_url }}" target="_blank" rel="noopener"
                   class="cursor-pointer flex h-11 w-11 shrink-0 items-center justify-center rounded-xl text-white/50 hover:text-white bg-white/5 hover:bg-white/10 border border-white/10 hover:border-white/20 transition-all"
                   title="Gabung peserta"
                   aria-label="Gabung peserta {{ $meeting->topic }}">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z" />
                    </svg>
                </a>
            @endif

            @if($meeting->hasRecording())
                <a href="{{ $meeting->recording_share_url }}" target="_blank" rel="noopener"
                   class="cursor-pointer flex h-11 w-11 shrink-0 items-center justify-center rounded-xl text-emerald-300/80 hover:text-emerald-200 bg-emerald-500/10 hover:bg-emerald-500/20 border border-emerald-500/20 transition-all"
                   title="Cek rekaman"
                   aria-label="Cek rekaman {{ $meeting->topic }}">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 10.5l4.72-4.72a.75.75 0 011.28.53v11.38a.75.75 0 01-1.28.53l-4.72-4.72M4.5 18.75h9a2.25 2.25 0 002.25-2.25v-9a2.25 2.25 0 00-2.25-2.25h-9A2.25 2.25 0 002.25 7.5v9a2.25 2.25 0 002.25 2.25z" />
                    </svg>
                </a>
            @endif

            @if(!$isFinished)
                <a href="{{ route('meetings.edit', $meeting) }}"
                   class="cursor-pointer flex h-11 w-11 shrink-0 items-center justify-center rounded-xl text-[#2D8CFF]/60 hover:text-[#2D8CFF] hover:bg-[#2D8CFF]/10 border border-transparent hover:border-[#2D8CFF]/20 transition-all"
                   title="Edit meeting"
                   aria-label="Edit meeting {{ $meeting->topic }}">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zM18 14v4.75A2.25 2.25 0 0115.75 21H5.25A2.25 2.25 0 013 18.75V8.25A2.25 2.25 0 015.25 6H10" />
                    </svg>
                </a>
            @endif

            <button type="button"
                    onclick="confirmDelete({{ $meeting->id }}, '{{ addslashes($meeting->topic) }}')"
                    class="cursor-pointer flex h-11 w-11 shrink-0 items-center justify-center rounded-xl text-red-400/55 hover:text-red-300 hover:bg-red-500/10 border border-transparent hover:border-red-500/20 transition-all"
                    aria-label="{{ $isFinished ? 'Hapus riwayat' : 'Batalkan' }} meeting {{ $meeting->topic }}">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                </svg>
            </button>
        </div>
    </div>
</div>
