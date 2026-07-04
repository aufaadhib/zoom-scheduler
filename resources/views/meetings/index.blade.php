<x-layouts.app :title="'Daftar Meeting — Zoom Scheduler'">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">

        {{-- Header --}}
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-8">
            <div>
                <h1 class="text-2xl sm:text-3xl font-bold bg-gradient-to-r from-white to-white/60 bg-clip-text text-transparent">
                    Jadwal Meeting Zoom
                </h1>
                <p class="text-white/40 text-sm mt-1">
                    Kelola dan jadwalkan rapat Zoom Anda dari satu dashboard terpusat
                </p>
            </div>
            <a href="{{ route('meetings.create') }}" class="btn-primary inline-flex items-center gap-2 shrink-0 self-start sm:self-auto" id="create-meeting-btn">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                </svg>
                Buat Meeting Baru
            </a>
        </div>

        {{-- Filters & Tabs --}}
        @if($activeMeetings->count() > 0 || $pastMeetings->count() > 0)
            <div class="flex border-b border-white/5 gap-6 mb-8">
                <button type="button" onclick="filterMeetings('all')" id="tab-all" class="cursor-pointer pb-4 text-sm font-semibold border-b-2 border-[#2D8CFF] text-white transition-all">
                    Semua Rapat ({{ $activeMeetings->count() + $pastMeetings->count() }})
                </button>
                <button type="button" onclick="filterMeetings('scheduled')" id="tab-scheduled" class="cursor-pointer pb-4 text-sm font-semibold border-b-2 border-transparent text-white/40 hover:text-white/70 transition-all">
                    Terjadwal ({{ $activeMeetings->filter(fn($m) => $m->isScheduled())->count() + $pastMeetings->count() }})
                </button>
                <button type="button" onclick="filterMeetings('instant')" id="tab-instant" class="cursor-pointer pb-4 text-sm font-semibold border-b-2 border-transparent text-white/40 hover:text-white/70 transition-all">
                    Instan ({{ $activeMeetings->filter(fn($m) => $m->isInstant())->count() }})
                </button>
            </div>

            {{-- Active & Upcoming Meetings Section --}}
            @if($activeMeetings->count() > 0)
                <div class="mb-12" id="active-section">
                    <h2 class="text-sm font-bold tracking-wider text-white/40 uppercase mb-4 flex items-center gap-2">
                        <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>
                        Rapat Aktif & Mendatang
                    </h2>
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                        @foreach($activeMeetings as $index => $meeting)
                            <div class="meeting-card glass-card rounded-2xl p-6 flex flex-col justify-between hover:border-white/15 transition-all duration-300 animate-fade-in-up"
                                 data-type="{{ $meeting->isInstant() ? 'instant' : 'scheduled' }}"
                                 style="animation-delay: {{ $index * 50 }}ms">
                                
                                <div>
                                    {{-- Meeting Header --}}
                                    <div class="flex justify-between items-start gap-3 mb-4">
                                        <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[10px] font-semibold tracking-wide uppercase {{ $meeting->isInstant() ? 'bg-amber-500/10 text-amber-400 border border-amber-500/20' : 'bg-emerald-500/10 text-emerald-400 border border-emerald-500/20' }}">
                                            {{ $meeting->isInstant() ? 'Instant' : 'Scheduled' }}
                                        </span>
                                    </div>

                                    {{-- Topic & Agenda --}}
                                    <h3 class="text-white font-bold text-lg mb-1 leading-snug truncate">
                                        <a href="{{ route('meetings.show', $meeting) }}" class="hover:text-[#2D8CFF] transition-colors">
                                            {{ $meeting->topic }}
                                        </a>
                                    </h3>
                                    <p class="text-white/30 text-xs line-clamp-2 mb-4 leading-relaxed">
                                        {{ $meeting->agenda ?? 'Tidak ada deskripsi.' }}
                                    </p>
                                </div>

                                <div>
                                    {{-- Detail list --}}
                                    <div class="space-y-2 border-t border-white/5 pt-4 mb-6">
                                        {{-- Account/Host --}}
                                        <div class="flex items-center justify-between text-xs">
                                            <span class="text-white/30">Akun Host</span>
                                            <span class="text-white/60 font-medium truncate max-w-[150px]">
                                                {{ $meeting->zoomAccount->account_name }}
                                            </span>
                                        </div>

                                        {{-- Date/Time --}}
                                        <div class="flex items-center justify-between text-xs">
                                            <span class="text-white/30">Waktu</span>
                                            <span class="text-white/60 font-medium">
                                                @if($meeting->isScheduled() && $meeting->start_time)
                                                    {{ $meeting->start_time->setTimezone($meeting->timezone)->format('d/m/y — H:i') }}
                                                @else
                                                    Mulai Sekarang
                                                @endif
                                            </span>
                                        </div>

                                        {{-- Duration --}}
                                        @if($meeting->isScheduled())
                                            <div class="flex items-center justify-between text-xs">
                                                <span class="text-white/30">Durasi</span>
                                                <span class="text-white/60 font-medium">{{ $meeting->duration }} Menit</span>
                                            </div>
                                        @endif
                                    </div>

                                    {{-- Action buttons --}}
                                    <div class="flex items-center gap-2">
                                        {{-- Host Start Button --}}
                                        <a href="{{ $meeting->start_url }}" target="_blank" rel="noopener" class="flex-1 btn-primary py-2.5 text-center text-xs font-semibold flex items-center justify-center gap-1">
                                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M5.25 5.653c0-.856.917-1.398 1.667-.986l11.54 6.348a1.125 1.125 0 010 1.971l-11.54 6.347a1.125 1.125 0 01-1.667-.985V5.653z" />
                                            </svg>
                                            Mulai (Host)
                                        </a>

                                        {{-- Join/Participant Button --}}
                                        <a href="{{ $meeting->join_url }}" target="_blank" rel="noopener" class="px-3 py-2.5 rounded-xl text-xs font-semibold text-white/50 hover:text-white bg-white/5 hover:bg-white/10 border border-white/10 hover:border-white/20 transition-all flex items-center justify-center gap-1" title="Gabung Peserta">
                                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z" />
                                            </svg>
                                        </a>

                                        {{-- Edit --}}
                                        <a href="{{ route('meetings.edit', $meeting) }}"
                                            class="cursor-pointer flex items-center justify-center w-10 h-10 rounded-xl text-[#2D8CFF]/50 hover:text-[#2D8CFF] hover:bg-[#2D8CFF]/10 border border-transparent hover:border-[#2D8CFF]/20 transition-all duration-200"
                                            title="Edit Meeting">
                                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0115.75 21H5.25A2.25 2.25 0 013 18.75V8.25A2.25 2.25 0 015.25 6H10" />
                                            </svg>
                                        </a>

                                        {{-- Delete --}}
                                        <button type="button"
                                            onclick="confirmDelete({{ $meeting->id }}, '{{ addslashes($meeting->topic) }}')"
                                            class="cursor-pointer flex items-center justify-center w-10 h-10 rounded-xl text-red-400/50 hover:text-red-400 hover:bg-red-500/10 border border-transparent hover:border-red-500/20 transition-all duration-200"
                                            aria-label="Batalkan meeting {{ $meeting->topic }}">
                                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                            </svg>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            {{-- Completed/Past Meetings Section --}}
            @if($pastMeetings->count() > 0)
                <div id="past-section">
                    <h2 class="text-sm font-bold tracking-wider text-white/20 uppercase mb-4 flex items-center gap-2">
                        <span class="w-2 h-2 rounded-full bg-white/20"></span>
                        Rapat Selesai
                    </h2>
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                        @foreach($pastMeetings as $index => $meeting)
                            <div class="meeting-card glass-card opacity-50 hover:opacity-90 rounded-2xl p-6 flex flex-col justify-between hover:border-white/10 transition-all duration-300 animate-fade-in-up"
                                 data-type="scheduled"
                                 style="animation-delay: {{ $index * 50 }}ms">
                                
                                <div>
                                    {{-- Meeting Header --}}
                                    <div class="flex justify-between items-start gap-3 mb-4">
                                        <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[10px] font-semibold tracking-wide uppercase bg-white/5 border border-white/5 text-white/30">
                                            Selesai
                                        </span>
                                    </div>

                                    {{-- Topic & Agenda --}}
                                    <h3 class="text-white/60 font-bold text-lg mb-1 leading-snug truncate line-through">
                                        <a href="{{ route('meetings.show', $meeting) }}" class="hover:text-[#2D8CFF] transition-colors">
                                            {{ $meeting->topic }}
                                        </a>
                                    </h3>
                                    <p class="text-white/20 text-xs line-clamp-2 mb-4 leading-relaxed">
                                        {{ $meeting->agenda ?? 'Tidak ada deskripsi.' }}
                                    </p>
                                </div>

                                <div>
                                    {{-- Detail list --}}
                                    <div class="space-y-2 border-t border-white/5 pt-4 mb-6">
                                        {{-- Account/Host --}}
                                        <div class="flex items-center justify-between text-xs">
                                            <span class="text-white/20">Akun Host</span>
                                            <span class="text-white/40 font-medium truncate max-w-[150px]">
                                                {{ $meeting->zoomAccount->account_name }}
                                            </span>
                                        </div>

                                        {{-- Date/Time --}}
                                        <div class="flex items-center justify-between text-xs">
                                            <span class="text-white/20">Waktu</span>
                                            <span class="text-white/40 font-medium">
                                                @if($meeting->start_time)
                                                    {{ $meeting->start_time->setTimezone($meeting->timezone)->format('d/m/y — H:i') }}
                                                @else
                                                    Mulai Sekarang
                                                @endif
                                            </span>
                                        </div>

                                        {{-- Duration --}}
                                        <div class="flex items-center justify-between text-xs">
                                            <span class="text-white/20">Durasi</span>
                                            <span class="text-white/40 font-medium">{{ $meeting->duration }} Menit</span>
                                        </div>
                                    </div>

                                    {{-- Action buttons --}}
                                    <div class="flex items-center gap-2">
                                        {{-- Details Link --}}
                                        <a href="{{ route('meetings.show', $meeting) }}" class="flex-1 text-center py-2.5 rounded-xl text-xs font-semibold text-white/40 hover:text-white/80 bg-white/5 hover:bg-white/10 border border-white/5 hover:border-white/10 transition-all flex items-center justify-center gap-1">
                                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z" />
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                            </svg>
                                            Lihat Detail
                                        </a>

                                        {{-- Delete --}}
                                        <button type="button"
                                            onclick="confirmDelete({{ $meeting->id }}, '{{ addslashes($meeting->topic) }}')"
                                            class="cursor-pointer flex items-center justify-center w-10 h-10 rounded-xl text-red-400/40 hover:text-red-400 hover:bg-red-500/10 border border-transparent hover:border-red-500/10 transition-all duration-200"
                                            aria-label="Hapus riwayat meeting {{ $meeting->topic }}">
                                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                            </svg>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif
        @else
            {{-- Empty State --}}
            <div class="glass-card rounded-2xl p-12 text-center animate-fade-in-up max-w-lg mx-auto">
                <div class="inline-flex items-center justify-center w-20 h-20 rounded-2xl bg-gradient-to-br from-[#2D8CFF]/10 to-[#0E71EB]/10 border border-[#2D8CFF]/20 mb-6">
                    <svg class="w-10 h-10 text-[#2D8CFF]/60" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5" />
                    </svg>
                </div>
                <h2 class="text-xl font-bold text-white mb-2">Belum ada meeting terjadwal</h2>
                <p class="text-white/40 text-sm mb-6 leading-relaxed">
                    Anda bisa membuat meeting instan untuk langsung dipakai, atau menjadwalkan rapat di masa mendatang dengan memilih salah satu akun Zoom Anda.
                </p>
                <a href="{{ route('meetings.create') }}" class="btn-primary inline-flex items-center gap-2" id="create-meeting-empty-btn">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                    </svg>
                    Buat Rapat Pertama
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
                <h3 class="text-lg font-bold text-white mb-1">Batalkan Meeting?</h3>
                <p class="text-white/40 text-sm mb-6">
                    Rapat <span id="delete-meeting-name" class="text-white/60 font-medium"></span> akan dihapus dari aplikasi dan dibatalkan di server Zoom.
                </p>

                <div class="flex items-center gap-3">
                    <button type="button" onclick="closeDeleteModal()" class="cursor-pointer flex-1 px-4 py-2.5 rounded-xl text-sm font-medium text-white/60 bg-white/5 hover:bg-white/10 border border-white/10 transition-all duration-200">
                        Batal
                    </button>
                    <form id="delete-form" method="POST" class="flex-1">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="cursor-pointer w-full px-4 py-2.5 rounded-xl text-sm font-medium text-white bg-red-500/80 hover:bg-red-500 transition-all duration-200 active:scale-[0.97]">
                            Ya, Batalkan
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
        function filterMeetings(filter) {
            const cards = document.querySelectorAll('.meeting-card');
            const tabs = {
                all: document.getElementById('tab-all'),
                scheduled: document.getElementById('tab-scheduled'),
                instant: document.getElementById('tab-instant')
            };

            // Toggle tabs styling
            Object.keys(tabs).forEach(key => {
                if (key === filter) {
                    tabs[key].classList.add('border-[#2D8CFF]', 'text-white');
                    tabs[key].classList.remove('border-transparent', 'text-white/40');
                } else {
                    tabs[key].classList.remove('border-[#2D8CFF]', 'text-white');
                    tabs[key].classList.add('border-transparent', 'text-white/40');
                }
            });

            // Filter cards
            cards.forEach(card => {
                const type = card.getAttribute('data-type');
                if (filter === 'all' || type === filter) {
                    card.classList.remove('hidden');
                } else {
                    card.classList.add('hidden');
                }
            });
        }

        function confirmDelete(meetingId, meetingTopic) {
            const modal = document.getElementById('delete-modal');
            const form = document.getElementById('delete-form');
            const nameEl = document.getElementById('delete-meeting-name');

            form.action = `/meetings/${meetingId}`;
            nameEl.textContent = `"${meetingTopic}"`;
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
