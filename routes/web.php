<?php

use App\Http\Controllers\AuthController;
use Illuminate\Support\Facades\Route;

// Public Booking Portal (unauthenticated)
Route::get('/', function () {
    return view('welcome');
})->name('home');

// Authentication Routes
Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login'])->name('login.post');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

// Role-Protected Clinical Portals
Route::middleware('auth')->prefix('portal')->group(function () {
    Route::get('/patient', function () {
        return view('portal.patient');
    })->middleware('role:patient')->name('portal.patient');

    Route::get('/doctor', function () {
        return view('portal.doctor');
    })->middleware('role:doctor')->name('portal.doctor');

    Route::get('/reception', function () {
        return view('portal.reception');
    })->middleware('role:receptionist')->name('portal.reception');
});
