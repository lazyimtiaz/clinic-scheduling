<?php

namespace App\Enums;

enum AppointmentStatus: string
{
    case Pending = 'pending';
    case Confirmed = 'confirmed';
    case Arrived = 'arrived';                 // Patient in waiting room
    case InConsultation = 'in_consultation';   // With the doctor
    case Completed = 'completed';
    case Cancelled = 'cancelled';
    case NoShow = 'no_show';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Pending',
            self::Confirmed => 'Confirmed',
            self::Arrived => 'Waiting Room',
            self::InConsultation => 'In Consultation',
            self::Completed => 'Completed',
            self::Cancelled => 'Cancelled',
            self::NoShow => 'No Show',
        };
    }

    public function badgeColor(): string
    {
        return match ($this) {
            self::Pending => 'bg-amber-50 text-amber-800 border-amber-200',
            self::Confirmed => 'bg-blue-50 text-blue-800 border-blue-200',
            self::Arrived => 'bg-purple-50 text-purple-800 border-purple-200',
            self::InConsultation => 'bg-indigo-50 text-indigo-800 border-indigo-200',
            self::Completed => 'bg-emerald-50 text-emerald-800 border-emerald-200',
            self::Cancelled => 'bg-rose-50 text-rose-800 border-rose-200',
            self::NoShow => 'bg-slate-100 text-slate-700 border-slate-200',
        };
    }

    public function isFinal(): bool
    {
        return in_array($this, [self::Completed, self::Cancelled, self::NoShow]);
    }

    public function isBlocking(): bool
    {
        // Any appointment that actively consumes a doctor's calendar slot
        return in_array($this, [self::Pending, self::Confirmed, self::Arrived, self::InConsultation]);
    }
}
