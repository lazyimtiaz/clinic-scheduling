<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class StoreAppointmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'doctor_id' => 'required|exists:doctors,id',
            'start_time' => 'required|date_format:Y-m-d H:i:s|after:now',
            'first_name' => 'required|string|min:2|max:50',
            'last_name' => 'required|string|min:2|max:50',
            'email' => 'required|email|max:100',
            'phone' => 'required|string|min:7|max:20',
            'patient_notes' => 'nullable|string|max:500',
        ];
    }

    public function messages(): array
    {
        return [
            'start_time.after' => 'Appointments cannot be booked in the past.',
        ];
    }
}
