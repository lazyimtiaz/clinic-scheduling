# 🏥 CuraPoint — Clinic Scheduling & Patient Workflow Platform

[![Continuous Integration](https://github.com/your-username/clinic-scheduling/actions/workflows/ci.yml/badge.svg)](https://github.com/your-username/clinic-scheduling/actions)
![PHP 8.3+](https://img.shields.io/badge/PHP-8.3%2B-blue.svg)
![Laravel 12](https://img.shields.io/badge/Laravel-12.x-red.svg)
![Livewire 3](https://img.shields.io/badge/Livewire-3.x-pink.svg)
![Tailwind CSS v4](https://img.shields.io/badge/Tailwind-v4.x-teal.svg)

> An enterprise-grade clinical scheduling and operations engine. Built to demonstrate **concurrency-safe transactional booking**, **dynamic availability algorithms**, **HIPAA-aligned encrypted sensitive data (PHI)**, and **asynchronous queued notifications**.

---

## 🏛️ System Architecture

```mermaid
flowchart TD
    subgraph Clients["Frontend Clients & Consumers"]
        PB["Patient Booking Portal (Livewire 3)"]
        RT["Receptionist Triage Console (Livewire 3)"]
        DC["Doctor Operations Desk (Livewire 3)"]
        API["Mobile & Third-Party Apps (REST API v1)"]
    end

    subgraph AppLayer["Laravel Application Layer"]
        AUTH["Sanctum & Policy Guards"]
        SV["AppointmentService (Availability Engine)"]
        LOCK["Pessimistic Row Mutex (lockForUpdate)"]
    end

    subgraph AsyncLayer["Asynchronous Worker Pool"]
        QUEUE["Redis / DB Queue Worker"]
        MAIL["Async Email Dispatcher (DB::afterCommit)"]
    end

    subgraph Storage["Data Tier"]
        DB[("MySQL 8.0 (Encrypted PHI at rest)")]
        LOG[("Immutable Audit Trail")]
    end

    PB --> AUTH
    RT --> AUTH
    DC --> AUTH
    API --> AUTH
    AUTH --> SV
    SV --> LOCK
    LOCK --> DB
    LOCK --> LOG
    SV -.-> QUEUE
    QUEUE --> MAIL
```

---

## 📊 Entity Relationship Diagram (ERD)

```mermaid
erDiagram
    CLINICS ||--o{ DOCTORS : "houses"
    CLINICS ||--o{ APPOINTMENTS : "hosts"
    DOCTORS ||--o{ DOCTOR_SCHEDULES : "works"
    DOCTORS ||--o{ DOCTOR_LEAVES : "takes"
    DOCTORS ||--o{ APPOINTMENTS : "attends"
    PATIENTS ||--o{ APPOINTMENTS : "books"
    APPOINTMENTS ||--o{ APPOINTMENT_AUDIT_LOGS : "tracks"
    USERS ||--o{ APPOINTMENT_AUDIT_LOGS : "triggers"

    CLINICS {
        bigint id PK
        string name
        string timezone
    }

    DOCTORS {
        bigint id PK
        foreignId clinic_id FK
        string name
        string specialization
        decimal consultation_fee
        integer slot_duration_minutes
    }

    PATIENTS {
        bigint id PK
        string first_name
        string last_name
        string email
        text medical_notes "Encrypted AES-256"
    }

    APPOINTMENTS {
        bigint id PK
        string appointment_number UK
        foreignId doctor_id FK
        foreignId patient_id FK
        datetime start_time
        datetime end_time
        string status
        text patient_notes "Encrypted AES-256"
    }

    APPOINTMENT_AUDIT_LOGS {
        bigint id PK
        foreignId appointment_id FK
        string action
        string from_status
        string to_status
        text notes
    }
```

---

## 🔑 Key Engineering Signals

### 1. Concurrency-Safe Booking (Pessimistic Row-Locking)
When two users attempt to book the identical 30-minute interval simultaneously, a race condition occurs. CuraPoint guards this operation inside an atomic transaction:
```php
DB::transaction(function () use ($doctor, $patient, $startTime, $endTime) {
    Doctor::where('id', $doctor->id)->lockForUpdate()->first();

    $overlap = Appointment::active()
        ->where('doctor_id', $doctor->id)
        ->where('start_time', '<', $endTime)
        ->where('end_time', '>', $startTime)
        ->exists();

    if ($overlap) {
        throw new AppointmentConflictException("Slot no longer available.");
    }

    // Insert Appointment & Audit Log atomically
});
```

### 2. Encryption at Rest for Sensitive Health Information (PHI)
Patient medical notes are cast with Laravel's native `'encrypted'` cast:
```php
protected $casts = [
    'medical_notes' => 'encrypted', // Automatically encrypted with AES-256-CBC
];
```

### 3. Asynchronous Queues via `DB::afterCommit()`
Email confirmations are dispatched to a background queue only after database transactions commit cleanly:
```php
DB::afterCommit(function () use ($appointment, $patient) {
    $patient->notify(new AppointmentBookedNotification($appointment));
});
```

---

## 🧪 Automated Test Suite

Includes automated unit, feature, and concurrency collision tests:
```bash
php artisan test
```

Test breakdown:
- `AppointmentAvailabilityTest`: Validates schedule boundaries, lunch intervals, holiday leaves, and past slot filtering.
- `AppointmentConcurrencyTest`: Simulates simultaneous requests against the same slot and verifies `AppointmentConflictException` is thrown.

---

## 👥 Demo Credentials for Evaluators

| Role | Email | Password | Access Level |
| :--- | :--- | :--- | :--- |
| **Attending Doctor** | `sarah@metrohealth.example.com` | `password` | Clinical Console, status completions, medical notes |
| **Receptionist** | `reception@metrohealth.example.com` | `password` | Front-desk triage, waiting room check-in queue |
| **Administrator** | `admin@metrohealth.example.com` | `password` | Full clinic & provider configuration |

---

## 🛠️ Local Setup

```bash
# 1. Clone & Install
git clone https://github.com/your-username/clinic-scheduling.git
cd clinic-scheduling
composer install
npm install

# 2. Setup Environment
cp .env.example .env
php artisan key:generate

# 3. Migrate & Seed Realistic Clinical Data
php artisan migrate:fresh --seed

# 4. Start Services
npm run dev
php artisan serve
```
