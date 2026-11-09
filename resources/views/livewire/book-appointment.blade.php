<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
    {{-- Notice / Conflict Alert --}}
    @if ($errorMessage)
        <div class="mb-6 rounded-lg bg-rose-50 border border-rose-200 p-4 text-xs text-rose-800 flex items-start justify-between shadow-subtle">
            <div class="flex items-center gap-2.5">
                <svg class="w-4 h-4 text-rose-600 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                </svg>
                <span class="font-medium">{{ $errorMessage }}</span>
            </div>
            <button wire:click="$set('errorMessage', null)" class="text-rose-400 hover:text-rose-700 font-bold ml-4">&times;</button>
        </div>
    @endif
    @if ($doctors->isEmpty())
        <div class="bg-white rounded-xl border border-slate-200 shadow-card p-12 text-center max-w-lg mx-auto my-12">
            <div class="w-12 h-12 bg-slate-100 rounded-full flex items-center justify-center mx-auto text-slate-400 mb-3">
                <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                </svg>
            </div>
            <h3 class="text-sm font-bold text-slate-900">No Active Practitioners</h3>
            <p class="text-xs text-slate-500 mt-1">Please seed practitioner schedules to initialize the booking engine.</p>
        </div>

    @elseif ($step === 3 && $confirmedAppointment)
        {{-- STEP 3: CLINICAL RECORD RECEIPT --}}
        <div class="max-w-xl mx-auto">
            <div class="bg-white rounded-xl border border-slate-200 shadow-card overflow-hidden">
                <div class="p-6 text-center border-b border-slate-100 bg-slate-50/50">
                    <div class="w-12 h-12 bg-clinical-100 text-clinical-800 rounded-full flex items-center justify-center mx-auto mb-3">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" />
                        </svg>
                    </div>
                    <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200">
                        Registration Locked & Confirmed
                    </span>
                    <h2 class="text-xl font-bold text-slate-900 tracking-tight mt-2">Consultation Scheduled</h2>
                    <p class="text-xs text-slate-500 mt-1">An immutable booking record has been written to the clinical ledger.</p>
                </div>

                <div class="p-6 space-y-4">
                    <div class="bg-slate-50 rounded-lg p-4 border border-slate-200 text-xs divide-y divide-slate-200/70">
                        <div class="flex justify-between items-center pb-2.5">
                            <span class="text-slate-500 font-medium">Record ID</span>
                            <span class="font-mono font-semibold text-slate-900">{{ $confirmedAppointment->appointment_number }}</span>
                        </div>
                        <div class="flex justify-between items-center py-2.5">
                            <span class="text-slate-500 font-medium">Physician</span>
                            <span class="font-semibold text-slate-900">{{ $confirmedAppointment->doctor->name }}</span>
                        </div>
                        <div class="flex justify-between items-center py-2.5">
                            <span class="text-slate-500 font-medium">Specialty</span>
                            <span class="text-slate-700">{{ $confirmedAppointment->doctor->specialization }}</span>
                        </div>
                        <div class="flex justify-between items-center py-2.5">
                            <span class="text-slate-500 font-medium">Time</span>
                            <span class="font-semibold text-slate-900">{{ $confirmedAppointment->start_time->format('l, M j, Y @ h:i A') }}</span>
                        </div>
                        <div class="flex justify-between items-center py-2.5">
                            <span class="text-slate-500 font-medium">Facility</span>
                            <span class="text-slate-700">{{ $confirmedAppointment->clinic->name }}</span>
                        </div>
                        <div class="flex justify-between items-center pt-2.5">
                            <span class="text-slate-500 font-medium">Consultation Fee</span>
                            <span class="font-mono font-bold text-slate-900 text-sm">${{ number_format($confirmedAppointment->doctor->consultation_fee, 2) }}</span>
                        </div>
                    </div>

                    <button wire:click="resetBooking" class="w-full py-2.5 px-4 rounded-lg text-xs font-semibold bg-slate-900 hover:bg-slate-800 text-white transition shadow-subtle">
                        Book Another Session
                    </button>
                </div>
            </div>
        </div>
    @else
        {{-- WORKSPACE: MASTER / DETAIL LAYOUT --}}
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">

            {{-- COLUMN 1: PRACTITIONER CONTEXT (4 COLS) --}}
            <div class="lg:col-span-4 space-y-4">
                <div class="bg-white rounded-xl border border-slate-200 shadow-card p-5">
                    <div class="flex items-center justify-between text-xs text-slate-400 mb-4">
                        <span class="font-mono">PRACTITIONER</span>
                        <span class="inline-flex items-center gap-1 text-emerald-700 font-medium text-[11px]">
                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span> Active
                        </span>
                    </div>

                    <div class="flex items-center gap-3.5">
                        <div class="w-12 h-12 rounded-lg bg-clinical-900 text-white font-bold text-sm flex items-center justify-center shrink-0">
                            {{ substr($selectedDoctor->name ?? 'MD', 0, 2) }}
                        </div>
                        <div>
                            <h2 class="text-sm font-bold text-slate-900 tracking-tight">{{ $selectedDoctor->name }}</h2>
                            <p class="text-xs font-medium text-clinical-700">{{ $selectedDoctor->specialization }}</p>
                        </div>
                    </div>

                    <p class="text-xs text-slate-600 mt-3 leading-relaxed border-t border-slate-100 pt-3">
                        {{ $selectedDoctor->bio }}
                    </p>

                    <div class="mt-4 pt-3 border-t border-slate-100 grid grid-cols-2 gap-2 text-xs">
                        <div class="bg-slate-50 p-2.5 rounded-lg border border-slate-100">
                            <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block">Standard Fee</span>
                            <span class="font-mono font-bold text-slate-900 mt-0.5 block">${{ number_format($selectedDoctor->consultation_fee, 2) }}</span>
                        </div>
                        <div class="bg-slate-50 p-2.5 rounded-lg border border-slate-100">
                            <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block">Slot Window</span>
                            <span class="font-mono font-bold text-slate-900 mt-0.5 block">{{ $selectedDoctor->slot_duration_minutes }} Min</span>
                        </div>
                    </div>

                    @if ($doctors->count() > 1)
                        <div class="mt-4 pt-3 border-t border-slate-100">
                            <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Switch Doctor</label>
                            <select wire:model.live="selectedDoctorId" class="w-full text-xs rounded-lg border-slate-200 bg-slate-50 py-1.5 px-2.5 text-slate-700 font-medium focus:border-slate-900 focus:ring-slate-900">
                                @foreach ($doctors as $doc)
                                    <option value="{{ $doc->id }}">{{ $doc->name }} ({{ $doc->specialization }})</option>
                                @endforeach
                            </select>
                        </div>
                    @endif
                </div>

                {{-- Facility metadata card --}}
                <div class="bg-white rounded-xl border border-slate-200 shadow-card p-4 text-xs space-y-1.5">
                    <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block">Practice Location</span>
                    <p class="font-semibold text-slate-900">{{ $selectedDoctor->clinic->name }}</p>
                    <p class="text-slate-500 leading-normal">{{ $selectedDoctor->clinic->address }}</p>
                    <p class="font-mono text-[10px] text-slate-400 pt-1">TZ: {{ $selectedDoctor->clinic->timezone }}</p>
                </div>
            </div>

            {{-- COLUMN 2: INTERACTIVE SCHEDULING CONSOLE (8 COLS) --}}
            <div class="lg:col-span-8">
                @if ($step === 1)
                    <div class="bg-white rounded-xl border border-slate-200 shadow-card p-6 space-y-6">

                        {{-- Calendar Strip Header --}}
                        <div class="flex items-center justify-between">
                            <div>
                                <h3 class="text-sm font-bold text-slate-900 tracking-tight">Select Date & Time</h3>
                                <p class="text-xs text-slate-500">Pick an open time slot from the live availability ledger</p>
                            </div>
                            <div class="flex items-center gap-1 bg-slate-100 p-0.5 rounded-lg border border-slate-200/80">
                                <button type="button" wire:click="prevWeek" class="p-1 rounded text-slate-600 hover:bg-white transition" title="Previous 7 days">
                                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                                    </svg>
                                </button>
                                <span class="text-[11px] font-mono px-2 text-slate-600">7-Day View</span>
                                <button type="button" wire:click="nextWeek" class="p-1 rounded text-slate-600 hover:bg-white transition" title="Next 7 days">
                                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                    </svg>
                                </button>
                            </div>
                        </div>

                        {{-- Weekday Strip --}}
                        <div class="grid grid-cols-7 gap-1.5">
                            @foreach ($weekDays as $day)
                                @php
                                    $isDaySelected = ($selectedDate === $day['date']);
                                @endphp
                                <button
                                    type="button"
                                    wire:click="selectDate('{{ $day['date'] }}')"
                                    @disabled($day['isPast'])
                                    class="flex flex-col items-center py-2.5 px-1 rounded-lg border text-center transition-all
                                        @if ($day['isPast'])
                                            bg-slate-50 text-slate-300 border-slate-100 cursor-not-allowed
                                        @elseif ($isDaySelected)
                                            bg-slate-900 text-white border-slate-900 shadow-sm
                                        @else
                                            bg-white text-slate-700 border-slate-200 hover:border-slate-300 hover:bg-slate-50/70
                                        @endif
                                    ">
                                    <span class="text-[10px] font-bold uppercase tracking-wider {{ $isDaySelected ? 'text-slate-400' : 'text-slate-400' }}">{{ $day['dayName'] }}</span>
                                    <span class="text-sm font-bold mt-0.5 {{ $isDaySelected ? 'text-white' : 'text-slate-900' }}">{{ $day['dayNumber'] }}</span>
                                    <span class="text-[9px] font-mono mt-0.5 {{ $isDaySelected ? 'text-clinical-200' : 'text-slate-400' }}">{{ $day['month'] }}</span>
                                </button>
                            @endforeach
                        </div>

                        <hr class="border-slate-100" />

                        {{-- Slot Grid Area --}}
                        <div>
                            <div class="flex items-center justify-between mb-3 text-xs">
                                <span class="font-bold text-slate-800">
                                    {{ \Carbon\Carbon::parse($selectedDate)->format('l, F j, Y') }}
                                </span>
                                <span class="font-mono text-slate-400 text-[11px]">
                                    {{ $timeSlots['total'] }} open slots
                                </span>
                            </div>

                            <div wire:loading class="w-full py-10 text-center text-xs text-slate-400">
                                <div class="inline-block animate-spin rounded-full h-4 w-4 border-2 border-slate-200 border-t-slate-800"></div>
                                <span class="ml-2 font-mono">Querying ledger...</span>
                            </div>

                            <div wire:loading.remove class="space-y-5">
                                @if ($timeSlots['total'] === 0)
                                    <div class="text-center py-10 bg-slate-50 rounded-lg border border-dashed border-slate-200 p-4">
                                        <p class="text-xs font-semibold text-slate-600">No Consultations Available</p>
                                        <p class="text-[11px] text-slate-400 mt-0.5">Doctor is off-duty, on leave, or fully booked on this date. Select another day above.</p>
                                    </div>
                                @else
                                    {{-- Morning Group --}}
                                    @if ($timeSlots['morning']->isNotEmpty())
                                        <div>
                                            <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400 block mb-2">Morning Window</span>
                                            <div class="grid grid-cols-2 sm:grid-cols-4 gap-2">
                                                @foreach ($timeSlots['morning'] as $slot)
                                                    @php $isSelected = ($selectedSlotStart === $slot['start_time']); @endphp
                                                    <button
                                                        type="button"
                                                        wire:click="selectSlot('{{ $slot['start_time'] }}', '{{ $slot['end_time'] }}')"
                                                        @disabled(! $slot['is_available'])
                                                        class="h-9 px-3 rounded-md border text-center transition-all font-mono text-xs font-semibold
                                                            @if (! $slot['is_available'])
                                                                bg-slate-50 text-slate-300 border-slate-100 line-through cursor-not-allowed
                                                            @elseif ($isSelected)
                                                                bg-clinical-900 text-white border-clinical-900 shadow-sm
                                                            @else
                                                                bg-white text-slate-700 border-slate-200 hover:border-slate-400 hover:bg-slate-50
                                                            @endif
                                                        ">
                                                        {{ \Carbon\Carbon::parse($slot['start_time'])->format('h:i A') }}
                                                    </button>
                                                @endforeach
                                            </div>
                                        </div>
                                    @endif

                                    {{-- Afternoon Group --}}
                                    @if ($timeSlots['afternoon']->isNotEmpty())
                                        <div>
                                            <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400 block mb-2">Afternoon Window</span>
                                            <div class="grid grid-cols-2 sm:grid-cols-4 gap-2">
                                                @foreach ($timeSlots['afternoon'] as $slot)
                                                    @php $isSelected = ($selectedSlotStart === $slot['start_time']); @endphp
                                                    <button
                                                        type="button"
                                                        wire:click="selectSlot('{{ $slot['start_time'] }}', '{{ $slot['end_time'] }}')"
                                                        @disabled(! $slot['is_available'])
                                                        class="h-9 px-3 rounded-md border text-center transition-all font-mono text-xs font-semibold
                                                            @if (! $slot['is_available'])
                                                                bg-slate-50 text-slate-300 border-slate-100 line-through cursor-not-allowed
                                                            @elseif ($isSelected)
                                                                bg-clinical-900 text-white border-clinical-900 shadow-sm
                                                            @else
                                                                bg-white text-slate-700 border-slate-200 hover:border-slate-400 hover:bg-slate-50
                                                            @endif
                                                        ">
                                                        {{ \Carbon\Carbon::parse($slot['start_time'])->format('h:i A') }}
                                                    </button>
                                                @endforeach
                                            </div>
                                        </div>
                                    @endif
                                @endif
                            </div>
                        </div>

                        {{-- Footer bar --}}
                        <div class="pt-4 border-t border-slate-100 flex items-center justify-between text-xs">
                            <span class="text-slate-500">
                                @if ($selectedSlotStart)
                                    Slot: <strong class="text-slate-900">{{ \Carbon\Carbon::parse($selectedSlotStart)->format('M j @ h:i A') }}</strong>
                                @else
                                    Choose an open slot to proceed
                                @endif
                            </span>

                            <button
                                type="button"
                                wire:click="goToStepTwo"
                                @disabled(! $selectedSlotStart)
                                class="h-9 px-4 rounded-lg font-semibold text-white bg-slate-900 hover:bg-slate-800 disabled:opacity-30 disabled:cursor-not-allowed transition shadow-subtle flex items-center gap-1.5">
                                <span>Continue to Intake</span>
                                <span class="font-mono text-slate-400">&rarr;</span>
                            </button>
                        </div>
                    </div>
                @endif

                @if ($step === 2)
                    {{-- STEP 2: INTAKE SPECIFICATION FORM --}}
                    <div class="bg-white rounded-xl border border-slate-200 shadow-card p-6 space-y-5">
                        <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                            <div>
                                <h3 class="text-sm font-bold text-slate-900 tracking-tight">Patient Intake Form</h3>
                                <p class="text-xs text-slate-500">Provide legal identification and clinical details</p>
                            </div>
                            <div class="font-mono text-[11px] bg-slate-100 text-slate-700 px-2 py-1 rounded border border-slate-200">
                                Locked: {{ \Carbon\Carbon::parse($selectedSlotStart)->format('h:i A') }}
                            </div>
                        </div>

                        <form wire:submit.prevent="confirmBooking" class="space-y-4">
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-[10px] font-bold uppercase tracking-wider text-slate-500 mb-1">Legal First Name *</label>
                                    <input type="text" wire:model="firstName" class="w-full text-xs rounded-lg border-slate-200 bg-white py-2 px-3 text-slate-900 focus:border-slate-900 focus:ring-slate-900">
                                    @error('firstName') <span class="text-[11px] text-rose-600 mt-1 block">{{ $message }}</span> @enderror
                                </div>
                                <div>
                                    <label class="block text-[10px] font-bold uppercase tracking-wider text-slate-500 mb-1">Legal Last Name *</label>
                                    <input type="text" wire:model="lastName" class="w-full text-xs rounded-lg border-slate-200 bg-white py-2 px-3 text-slate-900 focus:border-slate-900 focus:ring-slate-900">
                                    @error('lastName') <span class="text-[11px] text-rose-600 mt-1 block">{{ $message }}</span> @enderror
                                </div>
                            </div>

                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-[10px] font-bold uppercase tracking-wider text-slate-500 mb-1">Email Address *</label>
                                    <input type="email" wire:model="email" class="w-full text-xs rounded-lg border-slate-200 bg-white py-2 px-3 text-slate-900 focus:border-slate-900 focus:ring-slate-900">
                                    @error('email') <span class="text-[11px] text-rose-600 mt-1 block">{{ $message }}</span> @enderror
                                </div>
                                <div>
                                    <label class="block text-[10px] font-bold uppercase tracking-wider text-slate-500 mb-1">Mobile Phone *</label>
                                    <input type="tel" wire:model="phone" placeholder="+1 (555) 000-0000" class="w-full text-xs rounded-lg border-slate-200 bg-white py-2 px-3 text-slate-900 focus:border-slate-900 focus:ring-slate-900">
                                    @error('phone') <span class="text-[11px] text-rose-600 mt-1 block">{{ $message }}</span> @enderror
                                </div>
                            </div>

                            <div>
                                <label class="block text-[10px] font-bold uppercase tracking-wider text-slate-500 mb-1">Clinical Notes / Symptoms (Optional)</label>
                                <textarea wire:model="patientNotes" rows="3" placeholder="Provide brief context for the attending doctor..." class="w-full text-xs rounded-lg border-slate-200 bg-white py-2 px-3 text-slate-900 focus:border-slate-900 focus:ring-slate-900"></textarea>
                                @error('patientNotes') <span class="text-[11px] text-rose-600 mt-1 block">{{ $message }}</span> @enderror
                            </div>

                            <div class="pt-3 border-t border-slate-100 flex items-center justify-between text-xs">
                                <button type="button" wire:click="goToStepOne" class="font-medium text-slate-500 hover:text-slate-900 transition">
                                    &larr; Back to Calendar
                                </button>

                                <button type="submit" class="h-9 px-4 rounded-lg font-semibold text-white bg-slate-900 hover:bg-slate-800 transition shadow-subtle flex items-center gap-2">
                                    <span wire:loading.remove wire:target="confirmBooking">Authorize & Lock Appointment</span>
                                    <span wire:loading wire:target="confirmBooking">Locking via DB Mutex...</span>
                                </button>
                            </div>
                        </form>
                    </div>
                @endif
            </div>
        </div>
    @endif
</div>
