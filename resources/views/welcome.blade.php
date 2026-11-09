<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full bg-slate-50">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>CuraPoint Clinical Operating System</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=JetBrains+Mono:wght@500;600&display=swap" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="font-sans antialiased text-slate-900 min-h-full flex flex-col selection:bg-clinical-100 selection:text-clinical-900">
<!-- Top System Bar -->
<header class="bg-white border-b border-slate-200 sticky top-0 z-40">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-14 flex items-center justify-between">
        <div class="flex items-center space-x-6">
            <div class="flex items-center space-x-2.5">
                <div class="w-7 h-7 bg-clinical-900 rounded-md flex items-center justify-center text-white font-bold text-xs">
                    +
                </div>
                <div class="flex items-center gap-2">
                    <span class="font-bold text-sm text-slate-900 tracking-tight">CuraPoint</span>
                    <span class="text-[11px] font-medium text-slate-400 border-l border-slate-200 pl-2">Clinical OS</span>
                </div>
            </div>


            {{-- Portal Switcher Navigation --}}
            @php $view = request('view', 'booking'); @endphp
            <nav class="flex items-center gap-1 bg-slate-100 p-0.5 rounded-lg border border-slate-200/70 text-xs">
                <a href="{{ url('/') }}" class="px-3 py-1 rounded-md font-medium transition {{ $view === 'booking' ? 'bg-white text-slate-900 font-semibold shadow-subtle' : 'text-slate-600 hover:text-slate-900' }}">
                    Patient Booking
                </a>
                <a href="{{ url('/?view=reception') }}" class="px-3 py-1 rounded-md font-medium transition {{ $view === 'reception' ? 'bg-white text-slate-900 font-semibold shadow-subtle' : 'text-slate-600 hover:text-slate-900' }}">
                    Reception Triage
                </a>
                <a href="{{ url('/?view=doctor') }}" class="px-3 py-1 rounded-md font-medium transition {{ $view === 'doctor' ? 'bg-white text-slate-900 font-semibold shadow-subtle' : 'text-slate-600 hover:text-slate-900' }}">
                    Doctor Console
                </a>
            </nav>
        </div>

        <div class="flex items-center gap-4">
            <div class="flex items-center gap-2 text-xs text-slate-500">
                <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                <span class="font-mono text-[11px] text-slate-500">Livewire 3 &bull; Locking Active</span>
            </div>
        </div>
    </div>
</header>


<!-- Main Workspace -->
<main class="flex-1 py-6">
    @if (request('view') === 'doctor')
        <livewire:doctor-schedule-console />
    @elseif (request('view') === 'reception')
        <livewire:receptionist-triage-console />
    @else
        <livewire:book-appointment />
    @endif
</main>

<!-- System Footer -->
<footer class="border-t border-slate-200 bg-white py-4 text-xs text-slate-400">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 flex flex-col sm:flex-row justify-between items-center gap-2">
        <p>CuraPoint Health Systems &copy; {{ date('Y') }}. High-concurrency appointment engine.</p>
        <p class="font-mono text-[11px] text-slate-400">Project 03: Livewire 3 + Concurrency Control + MySQL</p>
    </div>
</footer>

@livewireScripts
</body>
</html>
