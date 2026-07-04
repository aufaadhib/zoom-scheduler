<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? 'Zoom Scheduler' }}</title>
    <meta name="description" content="Kelola beberapa akun Zoom dengan mudah. Simpan dan atur token OAuth2 untuk setiap akun.">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-dvh bg-[#0a0a1a] text-white font-['Plus_Jakarta_Sans'] antialiased">
    {{-- Animated background blobs --}}
    <div class="fixed inset-0 overflow-hidden pointer-events-none" aria-hidden="true">
        <div class="absolute -top-40 -left-40 w-96 h-96 bg-[#2D8CFF]/10 rounded-full blur-3xl animate-blob"></div>
        <div class="absolute top-1/3 -right-20 w-80 h-80 bg-[#0E71EB]/8 rounded-full blur-3xl animate-blob animation-delay-2000"></div>
        <div class="absolute -bottom-40 left-1/3 w-96 h-96 bg-[#2D8CFF]/6 rounded-full blur-3xl animate-blob animation-delay-4000"></div>
    </div>

    {{-- Navigation --}}
    <nav class="relative z-50 border-b border-white/5 backdrop-blur-xl bg-white/[0.02]">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-16">
                
                {{-- Left Side: Mobile Menu Button & Logo --}}
                <div class="flex items-center gap-3">
                    @auth
                        {{-- Mobile Hamburger Button --}}
                        <button type="button" onclick="toggleMobileMenu()" class="sm:hidden p-2 -ml-2 rounded-lg text-white/70 hover:text-white hover:bg-white/5 transition-colors cursor-pointer">
                            <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5" />
                            </svg>
                        </button>
                    @endauth

                    {{-- Logo --}}
                    <a href="{{ route('dashboard') }}" class="flex items-center gap-3 group">
                        <div class="w-9 h-9 rounded-xl bg-gradient-to-br from-[#2D8CFF] to-[#0E71EB] flex items-center justify-center shadow-lg shadow-[#2D8CFF]/20 group-hover:shadow-[#2D8CFF]/40 transition-shadow duration-300">
                            <svg class="w-5 h-5 text-white" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 10.5l4.72-4.72a.75.75 0 011.28.53v11.38a.75.75 0 01-1.28.53l-4.72-4.72M4.5 18.75h9a2.25 2.25 0 002.25-2.25v-9a2.25 2.25 0 00-2.25-2.25h-9A2.25 2.25 0 002.25 7.5v9a2.25 2.25 0 002.25 2.25z" />
                            </svg>
                        </div>
                        <span class="text-lg font-bold bg-gradient-to-r from-white to-white/70 bg-clip-text text-transparent">Zoom Scheduler</span>
                    </a>
                </div>

                {{-- Desktop Navigation Links --}}
                @auth
                    <div class="hidden sm:flex items-center gap-6 ml-10 mr-auto">
                        <a href="{{ route('dashboard') }}" class="text-sm font-semibold {{ request()->routeIs('dashboard') ? 'text-[#2D8CFF]' : 'text-white/40 hover:text-white/70' }} transition-colors">
                            Dashboard Akun
                        </a>
                        <a href="{{ route('meetings.index') }}" class="text-sm font-semibold {{ request()->routeIs('meetings.*') ? 'text-[#2D8CFF]' : 'text-white/40 hover:text-white/70' }} transition-colors">
                            Jadwal Meeting
                        </a>
                        <a href="{{ route('settings.index') }}" class="text-sm font-semibold {{ request()->routeIs('settings.*') ? 'text-[#2D8CFF]' : 'text-white/40 hover:text-white/70' }} transition-colors">
                            Pengaturan
                        </a>
                    </div>
                @endauth

                {{-- User Menu (Desktop) --}}
                <div class="flex items-center gap-4">
                    @auth
                        <div class="hidden sm:flex items-center gap-2 px-3 py-1.5 rounded-lg bg-white/5 border border-white/5">
                            <div class="w-7 h-7 rounded-full bg-gradient-to-br from-[#2D8CFF] to-[#0E71EB] flex items-center justify-center text-xs font-bold">
                                {{ strtoupper(substr(Auth::user()->name, 0, 1)) }}
                            </div>
                            <span class="text-sm text-white/70 font-medium">{{ Auth::user()->name }}</span>
                        </div>
                        <form method="POST" action="{{ route('logout') }}" class="hidden sm:block">
                            @csrf
                            <button type="submit" class="cursor-pointer flex items-center gap-2 px-3 py-1.5 rounded-lg text-sm text-white/50 hover:text-white hover:bg-white/5 transition-all duration-200">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0013.5 3h-6a2.25 2.25 0 00-2.25 2.25v13.5A2.25 2.25 0 007.5 21h6a2.25 2.25 0 002.25-2.25V15m3 0l3-3m0 0l-3-3m3 3H9" />
                                </svg>
                                <span>Logout</span>
                            </button>
                        </form>
                    @endauth
                </div>
            </div>
        </div>
    </nav>

    {{-- Mobile Sidebar Menu --}}
    @auth
        <div id="mobile-menu-overlay" class="fixed inset-0 bg-[#0a0a1a]/80 backdrop-blur-sm z-[60] hidden opacity-0 transition-opacity duration-300" onclick="toggleMobileMenu()"></div>
        <div id="mobile-menu" class="fixed top-0 left-0 bottom-0 w-72 bg-[#0d0d1e] border-r border-white/10 z-[70] transform -translate-x-full transition-transform duration-300 shadow-2xl flex flex-col">
            <div class="p-5 border-b border-white/10 flex items-center justify-between">
                <span class="text-lg font-bold text-white">Menu</span>
                <button type="button" onclick="toggleMobileMenu()" class="p-1 rounded-lg text-white/50 hover:text-white hover:bg-white/5 transition-colors cursor-pointer">
                    <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
            
            <div class="p-4 flex flex-col gap-2 flex-1">
                <a href="{{ route('dashboard') }}" class="flex items-center gap-3 px-4 py-3 rounded-xl {{ request()->routeIs('dashboard') ? 'bg-[#2D8CFF]/10 text-[#2D8CFF] font-semibold' : 'text-white/60 hover:bg-white/5 hover:text-white' }} transition-colors">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 12l8.954-8.955c.44-.439 1.152-.439 1.591 0L21.75 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125c.621 0 1.125-.504 1.125-1.125V9.75M8.25 21h8.25" /></svg>
                    Dashboard Akun
                </a>
                <a href="{{ route('meetings.index') }}" class="flex items-center gap-3 px-4 py-3 rounded-xl {{ request()->routeIs('meetings.*') ? 'bg-[#2D8CFF]/10 text-[#2D8CFF] font-semibold' : 'text-white/60 hover:bg-white/5 hover:text-white' }} transition-colors">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5" /></svg>
                    Jadwal Meeting
                </a>
                <a href="{{ route('settings.index') }}" class="flex items-center gap-3 px-4 py-3 rounded-xl {{ request()->routeIs('settings.*') ? 'bg-[#2D8CFF]/10 text-[#2D8CFF] font-semibold' : 'text-white/60 hover:bg-white/5 hover:text-white' }} transition-colors">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9.594 3.94c.09-.542.56-.94 1.11-.94h2.593c.55 0 1.02.398 1.11.94l.213 1.281c.063.374.313.686.645.87.074.04.147.083.22.127.325.196.72.257 1.075.124l1.217-.456a1.125 1.125 0 0 1 1.37.49l1.296 2.247a1.125 1.125 0 0 1-.26 1.431l-1.003.827c-.293.241-.438.613-.43.992a7.723 7.723 0 0 1 0 .255c-.008.378.137.75.43.991l1.004.827c.424.35.534.955.26 1.43l-1.298 2.247a1.125 1.125 0 0 1-1.369.491l-1.217-.456c-.355-.133-.75-.072-1.076.124a6.47 6.47 0 0 1-.22.128c-.331.183-.581.495-.644.869l-.213 1.281c-.09.543-.56.94-1.11.94h-2.594c-.55 0-1.019-.398-1.11-.94l-.213-1.281c-.062-.374-.312-.686-.644-.87a6.52 6.52 0 0 1-.22-.127c-.325-.196-.72-.257-1.076-.124l-1.217.456a1.125 1.125 0 0 1-1.369-.49l-1.297-2.247a1.125 1.125 0 0 1 .26-1.431l1.004-.827c.292-.24.437-.613.43-.991a6.932 6.932 0 0 1 0-.255c.007-.38-.138-.751-.43-.992l-1.004-.827a1.125 1.125 0 0 1-.26-1.43l1.297-2.247a1.125 1.125 0 0 1 1.37-.491l1.216.456c.356.133.751.072 1.076-.124.072-.044.146-.086.22-.128.332-.183.582-.495.644-.869l.214-1.28Z" /><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" /></svg>
                    Pengaturan
                </a>
            </div>

            <div class="p-4 border-t border-white/10">
                <div class="flex items-center gap-3 mb-4 px-2">
                    <div class="w-8 h-8 rounded-full bg-gradient-to-br from-[#2D8CFF] to-[#0E71EB] flex items-center justify-center text-sm font-bold">
                        {{ strtoupper(substr(Auth::user()->name, 0, 1)) }}
                    </div>
                    <div class="flex flex-col">
                        <span class="text-sm text-white font-medium">{{ Auth::user()->name }}</span>
                        <span class="text-xs text-white/40">{{ Auth::user()->email ?? 'Akun Anda' }}</span>
                    </div>
                </div>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="w-full cursor-pointer flex items-center justify-center gap-2 px-4 py-2.5 rounded-xl text-sm font-medium text-red-400 bg-red-500/10 hover:bg-red-500/20 border border-red-500/20 transition-all duration-200">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0013.5 3h-6a2.25 2.25 0 00-2.25 2.25v13.5A2.25 2.25 0 007.5 21h6a2.25 2.25 0 002.25-2.25V15m3 0l3-3m0 0l-3-3m3 3H9" />
                        </svg>
                        <span>Logout</span>
                    </button>
                </form>
            </div>
        </div>
    @endauth

    {{-- Flash Messages --}}
    @if(session('success'))
        <div class="relative z-40 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 mt-4" id="flash-success">
            <div class="flex items-center gap-3 px-4 py-3 rounded-xl bg-emerald-500/10 border border-emerald-500/20 text-emerald-400 text-sm animate-slide-down">
                <svg class="w-5 h-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <span>{{ session('success') }}</span>
                <button onclick="document.getElementById('flash-success').remove()" class="ml-auto cursor-pointer text-emerald-400/50 hover:text-emerald-400 transition-colors">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
                </button>
            </div>
        </div>
    @endif

    @if(session('error'))
        <div class="relative z-40 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 mt-4" id="flash-error">
            <div class="flex items-center gap-3 px-4 py-3 rounded-xl bg-red-500/10 border border-red-500/20 text-red-400 text-sm animate-slide-down">
                <svg class="w-5 h-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z" />
                </svg>
                <span>{{ session('error') }}</span>
                <button onclick="document.getElementById('flash-error').remove()" class="ml-auto cursor-pointer text-red-400/50 hover:text-red-400 transition-colors">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
                </button>
            </div>
        </div>
    @endif

    {{-- Main Content --}}
    <main class="relative z-10">
        {{ $slot }}
    </main>

    <script>
        function toggleMobileMenu() {
            const menu = document.getElementById('mobile-menu');
            const overlay = document.getElementById('mobile-menu-overlay');
            
            if (menu.classList.contains('-translate-x-full')) {
                // Open menu
                overlay.classList.remove('hidden');
                // Small delay to allow display block to apply before animating opacity
                setTimeout(() => {
                    overlay.classList.remove('opacity-0');
                    menu.classList.remove('-translate-x-full');
                }, 10);
            } else {
                // Close menu
                overlay.classList.add('opacity-0');
                menu.classList.add('-translate-x-full');
                setTimeout(() => {
                    overlay.classList.add('hidden');
                }, 300);
            }
        }

        // Auto-dismiss flash messages after 5 seconds
        setTimeout(() => {
            ['flash-success', 'flash-error'].forEach(id => {
                const el = document.getElementById(id);
                if (el) {
                    el.style.transition = 'opacity 300ms ease-out, transform 300ms ease-out';
                    el.style.opacity = '0';
                    el.style.transform = 'translateY(-8px)';
                    setTimeout(() => el.remove(), 300);
                }
            });
        }, 5000);
    </script>
</body>
</html>
