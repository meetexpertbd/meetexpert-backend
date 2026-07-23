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
        Route::post('check-email', [AuthController::class, 'checkEmail']);
        Route::post('register/email', [AuthController::class, 'checkEmail']);
        Route::post('register/verify-otp', [AuthController::class, 'verifyOtp']);
        Route::post('register/complete', [AuthController::class, 'completeRegistration']);
        Route::post('register/resend-otp', [AuthController::class, 'resendOtp']);
        Route::post('forgot-password', [AuthController::class, 'forgotPassword']);
        Route::post('forgot-password/verify-otp', [AuthController::class, 'verifyPasswordResetOtp']);
        Route::post('forgot-password/resend-otp', [AuthController::class, 'resendPasswordResetOtp']);
        Route::post('forgot-password/reset', [AuthController::class, 'resetPassword']);
        Route::post('login', [AuthController::class, 'login']);
        Route::post('logout', [AuthController::class, 'logout'])->middleware('auth:sanctum');
    });
    Route::middleware('auth:sanctum')->group(function (): void {
        Route::prefix('user')->group(function (): void {
            Route::get('profile', [AuthController::class, 'profile']);
            Route::post('profile', [AuthController::class, 'updateProfile']);
        });

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
