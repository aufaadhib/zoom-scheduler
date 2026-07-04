<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? 'Zoom Scheduler' }}</title>
    <meta name="description" content="Login ke Zoom Scheduler untuk mengelola akun Zoom Anda.">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-dvh bg-[#0a0a1a] text-white font-['Plus_Jakarta_Sans'] antialiased flex items-center justify-center p-4">
    {{-- Animated background --}}
    <div class="fixed inset-0 overflow-hidden pointer-events-none" aria-hidden="true">
        <div class="absolute -top-40 -left-40 w-[500px] h-[500px] bg-[#2D8CFF]/8 rounded-full blur-[100px] animate-blob"></div>
        <div class="absolute top-1/2 -right-20 w-[400px] h-[400px] bg-[#0E71EB]/6 rounded-full blur-[100px] animate-blob animation-delay-2000"></div>
        <div class="absolute -bottom-40 left-1/4 w-[450px] h-[450px] bg-[#2D8CFF]/5 rounded-full blur-[100px] animate-blob animation-delay-4000"></div>
    </div>

    {{-- Content --}}
    <div class="relative z-10 w-full max-w-md">
        {{ $slot }}
    </div>
</body>
</html>
