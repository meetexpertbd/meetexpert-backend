<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\StoreExpertBookingRequest;
use App\Http\Resources\ExpertBookingResource;
use App\Http\Responses\ApiResponse;
use App\Models\ExpertBooking;
use App\Services\AgoraMeetingService;
use App\Services\ExpertBookingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

class ExpertBookingController extends Controller
{
    public function __construct(
        private ExpertBookingService $expertBookingService,
        private AgoraMeetingService $agoraMeetingService
    ) {}

    #[OA\Get(
        path: '/api/v1/bookings',
        tags: ['Bookings'],
        summary: 'List the authenticated user’s bookings',
        security: [['sanctum' => []]],
        parameters: [
            new OA\Parameter(
                name: 'per_page',
                in: 'query',
                schema: new OA\Schema(type: 'integer', default: 20, minimum: 1, maximum: 100)
            ),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Bookings retrieved'),
            new OA\Response(response: 401, description: 'Unauthenticated'),
        ]
    )]
    public function index(Request $request): JsonResponse
    {
        $bookings = ExpertBooking::query()
            ->where('user_id', $request->user()->id)
            ->with('expert')
            ->orderByDesc('scheduled_date')
            ->orderBy('start_time')
            ->paginate(min((int) $request->input('per_page', 20), 100));

        return ApiResponse::success(
            'Bookings retrieved.',
            ExpertBookingResource::collection($bookings)
        );
    }

    #[OA\Post(
        path: '/api/v1/bookings',
        tags: ['Bookings'],
        summary: 'Book an expert',
        security: [['sanctum' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['expert_id', 'availability_slot_id', 'date'],
                properties: [
                    new OA\Property(property: 'expert_id', type: 'integer'),
                    new OA\Property(property: 'availability_slot_id', type: 'integer'),
                    new OA\Property(property: 'date', type: 'string', format: 'date'),
                    new OA\Property(property: 'notes', type: 'string', maxLength: 2000, nullable: true),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 201, description: 'Expert booked'),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 403, description: 'Account cannot create bookings'),
            new OA\Response(response: 422, description: 'Validation error or unavailable slot'),
        ]
    )]
    public function store(StoreExpertBookingRequest $request): JsonResponse
    {
        $booking = $this->expertBookingService->create(
            $request->user(),
            $request->validated()
        );

        return ApiResponse::success(
            'Expert booked successfully.',
            new ExpertBookingResource($booking),
            201
        );
    }

    #[OA\Get(
        path: '/api/v1/bookings/{booking}',
        tags: ['Bookings'],
        summary: 'Get a booking',
        security: [['sanctum' => []]],
        parameters: [
            new OA\Parameter(
                name: 'booking',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer')
            ),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Booking retrieved'),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 404, description: 'Booking not found'),
        ]
    )]
    public function show(Request $request, ExpertBooking $booking): JsonResponse
    {
        if ($booking->user_id !== $request->user()->id && $booking->expert_user_id !== $request->user()->id) {
            return ApiResponse::error('Booking not found.', null, 404);
        }

        $booking->load('expert');

        return ApiResponse::success(
            'Booking retrieved.',
            new ExpertBookingResource($booking)
        );
    }

    #[OA\Post(
        path: '/api/v1/bookings/{booking}/cancel',
        tags: ['Bookings'],
        summary: 'Cancel a booking',
        security: [['sanctum' => []]],
        parameters: [
            new OA\Parameter(
                name: 'booking',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer')
            ),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Booking cancelled'),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 404, description: 'Booking not found'),
            new OA\Response(response: 422, description: 'Booking cannot be cancelled'),
        ]
    )]
    public function cancel(Request $request, ExpertBooking $booking): JsonResponse
    {
        $booking = $this->expertBookingService->cancel($request->user(), $booking);

        return ApiResponse::success(
            'Booking cancelled.',
            new ExpertBookingResource($booking)
        );
    }

    #[OA\Get(
        path: '/api/v1/bookings/{booking}/meeting',
        tags: ['Bookings'],
        summary: 'Get Agora meeting credentials',
        security: [['sanctum' => []]],
        parameters: [
            new OA\Parameter(
                name: 'booking',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer')
            ),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Meeting credentials generated'),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 404, description: 'Booking not found'),
            new OA\Response(response: 422, description: 'Meeting is unavailable'),
        ]
    )]
    public function meeting(Request $request, ExpertBooking $booking): JsonResponse
    {
        $credentials = $this->agoraMeetingService->credentialsFor($request->user(), $booking);

        return ApiResponse::success('Meeting credentials generated.', $credentials);
    }
}
