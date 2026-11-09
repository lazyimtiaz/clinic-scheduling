<?php

namespace App\Models;

use App\Enums\AppointmentStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Appointment extends Model
{
    use HasFactory;

    protected $fillable = [
        'appointment_number',
        'doctor_id',
        'patient_id',
        'clinic_id',
        'start_time',
        'end_time',
        'status',
        'cancellation_reason',
        'patient_notes',
    ];

    protected $casts = [
        'start_time' => 'datetime',
        'end_time' => 'datetime',
        'status' => AppointmentStatus::class,
        'patient_notes' => 'encrypted', // Encrypted at rest
    ];

    public function doctor(): BelongsTo
    {
        return $this->belongsTo(Doctor::class);
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function clinic(): BelongsTo
    {
        return $this->belongsTo(Clinic::class);
    }

    public function auditLogs(): HasMany
    {
        return $this->hasMany(AppointmentAuditLog::class)->latest();
    }

    /**
     * Scope to find appointments that are active (blocking the doctor's calendar)
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->whereIn('status', [
            AppointmentStatus::Pending,
            AppointmentStatus::Confirmed,
            AppointmentStatus::Arrived,
            AppointmentStatus::InConsultation,
        ]);
    }
}
