<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\CompleteRegistrationRequest;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterEmailRequest;
use App\Http\Requests\Auth\ResendOtpRequest;
use App\Http\Requests\Auth\VerifyOtpRequest;
use App\Http\Resources\UserResource;
use App\Http\Responses\ApiResponse;
use App\Models\User;
use App\Services\AuthService;
use App\Services\RegistrationOtpService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException;

class AuthController extends Controller
{
    public function __construct(
        private RegistrationOtpService $registrationOtpService,
        private AuthService $authService
    ) {}

    public function registerEmail(RegisterEmailRequest $request): JsonResponse
    {
        $this->registrationOtpService->requestOtp($request->validated('email'));

        return ApiResponse::success('OTP sent to your email.', null);
    }

    public function verifyOtp(VerifyOtpRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $this->registrationOtpService->verifyOtp($validated['email'], $validated['otp']);

        return ApiResponse::success('OTP verified. You can complete registration.', null);
    }

    public function completeRegistration(CompleteRegistrationRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $user = $this->authService->completeRegistration(
            $validated['email'],
            $validated['password']
        );

        $token = $user->createToken('auth')->plainTextToken;

        return ApiResponse::success('Registration completed.', [
            'user' => new UserResource($user),
            'token' => $token,
            'token_type' => 'Bearer',
        ], 201);
    }

    public function resendOtp(ResendOtpRequest $request): JsonResponse
    {
        try {
            $this->registrationOtpService->resendOtp($request->validated('email'));
        } catch (TooManyRequestsHttpException $e) {
            return ApiResponse::error($e->getMessage(), null, 429);
        }

        return ApiResponse::success('A new OTP has been sent to your email.', null);
    }

    public function login(LoginRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $result = $this->authService->login($validated['email'], $validated['password']);

        if ($result === null) {
            return ApiResponse::error(__('auth.failed'), null, 401);
        }

        return ApiResponse::success('Login successful.', [
            'user' => new UserResource($result['user']),
            'token' => $result['token'],
            'token_type' => 'Bearer',
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()?->currentAccessToken()?->delete();

        return ApiResponse::success('Logged out successfully.', null);
    }

    public function profile(Request $request): JsonResponse
    {
        $user = $request->user();
        if ($user->user_type === User::USER_TYPE_EXPERT) {
            $user->load([
                'approvedExpertApplication.category',
                'approvedExpertApplication.subcategory',
                'approvedExpertApplication.skills',
            ]);
        }

        return ApiResponse::success('Profile retrieved.', [
            'user' => new UserResource($user),
        ]);
    }
}
