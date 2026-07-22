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
use OpenApi\Attributes as OA;
use Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException;

class AuthController extends Controller
{
    public function __construct(
        private RegistrationOtpService $registrationOtpService,
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
        path: '/api/v1/profile',
        tags: ['Authentication'],
        summary: 'Get the authenticated user profile',
        security: [['sanctum' => []]],
        responses: [
            new OA\Response(response: 200, description: 'Profile retrieved'),
            new OA\Response(response: 401, description: 'Unauthenticated'),
        ]
    )]
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
