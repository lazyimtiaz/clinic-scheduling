<?php

namespace App\Livewire;

use App\Models\Appointment;
use App\Services\AppointmentService;
use Livewire\Component;

class PatientPortal extends Component
{
    public ?string $feedbackMessage = null;

    public function cancelMyAppointment(int $appointmentId, AppointmentService $service): void
    {
        $user = auth()->user();

        // Scope strictly to current patient's email
        $appointment = Appointment::where('id', $appointmentId)
            ->whereHas('patient', function ($query) use ($user) {
                $query->where('email', $user->email);
            })
            ->firstOrFail();

        $service->cancel($appointment, 'Cancelled by patient via portal', $user->id);

        $this->feedbackMessage = "Appointment {$appointment->appointment_number} cancelled.";
    }

    public function render()
    {
        $user = auth()->user();

        $appointments = Appointment::with(['doctor', 'clinic'])
            ->whereHas('patient', function ($query) use ($user) {
                $query->where('email', $user->email);
            })
            ->orderBy('start_time', 'desc')
            ->get();

        return view('livewire.patient-portal', [
            'appointments' => $appointments,
        ]);
    }
}
