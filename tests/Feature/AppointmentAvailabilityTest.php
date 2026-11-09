<?php

namespace Tests\Feature;

use App\Models\Clinic;
use App\Models\Doctor;
use App\Models\DoctorLeave;
use App\Models\DoctorSchedule;
use App\Services\AppointmentService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AppointmentAvailabilityTest extends TestCase
{
    use RefreshDatabase;

    protected AppointmentService $service;

    protected Doctor $doctor;

    protected Clinic $clinic;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = new AppointmentService;

        $this->clinic = Clinic::create([
            'name' => 'Test Clinic',
            'slug' => 'test-clinic',
            'timezone' => 'UTC',
            'is_active' => true,
        ]);

        $this->doctor = Doctor::create([
            'clinic_id' => $this->clinic->id,
            'name' => 'Dr. Jane Smith',
            'email' => 'jane@example.com',
            'specialization' => 'Neurology',
            'slot_duration_minutes' => 30,
            'consultation_fee' => 150.00,
            'is_active' => true,
        ]);
    }

    public function test_it_generates_slots_for_doctor_working_hours(): void
    {
        // Doctor works Mondays 09:00 to 12:00 (3 hours = 6 slots of 30 mins)
        DoctorSchedule::create([
            'doctor_id' => $this->doctor->id,
            'day_of_week' => 1, // Monday
            'start_time' => '09:00:00',
            'end_time' => '12:00:00',
            'is_available' => true,
        ]);

        $targetDate = Carbon::parse('2026-11-16'); // Known Monday

        $slots = $this->service->getAvailableSlots($this->doctor, $targetDate);

        $this->assertCount(6, $slots);
        $this->assertEquals('09:00 AM - 09:30 AM', $slots->first()['display']);
        $this->assertEquals('11:30 AM - 12:00 PM', $slots->last()['display']);
    }

    public function test_it_excludes_break_times_from_generated_slots(): void
    {
        // Doctor works Mondays 09:00 to 13:00 with lunch 11:00 to 12:00
        // Total 4 hrs = 8 slots - 2 lunch slots = 6 slots
        DoctorSchedule::create([
            'doctor_id' => $this->doctor->id,
            'day_of_week' => 1,
            'start_time' => '09:00:00',
            'end_time' => '13:00:00',
            'break_start' => '11:00:00',
            'break_end' => '12:00:00',
            'is_available' => true,
        ]);

        $targetDate = Carbon::parse('2026-11-16');

        $slots = $this->service->getAvailableSlots($this->doctor, $targetDate);

        $this->assertCount(6, $slots);

        // Verify that 11:00 to 12:00 slots are NOT present
        $displays = $slots->pluck('display')->toArray();
        $this->assertNotContains('11:00 AM - 11:30 AM', $displays);
        $this->assertNotContains('11:30 AM - 12:00 PM', $displays);
    }

    public function test_it_returns_zero_slots_when_doctor_is_on_leave(): void
    {
        DoctorSchedule::create([
            'doctor_id' => $this->doctor->id,
            'day_of_week' => 1,
            'start_time' => '09:00:00',
            'end_time' => '17:00:00',
            'is_available' => true,
        ]);

        $targetDate = Carbon::parse('2026-11-16');

        // Create a leave on that date
        DoctorLeave::create([
            'doctor_id' => $this->doctor->id,
            'leave_date' => $targetDate->toDateString(),
            'reason' => 'Attending medical conference',
        ]);

        $slots = $this->service->getAvailableSlots($this->doctor, $targetDate);

        $this->assertCount(0, $slots);
    }
}
