<div class="max-w-4xl mx-auto py-8 px-4">
    <div class="bg-white rounded-xl border border-slate-200 p-6 shadow-xs mb-6">
        <h1 class="text-lg font-bold text-slate-900">Patient Consultation History</h1>
        <p class="text-xs text-slate-500 mt-1">Logged in as {{ auth()->user()->email }}</p>
    </div>

    @if ($feedbackMessage)
        <div class="mb-4 p-3 bg-emerald-50 border border-emerald-200 text-xs text-emerald-800 rounded-lg">
            {{ $feedbackMessage }}
        </div>
    @endif

    <div class="bg-white rounded-xl border border-slate-200 shadow-xs divide-y divide-slate-100">
        @forelse ($appointments as $apt)
            <div class="p-4 flex items-center justify-between text-xs">
                <div>
                    <span class="font-mono font-bold text-slate-900">{{ $apt->appointment_number }}</span>
                    <p class="text-slate-700 font-semibold mt-0.5">{{ $apt->doctor->name }} &bull; {{ $apt->start_time->format('l, M j, Y @ h:i A') }}</p>
                    <p class="text-slate-400 text-[11px]">{{ $apt->clinic->name }}</p>
                </div>
                <div class="flex items-center gap-3">
                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold border {{ $apt->status->badgeColor() }}">
                        {{ $apt->status->label() }}
                    </span>
                    @if ($apt->status === \App\Enums\AppointmentStatus::Confirmed)
                        <button wire:click="cancelMyAppointment({{ $apt->id }})" class="text-rose-600 hover:underline text-xs">
                            Cancel
                        </button>
                    @endif
                </div>
            </div>
        @empty
            <div class="p-8 text-center text-xs text-slate-400">
                You have no recorded appointments with this account.
            </div>
        @endforelse
    </div>
</div>
