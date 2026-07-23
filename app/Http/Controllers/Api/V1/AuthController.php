<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\UpdateUserProfileRequest;
use App\Http\Requests\Auth\CompleteRegistrationRequest;
use App\Http\Requests\Auth\ForgotPasswordRequest;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterEmailRequest;
use App\Http\Requests\Auth\ResendOtpRequest;
use App\Http\Requests\Auth\ResendPasswordResetOtpRequest;
use App\Http\Requests\Auth\ResetPasswordRequest;
use App\Http\Requests\Auth\VerifyOtpRequest;
use App\Http\Requests\Auth\VerifyPasswordResetOtpRequest;
use App\Http\Resources\UserResource;
use App\Http\Responses\ApiResponse;
use App\Models\User;
use App\Services\AuthService;
use App\Services\PasswordResetOtpService;
use App\Services\RegistrationOtpService;
use App\Services\UserProfileService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException;

class AuthController extends Controller
{
    public function __construct(
        private RegistrationOtpService $registrationOtpService,
        private PasswordResetOtpService $passwordResetOtpService,
        private UserProfileService $userProfileService,
        private AuthService $authService
    ) {}

    #[OA\Post(
        path: '/api/v1/auth/check-email',
        tags: ['Authentication'],
        summary: 'Check email and start login or registration',
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['email'],
                properties: [
                    new OA\Property(property: 'email', type: 'string', format: 'email', maxLength: 255),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'User exists (login) or OTP sent (register)'),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
    #[OA\Post(
        path: '/api/v1/auth/register/email',
        tags: ['Authentication'],
        summary: 'Alias of check-email (send registration OTP if email is new)',
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['email'],
                properties: [
                    new OA\Property(property: 'email', type: 'string', format: 'email', maxLength: 255),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'User exists (login) or OTP sent (register)'),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
    public function checkEmail(RegisterEmailRequest $request): JsonResponse
    {
        $email = $request->validated('email');

        if (User::where('email', $email)->exists()) {
            return ApiResponse::success('User found. Please login.', [
                'action' => 'login',
            ]);
        }

        $this->registrationOtpService->requestOtp($email);

        return ApiResponse::success('OTP sent to your email.', [
            'action' => 'register',
        ]);
    }

    #[OA\Post(
        path: '/api/v1/auth/register/verify-otp',
        tags: ['Authentication'],
        summary: 'Verify a registration OTP',
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['email', 'otp'],
                properties: [
                    new OA\Property(property: 'email', type: 'string', format: 'email', maxLength: 255),
                    new OA\Property(property: 'otp', type: 'string', pattern: '^[0-9]{4}$', example: '1234'),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'OTP verified'),
            new OA\Response(response: 422, description: 'Invalid OTP or validation error'),
        ]
    )]
    public function verifyOtp(VerifyOtpRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $this->registrationOtpService->verifyOtp($validated['email'], $validated['otp']);

        return ApiResponse::success('OTP verified. You can complete registration.', null);
    }

    #[OA\Post(
        path: '/api/v1/auth/register/complete',
        tags: ['Authentication'],
        summary: 'Complete registration',
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['email', 'name', 'password', 'password_confirmation'],
                properties: [
                    new OA\Property(property: 'email', type: 'string', format: 'email', maxLength: 255),
                    new OA\Property(property: 'name', type: 'string', maxLength: 255),
                    new OA\Property(property: 'password', type: 'string', format: 'password', minLength: 6),
                    new OA\Property(property: 'password_confirmation', type: 'string', format: 'password'),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 201, description: 'Registration completed'),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
    public function completeRegistration(CompleteRegistrationRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $user = $this->authService->completeRegistration(
            $validated['email'],
            $validated['name'],
            $validated['password']
        );

        $token = $user->createToken('auth')->plainTextToken;

        return ApiResponse::success('Registration completed.', [
            'user' => new UserResource($user),
            'token' => $token,
            'token_type' => 'Bearer',
        ], 201);
    }

    #[OA\Post(
        path: '/api/v1/auth/register/resend-otp',
        tags: ['Authentication'],
        summary: 'Resend a registration OTP',
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['email'],
                properties: [
                    new OA\Property(property: 'email', type: 'string', format: 'email', maxLength: 255),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'OTP resent'),
            new OA\Response(response: 429, description: 'Too many requests'),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
    public function resendOtp(ResendOtpRequest $request): JsonResponse
    {
        try {
            $this->registrationOtpService->resendOtp($request->validated('email'));
        } catch (TooManyRequestsHttpException $e) {
            return ApiResponse::error($e->getMessage(), null, 429);
        }

        return ApiResponse::success('A new OTP has been sent to your email.', null);
    }

    #[OA\Post(
        path: '/api/v1/auth/forgot-password',
        tags: ['Authentication'],
        summary: 'Send a password reset OTP',
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['email'],
                properties: [
                    new OA\Property(property: 'email', type: 'string', format: 'email', maxLength: 255),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'OTP sent'),
            new OA\Response(response: 422, description: 'Validation error or account not found'),
        ]
    )]
    public function forgotPassword(ForgotPasswordRequest $request): JsonResponse
    {
        $this->passwordResetOtpService->requestOtp($request->validated('email'));

        return ApiResponse::success('OTP sent to your email.', null);
    }

    #[OA\Post(
        path: '/api/v1/auth/forgot-password/verify-otp',
        tags: ['Authentication'],
        summary: 'Verify a password reset OTP',
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['email', 'otp'],
                properties: [
                    new OA\Property(property: 'email', type: 'string', format: 'email', maxLength: 255),
                    new OA\Property(property: 'otp', type: 'string', pattern: '^[0-9]{4}$', example: '1234'),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'OTP verified'),
            new OA\Response(response: 422, description: 'Invalid OTP or validation error'),
        ]
    )]
    public function verifyPasswordResetOtp(VerifyPasswordResetOtpRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $this->passwordResetOtpService->verifyOtp($validated['email'], $validated['otp']);

        return ApiResponse::success('OTP verified. You can reset your password.', null);
    }

    #[OA\Post(
        path: '/api/v1/auth/forgot-password/resend-otp',
        tags: ['Authentication'],
        summary: 'Resend a password reset OTP',
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['email'],
                properties: [
                    new OA\Property(property: 'email', type: 'string', format: 'email', maxLength: 255),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'OTP resent'),
            new OA\Response(response: 429, description: 'Too many requests'),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
    public function resendPasswordResetOtp(ResendPasswordResetOtpRequest $request): JsonResponse
    {
        try {
            $this->passwordResetOtpService->resendOtp($request->validated('email'));
        } catch (TooManyRequestsHttpException $e) {
            return ApiResponse::error($e->getMessage(), null, 429);
        }

        return ApiResponse::success('A new OTP has been sent to your email.', null);
    }

    #[OA\Post(
        path: '/api/v1/auth/forgot-password/reset',
        tags: ['Authentication'],
        summary: 'Reset password after OTP verification',
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['email', 'password', 'password_confirmation'],
                properties: [
                    new OA\Property(property: 'email', type: 'string', format: 'email', maxLength: 255),
                    new OA\Property(property: 'password', type: 'string', format: 'password', minLength: 6),
                    new OA\Property(property: 'password_confirmation', type: 'string', format: 'password'),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Password reset successful'),
            new OA\Response(response: 422, description: 'Validation error or unverified OTP'),
        ]
    )]
    public function resetPassword(ResetPasswordRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $this->passwordResetOtpService->resetPassword(
            $validated['email'],
            $validated['password']
        );

        return ApiResponse::success('Password reset successfully. Please login with your new password.', null);
    }

    #[OA\Post(
        path: '/api/v1/auth/login',
        tags: ['Authentication'],
        summary: 'Login with email and password',
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['email', 'password'],
                properties: [
                    new OA\Property(property: 'email', type: 'string', format: 'email', maxLength: 255),
                    new OA\Property(property: 'password', type: 'string', format: 'password'),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Login successful'),
            new OA\Response(response: 401, description: 'Invalid credentials'),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
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

    #[OA\Post(
        path: '/api/v1/auth/logout',
        tags: ['Authentication'],
        summary: 'Logout the current user',
        security: [['sanctum' => []]],
        responses: [
            new OA\Response(response: 200, description: 'Logged out'),
            new OA\Response(response: 401, description: 'Unauthenticated'),
        ]
    )]
    public function logout(Request $request): JsonResponse
    {
        $request->user()?->currentAccessToken()?->delete();

        return ApiResponse::success('Logged out successfully.', null);
    }

    #[OA\Get(
        path: '/api/v1/user/profile',
        tags: ['User Profile'],
        summary: 'Get the authenticated regular user profile',
        security: [['sanctum' => []]],
        responses: [
            new OA\Response(response: 200, description: 'Profile retrieved'),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 403, description: 'Only regular users can access this profile'),
        ]
    )]
    public function profile(Request $request): JsonResponse
    {
        $user = $request->user();

        if ($user->user_type !== User::USER_TYPE_USER) {
            return ApiResponse::error('Only regular user accounts can access this profile.', null, 403);
        }

        $user->load('profile');

        return ApiResponse::success('Profile retrieved.', [
            'user' => new UserResource($user),
        ]);
    }

    #[OA\Post(
        path: '/api/v1/user/profile',
        tags: ['User Profile'],
        summary: 'Update the authenticated regular user profile',
        security: [['sanctum' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: [
                new OA\MediaType(
                    mediaType: 'multipart/form-data',
                    schema: new OA\Schema(
                        properties: [
                            new OA\Property(property: 'gender', type: 'string', enum: ['male', 'female', 'other', 'prefer_not_to_say'], nullable: true),
                            new OA\Property(property: 'date_of_birth', type: 'string', format: 'date', nullable: true),
                            new OA\Property(property: 'phone', type: 'string', maxLength: 32, nullable: true),
                            new OA\Property(property: 'avatar', type: 'string', format: 'binary', nullable: true),
                            new OA\Property(property: 'present_address', type: 'string', nullable: true),
                            new OA\Property(property: 'permanent_address', type: 'string', nullable: true),
                            new OA\Property(property: 'district', type: 'string', nullable: true),
                            new OA\Property(property: 'country', type: 'string', minLength: 2, maxLength: 2, example: 'BD', nullable: true),
                            new OA\Property(property: 'preferred_language', type: 'string', enum: ['en', 'bn'], example: 'bn', nullable: true),
                        ]
                    )
                ),
                new OA\MediaType(
                    mediaType: 'application/json',
                    schema: new OA\Schema(
                        properties: [
                            new OA\Property(property: 'gender', type: 'string', enum: ['male', 'female', 'other', 'prefer_not_to_say'], nullable: true),
                            new OA\Property(property: 'date_of_birth', type: 'string', format: 'date', nullable: true),
                            new OA\Property(property: 'phone', type: 'string', maxLength: 32, nullable: true),
                            new OA\Property(property: 'present_address', type: 'string', nullable: true),
                            new OA\Property(property: 'permanent_address', type: 'string', nullable: true),
                            new OA\Property(property: 'district', type: 'string', nullable: true),
                            new OA\Property(property: 'country', type: 'string', minLength: 2, maxLength: 2, example: 'BD', nullable: true),
                            new OA\Property(property: 'preferred_language', type: 'string', enum: ['en', 'bn'], example: 'bn', nullable: true),
                        ]
                    )
                ),
            ]
        ),
        responses: [
            new OA\Response(response: 200, description: 'Profile updated'),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 403, description: 'Only regular users can update this profile'),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
    public function updateProfile(UpdateUserProfileRequest $request): JsonResponse
    {
        $user = $this->userProfileService->update(
            $request->user(),
            $request->safe()->except('avatar'),
            $request->file('avatar')
        );

        return ApiResponse::success('Profile updated.', [
            'user' => new UserResource($user),
        ]);
    }
}
