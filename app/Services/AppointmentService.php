<?php

namespace App\Services;

use App\Enums\AppointmentStatus;
use App\Exceptions\AppointmentConflictException;
use App\Models\Appointment;
use App\Models\AppointmentAuditLog;
use App\Models\Doctor;
use App\Models\DoctorLeave;
use App\Models\DoctorSchedule;
use App\Models\Patient;
use App\Notifications\AppointmentBookedNotification;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class AppointmentService
{
    /**
     * Get all available time slots for a doctor on a specific date.
     */
    public function getAvailableSlots(Doctor $doctor, Carbon $date): Collection
    {
        $isOnLeave = DoctorLeave::query()
            ->where('doctor_id', $doctor->id)
            ->whereDate('leave_date', $date->toDateString())
            ->exists();

        if ($isOnLeave) {
            return collect();
        }

        $schedule = DoctorSchedule::query()
            ->where('doctor_id', $doctor->id)
            ->where('day_of_week', $date->dayOfWeek)
            ->where('is_available', true)
            ->first();

        if (! $schedule) {
            return collect();
        }

        $shiftStart = Carbon::parse("{$date->toDateString()} {$schedule->start_time}");
        $shiftEnd = Carbon::parse("{$date->toDateString()} {$schedule->end_time}");
        $slotMinutes = $doctor->slot_duration_minutes;

        $breakStart = $schedule->break_start
            ? Carbon::parse("{$date->toDateString()} {$schedule->break_start}")
            : null;
        $breakEnd = $schedule->break_end
            ? Carbon::parse("{$date->toDateString()} {$schedule->break_end}")
            : null;

        $bookedAppointments = Appointment::query()
            ->active()
            ->where('doctor_id', $doctor->id)
            ->whereDate('start_time', $date->toDateString())
            ->get(['start_time', 'end_time']);

        $period = CarbonPeriod::since($shiftStart)
            ->minutes($slotMinutes)
            ->until($shiftEnd);

        $slots = collect();

        foreach ($period as $slotStart) {
            $slotEnd = (clone $slotStart)->addMinutes($slotMinutes);

            if ($slotEnd->gt($shiftEnd)) {
                break;
            }

            if ($slotStart->isPast() && $date->isToday()) {
                continue;
            }

            $isInBreak = false;
            if ($breakStart && $breakEnd) {
                $isInBreak = $slotStart->lt($breakEnd) && $slotEnd->gt($breakStart);
            }

            if ($isInBreak) {
                continue;
            }

            $isBooked = $bookedAppointments->contains(function ($appointment) use ($slotStart, $slotEnd) {
                return $slotStart->lt($appointment->end_time) && $slotEnd->gt($appointment->start_time);
            });

            $slots->push([
                'start_time' => $slotStart->toDateTimeString(),
                'end_time' => $slotEnd->toDateTimeString(),
                'display' => $slotStart->format('h:i A').' - '.$slotEnd->format('h:i A'),
                'is_available' => ! $isBooked,
                'reason' => $isBooked ? 'Already booked' : null,
            ]);
        }

        return $slots;
    }

    /**
     * Atomically book an appointment with pessimistic locking to prevent race conditions.
     *
     * @throws AppointmentConflictException
     */
    public function book(
        Doctor $doctor,
        Patient $patient,
        Carbon $startTime,
        Carbon $endTime,
        ?string $patientNotes = null,
        ?int $bookedByUserId = null
    ): Appointment {
        return DB::transaction(function () use ($doctor, $patient, $startTime, $endTime, $patientNotes, $bookedByUserId) {
            // 1. Lock the doctor's record for this transaction to serialize concurrent bookings
            // This prevents another process from checking availability at the exact same millisecond
            Doctor::query()->where('id', $doctor->id)->lockForUpdate()->first();

            // 2. Strict overlap check:
            // A collision occurs if (existing_start < new_end) AND (existing_end > new_start)
            $hasOverlap = Appointment::query()
                ->active()
                ->where('doctor_id', $doctor->id)
                ->where('start_time', '<', $endTime)
                ->where('end_time', '>', $startTime)
                ->exists();

            if ($hasOverlap) {
                throw new AppointmentConflictException(
                    'This time slot is no longer available. Please select another slot.'
                );
            }

            // 3. Create the appointment
            $appointment = Appointment::create([
                'appointment_number' => $this->generateReferenceNumber(),
                'doctor_id' => $doctor->id,
                'patient_id' => $patient->id,
                'clinic_id' => $doctor->clinic_id,
                'start_time' => $startTime,
                'end_time' => $endTime,
                'status' => AppointmentStatus::Confirmed,
                'patient_notes' => $patientNotes,
            ]);

            // 4. Create an immutable audit log entry
            AppointmentAuditLog::create([
                'appointment_id' => $appointment->id,
                'user_id' => $bookedByUserId,
                'action' => 'created',
                'from_status' => null,
                'to_status' => AppointmentStatus::Confirmed->value,
                'notes' => 'Appointment booked successfully.',
            ]);
            // 5. Asynchronously queue confirmation notification AFTER transaction commits
            DB::afterCommit(function () use ($appointment, $patient) {
                $patient->notify(new AppointmentBookedNotification($appointment));
            });

            return $appointment;
        });
    }

    /**
     * Cancel an appointment with audit log tracking.
     */
    public function cancel(
        Appointment $appointment,
        string $reason,
        ?int $cancelledByUserId = null
    ): Appointment {
        return DB::transaction(function () use ($appointment, $reason, $cancelledByUserId) {
            $fromStatus = $appointment->status->value;

            $appointment->update([
                'status' => AppointmentStatus::Cancelled,
                'cancellation_reason' => $reason,
            ]);

            AppointmentAuditLog::create([
                'appointment_id' => $appointment->id,
                'user_id' => $cancelledByUserId,
                'action' => 'cancelled',
                'from_status' => $fromStatus,
                'to_status' => AppointmentStatus::Cancelled->value,
                'notes' => "Reason: {$reason}",
            ]);

            return $appointment;
        });
    }

    /**
     * Generate a unique appointment reference number.
     * e.g., APT-20261109-A4B7
     */
    protected function generateReferenceNumber(): string
    {
        do {
            $code = 'APT-'.now()->format('Ymd').'-'.strtoupper(Str::random(4));
        } while (Appointment::where('appointment_number', $code)->exists());

        return $code;
    }

    /**
     * Concurrency-safe reschedule operation
     *
     * @throws AppointmentConflictException
     */
    public function reschedule(
        Appointment $appointment,
        Carbon $newStartTime,
        Carbon $newEndTime,
        ?int $rescheduledByUserId = null
    ): Appointment {
        return DB::transaction(function () use ($appointment, $newStartTime, $newEndTime, $rescheduledByUserId) {
            // Lock doctor record
            Doctor::query()->where('id', $appointment->doctor_id)->lockForUpdate()->first();

            // Check if another active appointment occupies the target window (excluding this appointment)
            $hasOverlap = Appointment::query()
                ->active()
                ->where('doctor_id', $appointment->doctor_id)
                ->where('id', '!=', $appointment->id)
                ->where('start_time', '<', $newEndTime)
                ->where('end_time', '>', $newStartTime)
                ->exists();

            if ($hasOverlap) {
                throw new AppointmentConflictException(
                    'The selected reschedule slot is no longer available. Please select another slot.'
                );
            }

            $oldTime = $appointment->start_time->format('Y-m-d H:i');

            $appointment->update([
                'start_time' => $newStartTime,
                'end_time' => $newEndTime,
                'status' => AppointmentStatus::Confirmed,
            ]);

            AppointmentAuditLog::create([
                'appointment_id' => $appointment->id,
                'user_id' => $rescheduledByUserId,
                'action' => 'rescheduled',
                'from_status' => $appointment->status->value,
                'to_status' => AppointmentStatus::Confirmed->value,
                'notes' => "Rescheduled from {$oldTime} to {$newStartTime->format('Y-m-d H:i')}",
            ]);

            return $appointment;
        });
    }
}
