<?php

namespace App\Services;

use App\Models\RegistrationOtp;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthService
{
    public function completeRegistration(string $email, string $password): User
    {
        $record = RegistrationOtp::where('email', $email)->first();

        if (!$record || !$record->verified_at) {
            throw ValidationException::withMessages([
                'email' => ['Email must be verified with OTP before completing registration.'],
            ]);
        }

        if (!$record->verification_expires_at || $record->verification_expires_at->isPast()) {
            throw ValidationException::withMessages([
                'email' => ['Verification expired. Please restart registration.'],
            ]);
        }

        if (User::where('email', $email)->exists()) {
            throw ValidationException::withMessages([
                'email' => ['The email is already registered.'],
            ]);
        }

        $user = User::create([
            'name' => $this->defaultNameFromEmail($email),
            'email' => $email,
            'password' => $password,
            'user_type' => User::USER_TYPE_USER,
            'email_verified_at' => $record->verified_at,
        ]);

        $record->delete();

        return $user;
    }

    public function login(string $email, string $password): ?array
    {
        $user = User::where('email', $email)->first();

        if (!$user || !Hash::check($password, $user->password)) {
            return null;
        }

        return [
            'user' => $user,
            'token' => $user->createToken('auth')->plainTextToken,
        ];
    }

    private function defaultNameFromEmail(string $email): string
    {
        $local = strstr($email, '@', true);

        return ($local !== false && $local !== '') ? $local : 'User';
    }
}
