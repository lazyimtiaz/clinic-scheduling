<!DOCTYPE html>
<html lang="en" class="h-full bg-slate-50">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Clinical Staff & Patient Portal Sign In &bull; CuraPoint</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=JetBrains+Mono:wght@500;600&display=swap" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="font-sans antialiased text-slate-900 min-h-full flex items-center justify-center p-4">
<div class="max-w-md w-full space-y-6">
    {{-- Brand Header --}}
    <div class="text-center">
        <div class="w-10 h-10 bg-clinical-900 rounded-lg flex items-center justify-center text-white font-bold text-sm mx-auto shadow-subtle mb-3">
            +
        </div>
        <h1 class="text-xl font-bold text-slate-900 tracking-tight">CuraPoint Clinical OS</h1>
        <p class="text-xs text-slate-500 mt-1">Authorized access for practitioners, triage staff, and patients</p>
    </div>

    {{-- Login Card --}}
    <div class="bg-white rounded-xl border border-slate-200 shadow-card p-6 sm:p-8 space-y-5">
        <form method="POST" action="{{ route('login.post') }}" class="space-y-4">
            @csrf

            <div>
                <label class="block text-[10px] font-bold uppercase tracking-wider text-slate-500 mb-1">Email Address</label>
                <input
                    type="email"
                    name="email"
                    value="{{ old('email') }}"
                    required
                    autofocus
                    class="w-full text-xs rounded-lg border-slate-200 bg-white py-2 px-3 text-slate-900 focus:border-slate-900 focus:ring-slate-900">
                @error('email')
                <span class="text-[11px] text-rose-600 mt-1 block">{{ $message }}</span>
                @enderror
            </div>

            <div>
                <label class="block text-[10px] font-bold uppercase tracking-wider text-slate-500 mb-1">Password</label>
                <input
                    type="password"
                    name="password"
                    required
                    class="w-full text-xs rounded-lg border-slate-200 bg-white py-2 px-3 text-slate-900 focus:border-slate-900 focus:ring-slate-900">
            </div>

            <div class="flex items-center justify-between text-xs">
                <label class="flex items-center gap-2 cursor-pointer">
                    <input type="checkbox" name="remember" class="rounded border-slate-300 text-slate-900 focus:ring-slate-900">
                    <span class="text-slate-600 text-[11px]">Remember session</span>
                </label>
                <a href="{{ route('home') }}" class="text-[11px] font-medium text-slate-500 hover:text-slate-900">&larr; Back to Booking</a>
            </div>

            <button
                type="submit"
                class="w-full h-9 rounded-lg font-semibold text-xs text-white bg-slate-900 hover:bg-slate-800 transition shadow-subtle">
                Sign In to Portal
            </button>
        </form>

        {{-- Demo Accounts Quick-Paste Box (High hiring value) --}}
        <div class="mt-6 pt-4 border-t border-slate-100 text-[11px] text-slate-500 space-y-2">
            <span class="font-bold text-slate-700 uppercase tracking-wider text-[10px] block">Demo Credentials:</span>
            <div class="grid grid-cols-1 gap-1.5 font-mono text-[10px]">
                <div class="bg-slate-50 p-1.5 rounded border border-slate-100 flex justify-between">
                    <span><strong>Doctor:</strong> sarah@metrohealth.example.com</span>
                    <span class="text-slate-400">password</span>
                </div>
                <div class="bg-slate-50 p-1.5 rounded border border-slate-100 flex justify-between">
                    <span><strong>Reception:</strong> reception@metrohealth.example.com</span>
                    <span class="text-slate-400">password</span>
                </div>
            </div>
        </div>
    </div>
</div>
</body>
</html>
