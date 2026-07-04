<x-layouts.app :title="'Daftar Meeting - Zoom Scheduler'">
    @php
        $totalMeetings = $ongoingMeetings->count() + $upcomingMeetings->count() + $finishedMeetings->count();
        $scheduledCount = $ongoingMeetings->merge($upcomingMeetings)->merge($finishedMeetings)->filter(fn($m) => $m->isScheduled())->count();
        $instantCount = $ongoingMeetings->merge($upcomingMeetings)->merge($finishedMeetings)->filter(fn($m) => $m->isInstant())->count();
        $recordingCount = $ongoingMeetings->merge($upcomingMeetings)->merge($finishedMeetings)->filter(fn($m) => $m->hasRecording())->count();
    @endphp

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-8">
            <div>
                <h1 class="text-2xl sm:text-3xl font-bold bg-gradient-to-r from-white to-white/60 bg-clip-text text-transparent">
                    Jadwal Meeting Zoom
                </h1>
                <p class="text-white/40 text-sm mt-1">
                    Pantau rapat aktif, jadwal mendatang, riwayat selesai, dan rekaman dari satu tempat.
                </p>
            </div>
            <a href="{{ route('meetings.create') }}" class="btn-primary inline-flex items-center gap-2 shrink-0 self-start sm:self-auto min-h-11" id="create-meeting-btn">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                </svg>
                Buat Meeting Baru
            </a>
        </div>

        @if($totalMeetings > 0)
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 mb-6">
                <div class="rounded-2xl border border-white/10 bg-white/[0.03] px-4 py-3">
                    <p class="text-[11px] uppercase tracking-wide text-white/35 font-semibold">Berlangsung</p>
                    <p class="mt-1 text-2xl font-bold text-emerald-300">{{ $ongoingMeetings->count() }}</p>
                </div>
                <div class="rounded-2xl border border-white/10 bg-white/[0.03] px-4 py-3">
                    <p class="text-[11px] uppercase tracking-wide text-white/35 font-semibold">Mendatang</p>
                    <p class="mt-1 text-2xl font-bold text-[#8EC5FF]">{{ $upcomingMeetings->count() }}</p>
                </div>
                <div class="rounded-2xl border border-white/10 bg-white/[0.03] px-4 py-3">
                    <p class="text-[11px] uppercase tracking-wide text-white/35 font-semibold">Selesai</p>
                    <p class="mt-1 text-2xl font-bold text-white/70">{{ $finishedMeetings->count() }}</p>
                </div>
                <div class="rounded-2xl border border-white/10 bg-white/[0.03] px-4 py-3">
                    <p class="text-[11px] uppercase tracking-wide text-white/35 font-semibold">Rekaman</p>
                    <p class="mt-1 text-2xl font-bold text-emerald-300">{{ $recordingCount }}</p>
                </div>
            </div>

            <div class="flex overflow-x-auto border-b border-white/5 gap-5 mb-8 pb-px">
                <button type="button" onclick="filterMeetings('all')" id="tab-all" class="cursor-pointer shrink-0 min-h-11 pb-3 text-sm font-semibold border-b-2 border-[#2D8CFF] text-white transition-all">
                    Semua ({{ $totalMeetings }})
                </button>
                <button type="button" onclick="filterMeetings('ongoing')" id="tab-ongoing" class="cursor-pointer shrink-0 min-h-11 pb-3 text-sm font-semibold border-b-2 border-transparent text-white/40 hover:text-white/70 transition-all">
                    Berlangsung ({{ $ongoingMeetings->count() }})
                </button>
                <button type="button" onclick="filterMeetings('scheduled')" id="tab-scheduled" class="cursor-pointer shrink-0 min-h-11 pb-3 text-sm font-semibold border-b-2 border-transparent text-white/40 hover:text-white/70 transition-all">
                    Terjadwal ({{ $scheduledCount }})
                </button>
                <button type="button" onclick="filterMeetings('instant')" id="tab-instant" class="cursor-pointer shrink-0 min-h-11 pb-3 text-sm font-semibold border-b-2 border-transparent text-white/40 hover:text-white/70 transition-all">
                    Instan ({{ $instantCount }})
                </button>
                <button type="button" onclick="filterMeetings('recorded')" id="tab-recorded" class="cursor-pointer shrink-0 min-h-11 pb-3 text-sm font-semibold border-b-2 border-transparent text-white/40 hover:text-white/70 transition-all">
                    Rekaman ({{ $recordingCount }})
                </button>
            </div>

            @if($ongoingMeetings->count() > 0)
                <section class="meeting-section mb-12" data-section="ongoing">
                    <div class="flex items-center justify-between gap-4 mb-4">
                        <h2 class="text-sm font-bold tracking-wider text-emerald-300 uppercase flex items-center gap-2">
                            <span class="w-2 h-2 rounded-full bg-emerald-300 animate-pulse"></span>
                            Sedang Berlangsung
                        </h2>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                        @foreach($ongoingMeetings as $index => $meeting)
                            @include('meetings.partials.meeting-card', ['meeting' => $meeting, 'variant' => 'ongoing', 'index' => $index])
                        @endforeach
                    </div>
                </section>
            @endif

            @if($upcomingMeetings->count() > 0)
                <section class="meeting-section mb-12" data-section="upcoming">
                    <h2 class="text-sm font-bold tracking-wider text-white/40 uppercase mb-4 flex items-center gap-2">
                        <span class="w-2 h-2 rounded-full bg-[#2D8CFF]"></span>
                        Mendatang & Siap Dimulai
                    </h2>
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                        @foreach($upcomingMeetings as $index => $meeting)
                            @include('meetings.partials.meeting-card', ['meeting' => $meeting, 'variant' => 'upcoming', 'index' => $index])
                        @endforeach
                    </div>
                </section>
            @endif

            @if($finishedMeetings->count() > 0)
                <section class="meeting-section" data-section="finished">
                    <h2 class="text-sm font-bold tracking-wider text-white/30 uppercase mb-4 flex items-center gap-2">
                        <span class="w-2 h-2 rounded-full bg-white/25"></span>
                        Rapat Selesai
                    </h2>
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                        @foreach($finishedMeetings as $index => $meeting)
                            @include('meetings.partials.meeting-card', ['meeting' => $meeting, 'variant' => 'finished', 'index' => $index])
                        @endforeach
                    </div>
                </section>
            @endif
        @else
            <div class="glass-card rounded-2xl p-12 text-center animate-fade-in-up max-w-lg mx-auto">
                <div class="inline-flex items-center justify-center w-20 h-20 rounded-2xl bg-gradient-to-br from-[#2D8CFF]/10 to-[#0E71EB]/10 border border-[#2D8CFF]/20 mb-6">
                    <svg class="w-10 h-10 text-[#2D8CFF]/60" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5" />
                    </svg>
                </div>
                <h2 class="text-xl font-bold text-white mb-2">Belum ada meeting terjadwal</h2>
                <p class="text-white/40 text-sm mb-6 leading-relaxed">
                    Buat meeting dari web, atau aktifkan callback Zoom agar jadwal yang dibuat langsung di Zoom ikut tersinkron.
                </p>
                <a href="{{ route('meetings.create') }}" class="btn-primary inline-flex items-center gap-2 min-h-11" id="create-meeting-empty-btn">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                    </svg>
                    Buat Rapat Pertama
                </a>
            </div>
        @endif
    </div>

    <div id="delete-modal" class="fixed inset-0 z-[100] hidden items-center justify-center p-4" role="dialog" aria-modal="true">
        <div class="absolute inset-0 bg-black/60 backdrop-blur-sm" onclick="closeDeleteModal()"></div>

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
                    <button type="button" onclick="closeDeleteModal()" class="cursor-pointer flex-1 min-h-11 px-4 py-2.5 rounded-xl text-sm font-medium text-white/60 bg-white/5 hover:bg-white/10 border border-white/10 transition-all duration-200">
                        Batal
                    </button>
                    <form id="delete-form" method="POST" class="flex-1">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="cursor-pointer w-full min-h-11 px-4 py-2.5 rounded-xl text-sm font-medium text-white bg-red-500/80 hover:bg-red-500 transition-all duration-200 active:scale-[0.97]">
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
            const sections = document.querySelectorAll('.meeting-section');
            const tabs = {
                all: document.getElementById('tab-all'),
                ongoing: document.getElementById('tab-ongoing'),
                scheduled: document.getElementById('tab-scheduled'),
                instant: document.getElementById('tab-instant'),
                recorded: document.getElementById('tab-recorded')
            };

            Object.keys(tabs).forEach(key => {
                if (!tabs[key]) return;

                if (key === filter) {
                    tabs[key].classList.add('border-[#2D8CFF]', 'text-white');
                    tabs[key].classList.remove('border-transparent', 'text-white/40');
                } else {
                    tabs[key].classList.remove('border-[#2D8CFF]', 'text-white');
                    tabs[key].classList.add('border-transparent', 'text-white/40');
                }
            });

            cards.forEach(card => {
                const type = card.getAttribute('data-type');
                const state = card.getAttribute('data-state');
                const hasRecording = card.querySelector('a[aria-label^="Cek rekaman"], a[aria-label^="Buka rekaman"]');
                const shouldShow =
                    filter === 'all' ||
                    filter === type ||
                    filter === state ||
                    (filter === 'recorded' && hasRecording);

                card.classList.toggle('hidden', !shouldShow);
            });

            sections.forEach(section => {
                const visibleCards = section.querySelectorAll('.meeting-card:not(.hidden)');
                section.classList.toggle('hidden', visibleCards.length === 0);
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
