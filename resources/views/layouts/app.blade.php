<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="app-base" content="{{ rtrim(request()->getBasePath(), '/') }}">
    <meta name="theme-color" content="#1d4ed8">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="default">
    <meta name="apple-mobile-web-app-title" content="Workout">
    <title>@yield('title', 'Morning Workout')</title>
    <link rel="manifest" href="{{ url('/manifest.webmanifest') }}">
    <link rel="icon" href="{{ asset('icons/icon-192.png') }}" type="image/png">
    <link rel="apple-touch-icon" href="{{ asset('icons/icon-192.png') }}">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-dvh bg-paper font-sans text-ink antialiased">
    @hasSection('full')
        @yield('full')
    @else
        <div class="mx-auto min-h-dvh max-w-md pb-40">
            <main class="px-4 pt-6">
                @yield('content')
            </main>
        </div>
    @endif

    <nav class="fixed inset-x-0 bottom-0 z-10 px-4 pb-[max(0.75rem,env(safe-area-inset-bottom))]" aria-label="Primary">
        <div class="mx-auto flex max-w-md gap-1 rounded-full bg-card/90 p-1 shadow-lg shadow-pine/10 ring-1 ring-line backdrop-blur">
            <a
                href="{{ route('today') }}"
                @class([
                    'flex min-h-12 flex-1 items-center justify-center rounded-full text-base font-semibold',
                    'bg-pine text-white' => request()->routeIs('today'),
                    'text-muted' => ! request()->routeIs('today'),
                ])
                @if (request()->routeIs('today')) aria-current="page" @endif
            >
                Today
            </a>
            <a
                href="{{ route('calendar') }}"
                @class([
                    'flex min-h-12 flex-1 items-center justify-center rounded-full text-base font-semibold',
                    'bg-pine text-white' => request()->routeIs('calendar', 'calendar.show'),
                    'text-muted' => ! request()->routeIs('calendar', 'calendar.show'),
                ])
                @if (request()->routeIs('calendar', 'calendar.show')) aria-current="page" @endif
            >
                Calendar
            </a>
        </div>
    </nav>

    <script>
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', () => {
                navigator.serviceWorker.register('{{ asset('sw.js') }}');
            });
        }
    </script>
</body>
</html>
