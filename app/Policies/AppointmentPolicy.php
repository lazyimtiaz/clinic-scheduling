<?php

namespace App\Policies;

use App\Models\Appointment;
use App\Models\User;

class AppointmentPolicy
{
    /**
     * Determine whether the user can view any appointments.
     * Staff (Admins, Receptionists, Doctors) can view clinic ledgers.
     */
    public function viewAny(User $user): bool
    {
        return $user->isStaff();
    }

    /**
     * Determine whether the user can view a specific appointment.
     */
    public function view(User $user, Appointment $appointment): bool
    {
        if ($user->isAdmin() || $user->isReceptionist()) {
            return true;
        }

        // Doctors can only view their own appointments
        if ($user->isDoctor()) {
            return $appointment->doctor->user_id === $user->id;
        }

        // Patients can only view their own appointments
        return $appointment->patient->email === $user->email;
    }

    /**
     * Determine whether the user can cancel an appointment.
     */
    public function cancel(User $user, Appointment $appointment): bool
    {
        // Cannot cancel already final appointments
        if ($appointment->status->isFinal()) {
            return false;
        }

        // Staff can always cancel with reason
        if ($user->isStaff()) {
            return true;
        }

        // Patients can only cancel their own appointment
        return $appointment->patient->email === $user->email;
    }

    /**
     * Determine whether the user can transition an appointment to Completed or No-Show.
     * Only medical staff (doctor or receptionist) can mark completion.
     */
    public function complete(User $user, Appointment $appointment): bool
    {
        return $user->isStaff() && ! $appointment->status->isFinal();
    }
}
