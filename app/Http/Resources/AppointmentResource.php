<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AppointmentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'reference_number' => $this->appointment_number,
            'status' => $this->status->value,
            'status_label' => $this->status->label(),
            'start_time' => $this->start_time->toISOString(),
            'end_time' => $this->end_time->toISOString(),
            'doctor' => [
                'name' => $this->doctor->name,
                'specialization' => $this->doctor->specialization,
                'fee' => (float) $this->doctor->consultation_fee,
            ],
            'clinic' => [
                'name' => $this->clinic->name,
                'address' => $this->clinic->address,
                'timezone' => $this->clinic->timezone,
            ],
            'patient' => [
                'name' => $this->patient->full_name,
                'email' => $this->patient->email,
                'phone' => $this->patient->phone,
            ],
            'patient_notes' => $this->patient_notes,
            'created_at' => $this->created_at->toISOString(),
        ];
    }
}
