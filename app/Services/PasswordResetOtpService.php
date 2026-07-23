<?php

namespace App\Services;

use App\Mail\OtpMail;
use App\Models\PasswordResetOtp;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException;

class PasswordResetOtpService
{
    public function requestOtp(string $email): void
    {
        if (! User::where('email', $email)->exists()) {
            throw ValidationException::withMessages([
                'email' => ['No account found for this email.'],
            ]);
        }

        $plain = $this->generateOtp();

        PasswordResetOtp::updateOrCreate(
            ['email' => $email],
            [
                'otp' => Hash::make($plain),
                'expires_at' => now()->addMinutes(config('otp.expiry_minutes')),
                'verified_at' => null,
                'verification_expires_at' => null,
            ]
        );

        Mail::to($email)->send(new OtpMail($plain, 'Your password reset verification code'));
    }

    public function verifyOtp(string $email, string $otp): void
    {
        $record = PasswordResetOtp::where('email', $email)->first();

        if (! $record || ! Hash::check($otp, $record->otp) || $record->expires_at->isPast()) {
            throw ValidationException::withMessages([
                'otp' => ['Invalid or expired OTP.'],
            ]);
        }

        $record->update([
            'verified_at' => now(),
            'verification_expires_at' => now()->addMinutes(config('otp.password_reset_ttl_minutes')),
        ]);
    }

    public function resendOtp(string $email): void
    {
        $key = 'password-reset-otp-resend:'.$email;
        $max = config('otp.resend.max_attempts');
        $decaySeconds = (int) (config('otp.resend.decay_minutes') * 60);

        if (RateLimiter::tooManyAttempts($key, $max)) {
            throw new TooManyRequestsHttpException(
                RateLimiter::availableIn($key),
                'Too many OTP resend attempts. Please try again later.'
            );
        }

        RateLimiter::hit($key, $decaySeconds);

        if (! PasswordResetOtp::where('email', $email)->exists()) {
            throw ValidationException::withMessages([
                'email' => ['No pending password reset found for this email.'],
            ]);
        }

        if (! User::where('email', $email)->exists()) {
            throw ValidationException::withMessages([
                'email' => ['No account found for this email.'],
            ]);
        }

        $plain = $this->generateOtp();

        PasswordResetOtp::where('email', $email)->update([
            'otp' => Hash::make($plain),
            'expires_at' => now()->addMinutes(config('otp.expiry_minutes')),
            'verified_at' => null,
            'verification_expires_at' => null,
        ]);

        Mail::to($email)->send(new OtpMail($plain, 'Your password reset verification code'));
    }

    public function resetPassword(string $email, string $password): User
    {
        $record = PasswordResetOtp::where('email', $email)->first();

        if (! $record || ! $record->verified_at) {
            throw ValidationException::withMessages([
                'email' => ['Email must be verified with OTP before resetting password.'],
            ]);
        }

        if (! $record->verification_expires_at || $record->verification_expires_at->isPast()) {
            throw ValidationException::withMessages([
                'email' => ['Verification expired. Please restart password reset.'],
            ]);
        }

        $user = User::where('email', $email)->first();

        if ($user === null) {
            throw ValidationException::withMessages([
                'email' => ['No account found for this email.'],
            ]);
        }

        $user->update(['password' => $password]);
        $user->tokens()->delete();
        $record->delete();

        return $user;
    }

    private function generateOtp(): string
    {
        return str_pad((string) random_int(0, 9999), 4, '0', STR_PAD_LEFT);
    }
}
