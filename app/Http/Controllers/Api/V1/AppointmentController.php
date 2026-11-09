<?php

namespace App\Http\Controllers\Api\V1;

use App\Exceptions\AppointmentConflictException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\CancelAppointmentRequest;
use App\Http\Requests\Api\StoreAppointmentRequest;
use App\Http\Resources\AppointmentResource;
use App\Http\Resources\DoctorResource;
use App\Models\Appointment;
use App\Models\Doctor;
use App\Models\Patient;
use App\Services\AppointmentService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AppointmentController extends Controller
{
    public function __construct(
        protected AppointmentService $service
    ) {}

    /**
     * List all active doctors
     */
    public function doctors(): JsonResponse
    {
        $doctors = Doctor::with('clinic')->where('is_active', true)->get();

        return response()->json([
            'success' => true,
            'data' => DoctorResource::collection($doctors),
        ]);
    }

    /**
     * Get real-time available slots for a doctor on a specific date
     */
    public function slots(Request $request, Doctor $doctor): JsonResponse
    {
        $request->validate([
            'date' => 'required|date_format:Y-m-d',
        ]);

        $date = Carbon::parse($request->query('date'));
        $slots = $this->service->getAvailableSlots($doctor, $date);

        return response()->json([
            'success' => true,
            'doctor' => $doctor->name,
            'date' => $date->toDateString(),
            'total_open' => $slots->where('is_available', true)->count(),
            'slots' => $slots->values(),
        ]);
    }

    /**
     * Book a new appointment via REST API (concurrency-safe)
     */
    public function store(StoreAppointmentRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $doctor = Doctor::with('clinic')->findOrFail($validated['doctor_id']);

        $patient = Patient::firstOrCreate(
            ['email' => strtolower(trim($validated['email']))],
            [
                'first_name' => trim($validated['first_name']),
                'last_name' => trim($validated['last_name']),
                'phone' => trim($validated['phone']),
            ]
        );

        $startTime = Carbon::parse($validated['start_time']);
        $endTime = (clone $startTime)->addMinutes($doctor->slot_duration_minutes);

        try {
            $appointment = $this->service->book(
                doctor: $doctor,
                patient: $patient,
                startTime: $startTime,
                endTime: $endTime,
                patientNotes: $validated['patient_notes'] ?? null
            );

            return response()->json([
                'success' => true,
                'message' => 'Appointment booked successfully.',
                'data' => new AppointmentResource($appointment->load(['doctor', 'clinic', 'patient'])),
            ], Response::HTTP_CREATED);

        } catch (AppointmentConflictException $e) {
            return response()->json([
                'success' => false,
                'error' => 'SlotConflict',
                'message' => $e->getMessage(),
            ], Response::HTTP_CONFLICT);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'An unexpected server error occurred during reservation.',
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Check appointment status by reference number
     */
    public function show(string $referenceNumber): JsonResponse
    {
        $appointment = Appointment::with(['doctor', 'clinic', 'patient'])
            ->where('appointment_number', $referenceNumber)
            ->first();

        if (! $appointment) {
            return response()->json([
                'success' => false,
                'message' => "Appointment {$referenceNumber} not found.",
            ], Response::HTTP_NOT_FOUND);
        }

        return response()->json([
            'success' => true,
            'data' => new AppointmentResource($appointment),
        ]);
    }

    /**
     * Cancel an appointment via REST API
     */
    public function cancel(CancelAppointmentRequest $request, string $referenceNumber): JsonResponse
    {
        $appointment = Appointment::where('appointment_number', $referenceNumber)->first();

        if (! $appointment) {
            return response()->json([
                'success' => false,
                'message' => "Appointment {$referenceNumber} not found.",
            ], Response::HTTP_NOT_FOUND);
        }

        if ($appointment->status->isFinal()) {
            return response()->json([
                'success' => false,
                'message' => "Appointment is already {$appointment->status->value} and cannot be cancelled.",
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $this->service->cancel($appointment, $request->validated('reason'));

        return response()->json([
            'success' => true,
            'message' => 'Appointment cancelled successfully.',
            'data' => new AppointmentResource($appointment->fresh(['doctor', 'clinic', 'patient'])),
        ]);
    }
}
