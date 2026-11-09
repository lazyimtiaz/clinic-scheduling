<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
    {{-- Toast Notification --}}
    @if ($feedbackMessage)
        <div class="mb-4 rounded-lg bg-slate-900 text-white p-3.5 text-xs flex items-center justify-between shadow-elevated">
            <div class="flex items-center gap-2">
                <span class="w-1.5 h-1.5 rounded-full bg-emerald-400"></span>
                <span>{{ $feedbackMessage }}</span>
            </div>
            <button wire:click="$set('feedbackMessage', null)" class="text-slate-400 hover:text-white font-bold">&times;</button>
        </div>
    @endif
    @if (! $selectedDoctor)
        <div class="bg-white rounded-xl border border-slate-200 shadow-card p-12 text-center max-w-lg mx-auto my-12">
            <p class="text-xs font-semibold text-slate-600">No clinicians registered in the system.</p>
        </div>
    @else



    {{-- Top Action & Filter Console --}}
    <div class="bg-white rounded-xl border border-slate-200 shadow-card p-5 mb-6 space-y-4">
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
            {{-- Doctor Selector & Date --}}
            <div class="flex flex-wrap items-center gap-3">
                <div class="w-64">
                    <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Attending Clinician</label>
                    <select wire:model.live="selectedDoctorId" class="w-full text-xs rounded-lg border-slate-200 bg-slate-50 py-1.5 px-2.5 text-slate-800 font-semibold focus:border-slate-900 focus:ring-slate-900">
                        @foreach ($doctors as $doc)
                            <option value="{{ $doc->id }}">{{ $doc->name }} ({{ $doc->specialization }})</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Schedule Date</label>
                    <div class="flex items-center gap-1.5">
                        <input type="date" wire:model.live="selectedDate" class="text-xs rounded-lg border-slate-200 bg-slate-50 py-1.5 px-2.5 font-mono text-slate-800 focus:border-slate-900 focus:ring-slate-900">
                        <button type="button" wire:click="setToday" class="px-2.5 py-1.5 rounded-lg border border-slate-200 text-[11px] font-medium text-slate-600 hover:bg-slate-50">Today</button>
                        <button type="button" wire:click="setTomorrow" class="px-2.5 py-1.5 rounded-lg border border-slate-200 text-[11px] font-medium text-slate-600 hover:bg-slate-50">Tomorrow</button>
                    </div>
                </div>
            </div>

            {{-- Daily Summary Counter Strip --}}
            <div class="flex items-center gap-2 border-t md:border-t-0 md:border-l border-slate-100 pt-3 md:pt-0 md:pl-4">
                <div class="text-center px-3 py-1 bg-slate-50 rounded-lg border border-slate-100">
                    <span class="text-[9px] font-bold uppercase text-slate-400 block">Total</span>
                    <span class="font-mono text-xs font-bold text-slate-900">{{ $stats['total'] }}</span>
                </div>
                <div class="text-center px-3 py-1 bg-emerald-50 rounded-lg border border-emerald-100">
                    <span class="text-[9px] font-bold uppercase text-emerald-600 block">Confirmed</span>
                    <span class="font-mono text-xs font-bold text-emerald-800">{{ $stats['confirmed'] }}</span>
                </div>
                <div class="text-center px-3 py-1 bg-blue-50 rounded-lg border border-blue-100">
                    <span class="text-[9px] font-bold uppercase text-blue-600 block">Completed</span>
                    <span class="font-mono text-xs font-bold text-blue-800">{{ $stats['completed'] }}</span>
                </div>
                <div class="text-center px-3 py-1 bg-rose-50 rounded-lg border border-rose-100">
                    <span class="text-[9px] font-bold uppercase text-rose-600 block">Cancelled</span>
                    <span class="font-mono text-xs font-bold text-rose-800">{{ $stats['cancelled'] }}</span>
                </div>
            </div>
        </div>

        {{-- Status Filter Tabs --}}
        <div class="pt-3 border-t border-slate-100 flex items-center justify-between text-xs">
            <div class="flex items-center gap-1">
                @foreach (['all' => 'All Entries', 'confirmed' => 'Active', 'completed' => 'Completed', 'cancelled' => 'Cancelled'] as $key => $label)
                    <button
                        type="button"
                        wire:click="$set('statusFilter', '{{ $key }}')"
                        class="px-3 py-1 rounded-md text-[11px] font-medium transition {{ $statusFilter === $key ? 'bg-slate-900 text-white font-semibold' : 'text-slate-600 hover:bg-slate-100' }}">
                        {{ $label }}
                    </button>
                @endforeach
            </div>

            <div class="text-slate-400 text-[11px] font-mono">
                {{ \Carbon\Carbon::parse($selectedDate)->format('D, M j, Y') }}
            </div>
        </div>
    </div>
    @endif

    {{-- Appointment Ledger Table --}}
    <div class="bg-white rounded-xl border border-slate-200 shadow-card overflow-hidden">
        @if ($appointments->isEmpty())
            <div class="text-center py-16 px-4">
                <div class="w-10 h-10 rounded-full bg-slate-100 flex items-center justify-center mx-auto text-slate-400 mb-2">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                    </svg>
                </div>
                <p class="text-xs font-bold text-slate-700">No Appointments on Ledger</p>
                <p class="text-[11px] text-slate-400 mt-0.5">There are no records matching your doctor or status filters for this date.</p>
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs border-collapse">
                    <thead>
                    <tr class="bg-slate-50 border-b border-slate-200 text-[10px] font-bold uppercase tracking-wider text-slate-500">
                        <th class="py-3 px-4">Time</th>
                        <th class="py-3 px-4">Reference</th>
                        <th class="py-3 px-4">Patient Profile</th>
                        <th class="py-3 px-4">Clinical Symptoms / Notes</th>
                        <th class="py-3 px-4">Status</th>
                        <th class="py-3 px-4 text-right">Ledger Actions</th>
                    </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                    @foreach ($appointments as $apt)
                        <tr class="hover:bg-slate-50/70 transition">
                            {{-- Time Slot --}}
                            <td class="py-3.5 px-4 whitespace-nowrap">
                                <span class="font-mono font-bold text-slate-900">{{ $apt->start_time->format('h:i A') }}</span>
                                <span class="block text-[10px] font-mono text-slate-400">{{ $apt->end_time->format('h:i A') }}</span>
                            </td>

                            {{-- Reference ID --}}
                            <td class="py-3.5 px-4 whitespace-nowrap">
                                    <span class="font-mono text-[11px] text-slate-700 font-semibold bg-slate-100 px-1.5 py-0.5 rounded border border-slate-200">
                                        {{ $apt->appointment_number }}
                                    </span>
                            </td>

                            {{-- Patient Info --}}
                            <td class="py-3.5 px-4">
                                <div class="font-semibold text-slate-900">{{ $apt->patient->full_name }}</div>
                                <div class="text-[11px] text-slate-500 font-mono">{{ $apt->patient->phone }} &bull; {{ $apt->patient->email }}</div>
                            </td>

                            {{-- Notes --}}
                            <td class="py-3.5 px-4 max-w-xs">
                                @if ($apt->patient_notes)
                                    <p class="truncate text-slate-600 text-xs" title="{{ $apt->patient_notes }}">{{ $apt->patient_notes }}</p>
                                @else
                                    <span class="text-slate-300 italic text-[11px]">No intake notes provided</span>
                                @endif
                            </td>

                            {{-- Status Badge --}}
                            <td class="py-3.5 px-4 whitespace-nowrap">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold border {{ $apt->status->badgeColor() }}">
                                        {{ $apt->status->label() }}
                                    </span>
                            </td>

                            {{-- Actions --}}
                            <td class="py-3.5 px-4 whitespace-nowrap text-right space-x-1.5">
                                {{-- Audit Trail button --}}
                                <button
                                    type="button"
                                    wire:click="viewAuditLog({{ $apt->id }})"
                                    class="px-2 py-1 rounded text-[11px] font-medium text-slate-600 hover:bg-slate-100 border border-slate-200 transition"
                                    title="View Immutable Audit Trail">
                                    Audit Log ({{ $apt->auditLogs->count() }})
                                </button>

                                @if ($apt->status === \App\Enums\AppointmentStatus::Confirmed)
                                    {{-- Mark Completed --}}
                                    <button
                                        type="button"
                                        wire:click="markAsCompleted({{ $apt->id }})"
                                        class="px-2 py-1 rounded text-[11px] font-bold text-emerald-700 bg-emerald-50 hover:bg-emerald-100 border border-emerald-200 transition">
                                        Complete
                                    </button>

                                    {{-- Mark No-Show --}}
                                    <button
                                        type="button"
                                        wire:click="markAsNoShow({{ $apt->id }})"
                                        class="px-2 py-1 rounded text-[11px] font-medium text-slate-600 hover:bg-slate-100 border border-slate-200 transition">
                                        No-Show
                                    </button>

                                    {{-- Cancel --}}
                                    <button
                                        type="button"
                                        wire:click="openCancelModal({{ $apt->id }})"
                                        class="px-2 py-1 rounded text-[11px] font-medium text-rose-600 hover:bg-rose-50 border border-rose-200 transition">
                                        Cancel
                                    </button>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>

    {{-- AUDIT TRAIL MODAL --}}
    @if ($showAuditModal && $activeAppointment)
        <div class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4">
            <div class="bg-white rounded-xl border border-slate-200 shadow-elevated max-w-lg w-full p-6 space-y-4">
                <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                    <div>
                        <h4 class="text-sm font-bold text-slate-900">Immutable Audit Trail</h4>
                        <p class="text-[11px] text-slate-400 font-mono">{{ $activeAppointment->appointment_number }} &bull; {{ $activeAppointment->patient->full_name }}</p>
                    </div>
                    <button wire:click="closeModal" class="text-slate-400 hover:text-slate-700 text-lg font-bold">&times;</button>
                </div>

                {{-- Audit Timeline --}}
                <div class="space-y-3 max-h-80 overflow-y-auto pr-1">
                    @foreach ($activeAppointment->auditLogs as $log)
                        <div class="text-xs bg-slate-50 rounded-lg p-3 border border-slate-100 space-y-1">
                            <div class="flex items-center justify-between">
                                <span class="font-bold text-slate-800 uppercase tracking-wider text-[10px]">{{ $log->action }}</span>
                                <span class="font-mono text-[10px] text-slate-400">{{ $log->created_at->format('M j, Y h:i:s A') }}</span>
                            </div>
                            @if ($log->from_status || $log->to_status)
                                <div class="text-[11px] font-mono text-slate-600">
                                    Transition: <span class="line-through text-slate-400">{{ $log->from_status ?? 'none' }}</span> &rarr; <span class="font-bold text-slate-900">{{ $log->to_status }}</span>
                                </div>
                            @endif
                            @if ($log->notes)
                                <p class="text-slate-600 text-[11px] mt-0.5">{{ $log->notes }}</p>
                            @endif
                        </div>
                    @endforeach
                </div>

                <div class="pt-3 border-t border-slate-100 flex justify-end">
                    <button wire:click="closeModal" class="px-3.5 py-1.5 rounded-lg text-xs font-semibold bg-slate-900 text-white hover:bg-slate-800">Close</button>
                </div>
            </div>
        </div>
    @endif

    {{-- CANCELLATION MODAL --}}
    @if ($showCancelModal && $activeAppointment)
        <div class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4">
            <div class="bg-white rounded-xl border border-slate-200 shadow-elevated max-w-md w-full p-6 space-y-4">
                <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                    <div>
                        <h4 class="text-sm font-bold text-rose-900">Cancel Appointment</h4>
                        <p class="text-[11px] text-slate-500">{{ $activeAppointment->appointment_number }} ({{ $activeAppointment->patient->full_name }})</p>
                    </div>
                    <button wire:click="closeModal" class="text-slate-400 hover:text-slate-700 text-lg font-bold">&times;</button>
                </div>

                <form wire:submit.prevent="confirmCancellation" class="space-y-3">
                    <div>
                        <label class="block text-[10px] font-bold uppercase tracking-wider text-slate-500 mb-1">Cancellation Reason *</label>
                        <textarea wire:model="cancellationReason" rows="3" placeholder="e.g. Patient requested reschedule, doctor emergency..." class="w-full text-xs rounded-lg border-slate-200 focus:border-slate-900 focus:ring-slate-900"></textarea>
                        @error('cancellationReason') <span class="text-[11px] text-rose-600 block mt-1">{{ $message }}</span> @enderror
                    </div>

                    <div class="pt-2 flex items-center justify-end gap-2 text-xs">
                        <button type="button" wire:click="closeModal" class="px-3 py-1.5 rounded-lg border border-slate-200 text-slate-600 hover:bg-slate-50">Keep Appointment</button>
                        <button type="submit" class="px-3 py-1.5 rounded-lg font-semibold bg-rose-600 text-white hover:bg-rose-700">Confirm Cancellation</button>
                    </div>
                </form>
            </div>
        </div>
    @endif
</div>
