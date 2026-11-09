<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6 space-y-6">
    {{-- Toast feedback --}}
    @if ($feedbackMessage)
        <div class="rounded-lg bg-slate-900 text-white p-3 text-xs flex items-center justify-between shadow-subtle">
            <div class="flex items-center gap-2">
                <span class="w-1.5 h-1.5 rounded-full bg-emerald-400"></span>
                <span>{{ $feedbackMessage }}</span>
            </div>
            <button wire:click="$set('feedbackMessage', null)" class="text-slate-400 hover:text-white">&times;</button>
        </div>
    @endif

    {{-- Top Action Bar --}}
    <div class="bg-white rounded-xl border border-slate-200 shadow-card p-5 flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <h2 class="text-base font-bold text-slate-900 tracking-tight">Front Desk Triage & Check-in</h2>
            <p class="text-xs text-slate-500 mt-0.5">Manage patient arrivals, lobby queues, and doctor handoffs</p>
        </div>

        <div class="flex items-center gap-3">
            <div class="relative">
                <input
                    type="text"
                    wire:model.live.debounce.300ms="searchQuery"
                    placeholder="Search patient, phone, or Ref #..."
                    class="w-64 text-xs rounded-lg border-slate-200 bg-slate-50 py-1.5 pl-8 pr-3 text-slate-900 focus:border-slate-900 focus:ring-slate-900">
                <svg class="w-3.5 h-3.5 text-slate-400 absolute left-2.5 top-2.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                </svg>
            </div>

            <input
                type="date"
                wire:model.live="filterDate"
                class="text-xs rounded-lg border-slate-200 bg-slate-50 py-1.5 px-2.5 font-mono text-slate-800 focus:border-slate-900 focus:ring-slate-900">
        </div>
    </div>

    {{-- TRIAGE QUEUE COLUMNS (3 Kanban-Style Swimlanes) --}}
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 items-start">

        {{-- LANE 1: EXPECTED ARRIVALS (Confirmed) --}}
        <div class="bg-white rounded-xl border border-slate-200 shadow-card overflow-hidden">
            <div class="p-3.5 bg-slate-50 border-b border-slate-200 flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <span class="w-2 h-2 rounded-full bg-blue-500"></span>
                    <span class="text-xs font-bold text-slate-900 uppercase tracking-wider">Expected Today</span>
                </div>
                <span class="font-mono text-xs font-semibold text-slate-500 bg-white px-2 py-0.5 rounded border border-slate-200">{{ $expected->count() }}</span>
            </div>

            <div class="p-3 space-y-2.5 max-h-[600px] overflow-y-auto">
                @forelse ($expected as $apt)
                    <div class="bg-slate-50/60 rounded-lg p-3 border border-slate-200/80 hover:bg-white transition space-y-2">
                        <div class="flex items-center justify-between">
                            <span class="font-mono font-bold text-xs text-slate-900">{{ $apt->start_time->format('h:i A') }}</span>
                            <span class="font-mono text-[10px] text-slate-500">{{ $apt->appointment_number }}</span>
                        </div>
                        <div>
                            <p class="font-bold text-xs text-slate-900">{{ $apt->patient->full_name }}</p>
                            <p class="text-[11px] text-slate-500">Dr. {{ $apt->doctor->name }} ({{ $apt->doctor->specialization }})</p>
                        </div>
                        <div class="pt-2 border-t border-slate-200/60 flex justify-end">
                            <button
                                type="button"
                                wire:click="markAsArrived({{ $apt->id }})"
                                class="px-2.5 py-1 rounded text-[11px] font-bold text-purple-700 bg-purple-50 hover:bg-purple-100 border border-purple-200 transition">
                                Check In &rarr;
                            </button>
                        </div>
                    </div>
                @empty
                    <p class="text-center py-8 text-xs text-slate-400">No pending arrivals</p>
                @endforelse
            </div>
        </div>

        {{-- LANE 2: WAITING ROOM LOBBY (Arrived) --}}
        <div class="bg-white rounded-xl border border-purple-200 shadow-card overflow-hidden">
            <div class="p-3.5 bg-purple-50/60 border-b border-purple-200 flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <span class="w-2 h-2 rounded-full bg-purple-500 animate-pulse"></span>
                    <span class="text-xs font-bold text-purple-900 uppercase tracking-wider">Waiting Room</span>
                </div>
                <span class="font-mono text-xs font-bold text-purple-800 bg-white px-2 py-0.5 rounded border border-purple-200">{{ $waitingRoom->count() }}</span>
            </div>

            <div class="p-3 space-y-2.5 max-h-[600px] overflow-y-auto">
                @forelse ($waitingRoom as $apt)
                    <div class="bg-white rounded-lg p-3 border border-purple-100 shadow-xs space-y-2">
                        <div class="flex items-center justify-between">
                            <span class="font-mono font-bold text-xs text-purple-900">Slot: {{ $apt->start_time->format('h:i A') }}</span>
                            <span class="text-[10px] text-purple-700 font-semibold bg-purple-50 px-1.5 py-0.5 rounded border border-purple-200">Checked In</span>
                        </div>
                        <div>
                            <p class="font-bold text-xs text-slate-900">{{ $apt->patient->full_name }}</p>
                            <p class="text-[11px] text-slate-500 font-mono">{{ $apt->patient->phone }}</p>
                            <p class="text-[11px] text-slate-600 mt-1 font-medium">Attending: {{ $apt->doctor->name }}</p>
                        </div>
                        <div class="pt-2 border-t border-slate-100 flex justify-end">
                            <button
                                type="button"
                                wire:click="markInConsultation({{ $apt->id }})"
                                class="px-2.5 py-1 rounded text-[11px] font-bold text-white bg-slate-900 hover:bg-slate-800 transition">
                                Call into Office &rarr;
                            </button>
                        </div>
                    </div>
                @empty
                    <p class="text-center py-8 text-xs text-slate-400">Waiting room is empty</p>
                @endforelse
            </div>
        </div>

        {{-- LANE 3: IN CONSULTATION & RECENTLY COMPLETED --}}
        <div class="bg-white rounded-xl border border-slate-200 shadow-card overflow-hidden">
            <div class="p-3.5 bg-slate-50 border-b border-slate-200 flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                    <span class="text-xs font-bold text-slate-900 uppercase tracking-wider">In Office / Completed</span>
                </div>
                <span class="font-mono text-xs font-semibold text-slate-500 bg-white px-2 py-0.5 rounded border border-slate-200">
                    {{ $inConsultation->count() }} active &bull; {{ $completed->count() }} done
                </span>
            </div>

            <div class="p-3 space-y-2.5 max-h-[600px] overflow-y-auto">
                @foreach ($inConsultation as $apt)
                    <div class="bg-indigo-50/50 rounded-lg p-3 border border-indigo-200 space-y-1">
                        <div class="flex items-center justify-between">
                            <span class="font-bold text-xs text-indigo-900">{{ $apt->patient->full_name }}</span>
                            <span class="text-[10px] font-bold text-indigo-700 bg-white px-1.5 py-0.5 rounded border border-indigo-200">In Office</span>
                        </div>
                        <p class="text-[11px] text-indigo-800">Examining with {{ $apt->doctor->name }}</p>
                    </div>
                @endforeach

                @foreach ($completed->take(5) as $apt)
                    <div class="bg-slate-50 rounded-lg p-3 border border-slate-200/60 opacity-75 space-y-1">
                        <div class="flex items-center justify-between">
                            <span class="font-semibold text-xs text-slate-700">{{ $apt->patient->full_name }}</span>
                            <span class="text-[10px] text-emerald-700 bg-emerald-50 px-1.5 py-0.5 rounded border border-emerald-200">Finished</span>
                        </div>
                        <p class="text-[10px] font-mono text-slate-400">Completed with {{ $apt->doctor->name }}</p>
                    </div>
                @endforeach

                @if ($inConsultation->isEmpty() && $completed->isEmpty())
                    <p class="text-center py-8 text-xs text-slate-400">No active sessions</p>
                @endif
            </div>
        </div>

    </div>
</div>
