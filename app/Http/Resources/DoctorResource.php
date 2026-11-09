<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DoctorResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'specialization' => $this->specialization,
            'bio' => $this->bio,
            'slot_duration_minutes' => $this->slot_duration_minutes,
            'consultation_fee' => (float) $this->consultation_fee,
            'clinic' => [
                'id' => $this->clinic->id,
                'name' => $this->clinic->name,
                'address' => $this->clinic->address,
                'timezone' => $this->clinic->timezone,
            ],
        ];
    }
}
