<?php

namespace App\Livewire;

use App\Enums\AppointmentStatus;
use App\Models\Appointment;
use App\Models\AppointmentAuditLog;
use App\Models\Doctor;
use App\Services\AppointmentService;
use Carbon\Carbon;
use Livewire\Component;

class DoctorScheduleConsole extends Component
{
    // Filter State
    public ?int $selectedDoctorId = null;

    public string $selectedDate = '';

    public string $statusFilter = 'all'; // all, confirmed, completed, cancelled

    // Action Modal State
    public ?int $activeAppointmentId = null;

    public ?Appointment $activeAppointment = null;

    public string $cancellationReason = '';

    public bool $showAuditModal = false;

    public bool $showCancelModal = false;

    // Toast Feedback
    public ?string $feedbackMessage = null;

    public function mount(): void
    {
        $user = auth()->user();

        if ($user && $user->isDoctor()) {
            // Find doctor record linked to this user
            $doctor = Doctor::where('user_id', $user->id)->first();
            $this->selectedDoctorId = $doctor?->id;
        } else {
            $firstDoctor = Doctor::where('is_active', true)->first();
            $this->selectedDoctorId = $firstDoctor?->id;
        }

        $this->selectedDate = Carbon::today()->toDateString();
    }
    public function selectDate(string $date): void
    {
        $this->selectedDate = $date;
    }

    public function setToday(): void
    {
        $this->selectedDate = Carbon::today()->toDateString();
    }

    public function setTomorrow(): void
    {
        $this->selectedDate = Carbon::tomorrow()->toDateString();
    }

    /**
     * Mark an appointment as Completed
     */
    public function markAsCompleted(int $appointmentId): void
    {
        $appointment = Appointment::findOrFail($appointmentId);

        if ($appointment->status->isFinal()) {
            return;
        }

        $fromStatus = $appointment->status->value;
        $appointment->update(['status' => AppointmentStatus::Completed]);

        // Audit Trail Record
        AppointmentAuditLog::create([
            'appointment_id' => $appointment->id,
            'user_id' => auth()->id(),
            'action' => 'status_changed',
            'from_status' => $fromStatus,
            'to_status' => AppointmentStatus::Completed->value,
            'notes' => 'Marked as completed by attending clinician.',
        ]);

        $this->feedbackMessage = "Appointment {$appointment->appointment_number} marked as Completed.";
    }

    /**
     * Mark an appointment as No-Show
     */
    public function markAsNoShow(int $appointmentId): void
    {
        $appointment = Appointment::findOrFail($appointmentId);

        if ($appointment->status->isFinal()) {
            return;
        }

        $fromStatus = $appointment->status->value;
        $appointment->update(['status' => AppointmentStatus::NoShow]);

        AppointmentAuditLog::create([
            'appointment_id' => $appointment->id,
            'user_id' => auth()->id(),
            'action' => 'status_changed',
            'from_status' => $fromStatus,
            'to_status' => AppointmentStatus::NoShow->value,
            'notes' => 'Patient did not arrive for scheduled slot.',
        ]);

        $this->feedbackMessage = "Appointment {$appointment->appointment_number} marked as No-Show.";
    }

    /**
     * Open Cancel Modal
     */
    public function openCancelModal(int $appointmentId): void
    {
        $this->activeAppointmentId = $appointmentId;
        $this->activeAppointment = Appointment::findOrFail($appointmentId);
        $this->cancellationReason = '';
        $this->showCancelModal = true;
    }

    public function confirmCancellation(AppointmentService $service): void
    {
        $this->validate([
            'cancellationReason' => 'required|string|min:3|max:255',
        ]);

        if (! $this->activeAppointment) {
            return;
        }

        $service->cancel(
            appointment: $this->activeAppointment,
            reason: trim($this->cancellationReason),
            cancelledByUserId: auth()->id()
        );

        $this->showCancelModal = false;
        $this->feedbackMessage = "Appointment {$this->activeAppointment->appointment_number} cancelled and archived.";
        $this->activeAppointment = null;
        $this->activeAppointmentId = null;
    }

    /**
     * View Audit Logs for an Appointment
     */
    public function viewAuditLog(int $appointmentId): void
    {
        $this->activeAppointmentId = $appointmentId;
        $this->activeAppointment = Appointment::with(['auditLogs.user', 'patient', 'doctor'])->findOrFail($appointmentId);
        $this->showAuditModal = true;
    }

    public function closeModal(): void
    {
        $this->showAuditModal = false;
        $this->showCancelModal = false;
        $this->activeAppointment = null;
        $this->activeAppointmentId = null;
    }

    public function render()
    {
        $doctors = Doctor::with('clinic')->where('is_active', true)->get();
        $selectedDoctor = $this->selectedDoctorId ? Doctor::find($this->selectedDoctorId) : null;

        // Query appointments for the day with eager loading
        $query = Appointment::with(['patient', 'doctor', 'clinic', 'auditLogs'])
            ->where('doctor_id', $this->selectedDoctorId)
            ->whereDate('start_time', $this->selectedDate)
            ->orderBy('start_time', 'asc');

        if ($this->statusFilter !== 'all') {
            $query->where('status', $this->statusFilter);
        }

        $appointments = $query->get();

        // Daily summary stats
        $stats = [
            'total' => $appointments->count(),
            'confirmed' => $appointments->where('status', AppointmentStatus::Confirmed)->count(),
            'completed' => $appointments->where('status', AppointmentStatus::Completed)->count(),
            'cancelled' => $appointments->where('status', AppointmentStatus::Cancelled)->count(),
        ];

        return view('livewire.doctor-schedule-console', [
            'doctors' => $doctors,
            'selectedDoctor' => $selectedDoctor,
            'appointments' => $appointments,
            'stats' => $stats,
        ]);
    }
}
