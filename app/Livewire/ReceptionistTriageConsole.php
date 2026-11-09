<?php

namespace App\Livewire;

use App\Enums\AppointmentStatus;
use App\Models\Appointment;
use App\Models\AppointmentAuditLog;
use App\Models\Clinic;
use App\Models\Doctor;
use Carbon\Carbon;
use Livewire\Component;

class ReceptionistTriageConsole extends Component
{
    public string $searchQuery = '';

    public string $filterDate = '';

    public ?int $selectedClinicId = null;

    // Toast
    public ?string $feedbackMessage = null;

    public function mount(): void
    {
        $this->filterDate = Carbon::today()->toDateString();
        $firstClinic = Clinic::first();
        if ($firstClinic) {
            $this->selectedClinicId = $firstClinic->id;
        }
    }

    /**
     * Transition patient to Arrived (Waiting room)
     */
    public function markAsArrived(int $appointmentId): void
    {
        $appointment = Appointment::findOrFail($appointmentId);
        $fromStatus = $appointment->status->value;

        $appointment->update(['status' => AppointmentStatus::Arrived]);

        AppointmentAuditLog::create([
            'appointment_id' => $appointment->id,
            'user_id' => auth()->id(),
            'action' => 'patient_check_in',
            'from_status' => $fromStatus,
            'to_status' => AppointmentStatus::Arrived->value,
            'notes' => 'Patient arrived at clinic front desk.',
        ]);

        $this->feedbackMessage = "Patient {$appointment->patient->full_name} checked in to waiting room.";
    }

    /**
     * Transition patient to In Consultation (called by doctor/nurse)
     */
    public function markInConsultation(int $appointmentId): void
    {
        $appointment = Appointment::findOrFail($appointmentId);
        $fromStatus = $appointment->status->value;

        $appointment->update(['status' => AppointmentStatus::InConsultation]);

        AppointmentAuditLog::create([
            'appointment_id' => $appointment->id,
            'user_id' => auth()->id(),
            'action' => 'consultation_started',
            'from_status' => $fromStatus,
            'to_status' => AppointmentStatus::InConsultation->value,
            'notes' => 'Patient escorted to examination room.',
        ]);

        $this->feedbackMessage = "Patient {$appointment->patient->full_name} is now in consultation.";
    }

    public function render()
    {
        $query = Appointment::with(['patient', 'doctor', 'clinic'])
            ->whereDate('start_time', $this->filterDate)
            ->orderBy('start_time', 'asc');

        if ($this->searchQuery) {
            $q = '%'.trim($this->searchQuery).'%';
            $query->where(function ($sub) use ($q) {
                $sub->where('appointment_number', 'like', $q)
                    ->orWhereHas('patient', function ($p) use ($q) {
                        $p->where('first_name', 'like', $q)
                            ->orWhere('last_name', 'like', $q)
                            ->orWhere('phone', 'like', $q);
                    });
            });
        }

        $allToday = $query->get();

        // Group into Triage Queues
        $waitingRoom = $allToday->where('status', AppointmentStatus::Arrived);
        $inConsultation = $allToday->where('status', AppointmentStatus::InConsultation);
        $expected = $allToday->where('status', AppointmentStatus::Confirmed);
        $completed = $allToday->where('status', AppointmentStatus::Completed);

        return view('livewire.receptionist-triage-console', [
            'waitingRoom' => $waitingRoom,
            'inConsultation' => $inConsultation,
            'expected' => $expected,
            'completed' => $completed,
            'totalCount' => $allToday->count(),
        ]);
    }
}
