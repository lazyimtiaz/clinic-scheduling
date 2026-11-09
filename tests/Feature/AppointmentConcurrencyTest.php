<?php

namespace Tests\Feature;

use App\Enums\AppointmentStatus;
use App\Exceptions\AppointmentConflictException;
use App\Models\Clinic;
use App\Models\Doctor;
use App\Models\DoctorSchedule;
use App\Models\Patient;
use App\Services\AppointmentService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AppointmentConcurrencyTest extends TestCase
{
    use RefreshDatabase;

    protected AppointmentService $service;

    protected Doctor $doctor;

    protected Clinic $clinic;

    protected Patient $patientA;

    protected Patient $patientB;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = new AppointmentService;

        $this->clinic = Clinic::create([
            'name' => 'Central Hospital',
            'slug' => 'central-hospital',
            'timezone' => 'UTC',
            'is_active' => true,
        ]);

        $this->doctor = Doctor::create([
            'clinic_id' => $this->clinic->id,
            'name' => 'Dr. Gregory House',
            'email' => 'house@example.com',
            'specialization' => 'Diagnostic Medicine',
            'slot_duration_minutes' => 30,
            'consultation_fee' => 200.00,
            'is_active' => true,
        ]);

        DoctorSchedule::create([
            'doctor_id' => $this->doctor->id,
            'day_of_week' => 1,
            'start_time' => '09:00:00',
            'end_time' => '17:00:00',
            'is_available' => true,
        ]);

        $this->patientA = Patient::create([
            'first_name' => 'Alice',
            'last_name' => 'Walker',
            'email' => 'alice@example.com',
            'phone' => '1112223333',
        ]);

        $this->patientB = Patient::create([
            'first_name' => 'Bob',
            'last_name' => 'Miller',
            'email' => 'bob@example.com',
            'phone' => '4445556666',
        ]);
    }

    public function test_it_successfully_books_an_open_slot(): void
    {
        $slotStart = Carbon::parse('2026-11-16 10:00:00');
        $slotEnd = Carbon::parse('2026-11-16 10:30:00');

        $appointment = $this->service->book(
            doctor: $this->doctor,
            patient: $this->patientA,
            startTime: $slotStart,
            endTime: $slotEnd,
            patientNotes: 'Recurring headache'
        );

        $this->assertDatabaseHas('appointments', [
            'id' => $appointment->id,
            'doctor_id' => $this->doctor->id,
            'patient_id' => $this->patientA->id,
            'status' => AppointmentStatus::Confirmed->value,
            'appointment_number' => $appointment->appointment_number,
        ]);

        // Verify audit log creation
        $this->assertDatabaseHas('appointment_audit_logs', [
            'appointment_id' => $appointment->id,
            'action' => 'created',
            'to_status' => AppointmentStatus::Confirmed->value,
        ]);
    }

    public function test_it_prevents_double_booking_and_throws_conflict_exception(): void
    {
        $slotStart = Carbon::parse('2026-11-16 14:00:00');
        $slotEnd = Carbon::parse('2026-11-16 14:30:00');

        // 1. First booking succeeds
        $firstAppointment = $this->service->book(
            doctor: $this->doctor,
            patient: $this->patientA,
            startTime: $slotStart,
            endTime: $slotEnd
        );

        $this->assertNotNull($firstAppointment);

        // 2. Second booking attempt for the exact same doctor and time window must fail
        $this->expectException(AppointmentConflictException::class);
        $this->expectExceptionMessage('This time slot is no longer available. Please select another slot.');

        $this->service->book(
            doctor: $this->doctor,
            patient: $this->patientB,
            startTime: $slotStart,
            endTime: $slotEnd
        );
    }

    public function test_cancelling_an_appointment_frees_up_the_slot(): void
    {
        $slotStart = Carbon::parse('2026-11-16 15:00:00');
        $slotEnd = Carbon::parse('2026-11-16 15:30:00');

        // Book slot
        $appointment = $this->service->book(
            doctor: $this->doctor,
            patient: $this->patientA,
            startTime: $slotStart,
            endTime: $slotEnd
        );

        // Cancel it
        $this->service->cancel($appointment, 'Patient requested cancellation');

        // Verify it was marked as cancelled
        $this->assertEquals(AppointmentStatus::Cancelled, $appointment->fresh()->status);

        // Now Patient B should be able to book the exact same slot without collision
        $newAppointment = $this->service->book(
            doctor: $this->doctor,
            patient: $this->patientB,
            startTime: $slotStart,
            endTime: $slotEnd
        );

        $this->assertNotNull($newAppointment);
        $this->assertEquals(AppointmentStatus::Confirmed, $newAppointment->status);
    }
}
