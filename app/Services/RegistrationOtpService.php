<?php

namespace App\Services;

use App\Mail\OtpMail;
use App\Models\RegistrationOtp;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException;

class RegistrationOtpService
{
    public function requestOtp(string $email): void
    {
        if (User::where('email', $email)->exists()) {
            throw ValidationException::withMessages([
                'email' => ['The email is already registered.'],
            ]);
        }

        $plain = $this->generateOtp();

        RegistrationOtp::updateOrCreate(
            ['email' => $email],
            [
                'otp' => Hash::make($plain),
                'expires_at' => now()->addMinutes(config('otp.expiry_minutes')),
                'verified_at' => null,
                'verification_expires_at' => null,
            ]
        );

        Mail::to($email)->send(new OtpMail($plain));
    }

    public function verifyOtp(string $email, string $otp): void
    {
        $record = RegistrationOtp::where('email', $email)->first();

        if (! $record || ! Hash::check($otp, $record->otp) || $record->expires_at->isPast()) {
            throw ValidationException::withMessages([
                'otp' => ['Invalid or expired OTP.'],
            ]);
        }

        $record->update([
            'verified_at' => now(),
            'verification_expires_at' => now()->addMinutes(config('otp.registration_complete_ttl_minutes')),
        ]);
    }

    public function resendOtp(string $email): void
    {
        $key = 'otp-resend:'.$email;
        $max = config('otp.resend.max_attempts');
        $decaySeconds = (int) (config('otp.resend.decay_minutes') * 60);

        if (RateLimiter::tooManyAttempts($key, $max)) {
            throw new TooManyRequestsHttpException(
                RateLimiter::availableIn($key),
                'Too many OTP resend attempts. Please try again later.'
            );
        }

        RateLimiter::hit($key, $decaySeconds);

        if (! RegistrationOtp::where('email', $email)->exists()) {
            throw ValidationException::withMessages([
                'email' => ['No pending registration found for this email.'],
            ]);
        }

        if (User::where('email', $email)->exists()) {
            throw ValidationException::withMessages([
                'email' => ['The email is already registered.'],
            ]);
        }

        $plain = $this->generateOtp();

        RegistrationOtp::where('email', $email)->update([
            'otp' => Hash::make($plain),
            'expires_at' => now()->addMinutes(config('otp.expiry_minutes')),
            'verified_at' => null,
            'verification_expires_at' => null,
        ]);

        Mail::to($email)->send(new OtpMail($plain));
    }

    private function generateOtp(): string
    {
        return str_pad((string) random_int(0, 9999), 4, '0', STR_PAD_LEFT);
    }
}
