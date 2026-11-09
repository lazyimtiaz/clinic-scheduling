<?php

namespace App\Livewire;

use App\Exceptions\AppointmentConflictException;
use App\Models\Appointment;
use App\Models\Doctor;
use App\Models\Patient;
use App\Services\AppointmentService;
use Carbon\Carbon;
use Livewire\Attributes\Validate;
use Livewire\Component;

class BookAppointment extends Component
{
    // Wizard step: 1 = Schedule & Slot, 2 = Intake details, 3 = Confirmed
    public int $step = 1;

    // Doctor & Calendar State
    public ?int $selectedDoctorId = null;

    public string $selectedDate = '';

    public string $weekStartDate = ''; // Track start of current 7-day strip view

    public ?string $selectedSlotStart = null;

    public ?string $selectedSlotEnd = null;

    // Patient Intake Form
    #[Validate('required|string|min:2|max:50')]
    public string $firstName = '';

    #[Validate('required|string|min:2|max:50')]
    public string $lastName = '';

    #[Validate('required|email|max:100')]
    public string $email = '';

    #[Validate('required|string|min:7|max:20')]
    public string $phone = '';

    #[Validate('nullable|string|max:500')]
    public string $patientNotes = '';

    // Feedback
    public ?string $errorMessage = null;

    public ?Appointment $confirmedAppointment = null;

    public function mount(): void
    {
        $firstDoctor = Doctor::with('clinic')->where('is_active', true)->first();
        if ($firstDoctor) {
            $this->selectedDoctorId = $firstDoctor->id;
        }

        $today = Carbon::today();
        $this->selectedDate = $today->toDateString();
        $this->weekStartDate = $today->toDateString();
    }

    /**
     * Build the 7-day strip for calendar navigation
     */
    public function getWeekDaysProperty(): array
    {
        $start = Carbon::parse($this->weekStartDate);
        $days = [];

        for ($i = 0; $i < 7; $i++) {
            $day = (clone $start)->addDays($i);
            $days[] = [
                'date' => $day->toDateString(),
                'dayName' => $day->format('D'),
                'dayNumber' => $day->format('d'),
                'month' => $day->format('M'),
                'isToday' => $day->isToday(),
                'isPast' => $day->isPast() && ! $day->isToday(),
            ];
        }

        return $days;
    }

    public function nextWeek(): void
    {
        $this->weekStartDate = Carbon::parse($this->weekStartDate)->addDays(7)->toDateString();
    }

    public function prevWeek(): void
    {
        $prev = Carbon::parse($this->weekStartDate)->subDays(7);
        // Do not allow going before today's week
        if ($prev->endOfDay()->gte(Carbon::today()->startOfDay())) {
            $this->weekStartDate = $prev->toDateString();
        } else {
            $this->weekStartDate = Carbon::today()->toDateString();
        }
    }

    public function selectDate(string $date): void
    {
        if (Carbon::parse($date)->lt(Carbon::today())) {
            return;
        }

        $this->selectedDate = $date;
        $this->selectedSlotStart = null;
        $this->selectedSlotEnd = null;
        $this->errorMessage = null;
    }

    /**
     * Compute available time slots grouped into Morning & Afternoon
     */
    public function getTimeSlotsProperty(AppointmentService $service): array
    {
        if (! $this->selectedDoctorId || ! $this->selectedDate) {
            return ['morning' => collect(), 'afternoon' => collect()];
        }

        $doctor = Doctor::find($this->selectedDoctorId);
        if (! $doctor) {
            return ['morning' => collect(), 'afternoon' => collect()];
        }

        $rawSlots = $service->getAvailableSlots($doctor, Carbon::parse($this->selectedDate));

        $morning = $rawSlots->filter(function ($slot) {
            return Carbon::parse($slot['start_time'])->hour < 12;
        });

        $afternoon = $rawSlots->filter(function ($slot) {
            return Carbon::parse($slot['start_time'])->hour >= 12;
        });

        return [
            'morning' => $morning,
            'afternoon' => $afternoon,
            'total' => $rawSlots->where('is_available', true)->count(),
        ];
    }

    public function selectSlot(string $start, string $end): void
    {
        $this->selectedSlotStart = $start;
        $this->selectedSlotEnd = $end;
        $this->errorMessage = null;
    }

    public function goToStepTwo(): void
    {
        if (! $this->selectedSlotStart || ! $this->selectedSlotEnd) {
            $this->errorMessage = 'Please choose an available appointment time slot to continue.';

            return;
        }

        $this->errorMessage = null;
        $this->step = 2;
    }

    public function goToStepOne(): void
    {
        $this->step = 1;
        $this->errorMessage = null;
    }

    public function confirmBooking(AppointmentService $service): void
    {
        $this->validate();

        $doctor = Doctor::with('clinic')->findOrFail($this->selectedDoctorId);

        $patient = Patient::firstOrCreate(
            ['email' => strtolower(trim($this->email))],
            [
                'first_name' => trim($this->firstName),
                'last_name' => trim($this->lastName),
                'phone' => trim($this->phone),
            ]
        );

        try {
            $this->confirmedAppointment = $service->book(
                doctor: $doctor,
                patient: $patient,
                startTime: Carbon::parse($this->selectedSlotStart),
                endTime: Carbon::parse($this->selectedSlotEnd),
                patientNotes: $this->patientNotes ?: null
            );

            $this->step = 3;
            $this->errorMessage = null;
        } catch (AppointmentConflictException $e) {
            $this->errorMessage = $e->getMessage();
            $this->step = 1;
            $this->selectedSlotStart = null;
            $this->selectedSlotEnd = null;
        } catch (\Throwable $e) {
            $this->errorMessage = 'An unexpected error occurred while booking. Please try again.';
        }
    }

    public function resetBooking(): void
    {
        $this->reset([
            'step',
            'selectedSlotStart',
            'selectedSlotEnd',
            'firstName',
            'lastName',
            'email',
            'phone',
            'patientNotes',
            'errorMessage',
            'confirmedAppointment',
        ]);

        $today = Carbon::today();
        $this->selectedDate = $today->toDateString();
        $this->weekStartDate = $today->toDateString();
    }

    public function render()
    {
        $doctors = Doctor::with('clinic')->where('is_active', true)->get();
        $selectedDoctor = $this->selectedDoctorId ? Doctor::find($this->selectedDoctorId) : null;

        return view('livewire.book-appointment', [
            'doctors' => $doctors,
            'selectedDoctor' => $selectedDoctor,
            'timeSlots' => $this->timeSlots,
            'weekDays' => $this->weekDays,
        ]);
    }
}
