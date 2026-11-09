<?php

namespace App\Enums;

enum UserRole: string
{
    case Admin = 'admin';
    case Doctor = 'doctor';
    case Receptionist = 'receptionist';
    case Patient = 'patient';

    public function label(): string
    {
        return match ($this) {
            self::Admin => 'Administrator',
            self::Doctor => 'Attending Physician',
            self::Receptionist => 'Clinic Receptionist',
            self::Patient => 'Registered Patient',
        };
    }
}
