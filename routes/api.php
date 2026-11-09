<?php

use App\Http\Controllers\Api\V1\AppointmentController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::prefix('v1')->middleware('throttle:api')->group(function () {
    // Doctors & Availability
    Route::get('/doctors', [AppointmentController::class, 'doctors']);
    Route::get('/doctors/{doctor}/slots', [AppointmentController::class, 'slots']);

    // Appointment Operations
    Route::post('/appointments', [AppointmentController::class, 'store']);
    Route::get('/appointments/{referenceNumber}', [AppointmentController::class, 'show']);
    Route::post('/appointments/{referenceNumber}/cancel', [AppointmentController::class, 'cancel']);
});
