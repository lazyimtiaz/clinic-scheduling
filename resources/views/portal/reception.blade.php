<!DOCTYPE html>
<html lang="en" class="h-full bg-slate-50">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Reception Triage Desk &bull; CuraPoint</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=JetBrains+Mono:wght@500;600&display=swap" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="font-sans antialiased text-slate-900 min-h-full flex flex-col">
<header class="bg-white border-b border-slate-200 sticky top-0 z-40">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-14 flex items-center justify-between">
        <div class="flex items-center space-x-3">
            <div class="w-7 h-7 bg-clinical-900 rounded-md flex items-center justify-center text-white font-bold text-xs">+</div>
            <span class="font-bold text-sm text-slate-900">Front Desk Triage</span>
            <span class="text-[11px] font-mono text-purple-700 bg-purple-50 px-2 py-0.5 rounded border border-purple-200">{{ auth()->user()->name }}</span>
        </div>

        <div class="flex items-center gap-3">
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="text-xs font-semibold text-rose-600 hover:text-rose-800">Sign Out</button>
            </form>
        </div>
    </div>
</header>

<main class="flex-1 py-6">
    <livewire:receptionist-triage-console />
</main>
@livewireScripts
</body>
</html>
