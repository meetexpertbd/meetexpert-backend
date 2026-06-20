<?php

use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\ExpertApplicationController;
use App\Http\Controllers\Api\V1\ExpertAvailabilityController;
use App\Http\Controllers\Api\V1\ExpertBookingController;
use App\Http\Controllers\Api\V1\ExpertController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function (): void {
    Route::get('experts', [ExpertController::class, 'index']);
    Route::get('experts/{user}/available-slots', [ExpertController::class, 'availableSlots']);
    Route::get('experts/{user}', [ExpertController::class, 'show']);
    Route::prefix('auth')->group(function (): void {
        Route::post('register/email', [AuthController::class, 'registerEmail']);
        Route::post('register/verify-otp', [AuthController::class, 'verifyOtp']);
        Route::post('register/complete', [AuthController::class, 'completeRegistration']);
        Route::post('register/resend-otp', [AuthController::class, 'resendOtp']);
        Route::post('login', [AuthController::class, 'login']);
        Route::post('logout', [AuthController::class, 'logout'])->middleware('auth:sanctum');
    });

    Route::middleware('auth:sanctum')->group(function (): void {
        Route::get('profile', [AuthController::class, 'profile']);
        Route::post('expert/application', [ExpertApplicationController::class, 'store']);
        Route::get('expert/availability', [ExpertAvailabilityController::class, 'show']);
        Route::put('expert/availability', [ExpertAvailabilityController::class, 'update']);

        Route::get('bookings', [ExpertBookingController::class, 'index']);
        Route::post('bookings', [ExpertBookingController::class, 'store']);
        Route::get('bookings/{booking}', [ExpertBookingController::class, 'show']);
        Route::get('bookings/{booking}/meeting', [ExpertBookingController::class, 'meeting']);
        Route::post('bookings/{booking}/cancel', [ExpertBookingController::class, 'cancel']);
    });
});
