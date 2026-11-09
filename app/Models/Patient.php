<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Notifications\Notifiable;

class Patient extends Model
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'first_name',
        'last_name',
        'email',
        'phone',
        'date_of_birth',
        'gender',
        'medical_notes',
    ];

    protected $casts = [
        'date_of_birth' => 'date',
        'medical_notes' => 'encrypted', // Automatically encrypted at rest in MySQL!
    ];

    public function appointments(): HasMany
    {
        return $this->hasMany(Appointment::class);
    }

    // Accessor for clean full name in UI
    public function getFullNameAttribute(): string
    {
        return "{$this->first_name} {$this->last_name}";
    }
}
