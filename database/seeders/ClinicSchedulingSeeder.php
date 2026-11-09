<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\Clinic;
use App\Models\Doctor;
use App\Models\DoctorSchedule;
use App\Models\Patient;
use App\Models\User;
use Illuminate\Database\Seeder;

class ClinicSchedulingSeeder extends Seeder
{
    public function run(): void
    {
        // Create Staff Users
        $doctorUser = User::firstOrCreate(
            ['email' => 'sarah@metrohealth.example.com'],
            [
                'name' => 'Dr. Sarah Mitchell',
                'password' => bcrypt('password'),
                'role' => UserRole::Doctor,
            ]
        );

        $receptionistUser = User::firstOrCreate(
            ['email' => 'reception@metrohealth.example.com'],
            [
                'name' => 'Emma Receptionist',
                'password' => bcrypt('password'),
                'role' => UserRole::Receptionist,
            ]
        );

        $adminUser = User::firstOrCreate(
            ['email' => 'admin@metrohealth.example.com'],
            [
                'name' => 'Clinic Administrator',
                'password' => bcrypt('password'),
                'role' => UserRole::Admin,
            ]
        );

        // 1. Create a Clinic
        $clinic = Clinic::create([
            'name' => 'Metro Health Clinic',
            'slug' => 'metro-health-clinic',
            'phone' => '+1 (555) 019-2834',
            'email' => 'contact@metrohealth.example.com',
            'address' => '742 Evergreen Terrace, Springfield',
            'timezone' => 'America/New_York',
            'is_active' => true,
        ]);

        // 2. Create Doctors
        $doctor = Doctor::create([
            'clinic_id' => $clinic->id,
            'name' => 'Dr. Sarah Mitchell, MD',
            'email' => 'sarah.mitchell@metrohealth.example.com',
            'phone' => '+1 (555) 998-1122',
            'specialization' => 'Cardiologist',
            'bio' => 'Board-certified cardiologist with over 12 years of experience in preventative heart care.',
            'slot_duration_minutes' => 30,
            'consultation_fee' => 120.00,
            'is_active' => true,
        ]);

        // 3. Setup Recurring Schedule (Monday to Friday, 09:00 to 17:00 with lunch break 13:00-14:00)
        // 1 = Monday, 5 = Friday
        for ($day = 1; $day <= 5; $day++) {
            DoctorSchedule::create([
                'doctor_id' => $doctor->id,
                'day_of_week' => $day,
                'start_time' => '09:00:00',
                'end_time' => '17:00:00',
                'break_start' => '13:00:00',
                'break_end' => '14:00:00',
                'is_available' => true,
            ]);
        }

        // 4. Create Patients
        Patient::create([
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => 'john.doe@example.com',
            'phone' => '+1 (555) 123-4567',
            'date_of_birth' => '1988-04-12',
            'gender' => 'male',
            'medical_notes' => 'History of mild hypertension.',
        ]);

        Patient::create([
            'first_name' => 'Emily',
            'last_name' => 'Watson',
            'email' => 'emily.watson@example.com',
            'phone' => '+1 (555) 765-4321',
            'date_of_birth' => '1993-08-23',
            'gender' => 'female',
        ]);
    }
}
