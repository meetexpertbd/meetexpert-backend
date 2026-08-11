<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\StoreBookingReviewRequest;
use App\Http\Resources\BookingReviewResource;
use App\Http\Responses\ApiResponse;
use App\Models\ExpertBooking;
use App\Services\BookingReviewService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

class BookingReviewController extends Controller
{
    public function __construct(
        private BookingReviewService $bookingReviewService
    ) {}

    #[OA\Get(
        path: '/api/v1/bookings/{booking}/review',
        tags: ['Bookings'],
        summary: 'Get the review for a booking',
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
            new OA\Response(response: 200, description: 'Review retrieved'),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 404, description: 'Booking or review not found'),
        ]
    )]
    public function show(Request $request, ExpertBooking $booking): JsonResponse
    {
        if (! $this->canViewBooking($request, $booking)) {
            return ApiResponse::error('Booking not found.', null, 404);
        }

        $booking->loadMissing('review.user', 'review.expert');
        if ($booking->review === null) {
            return ApiResponse::error('Review not found.', null, 404);
        }

        return ApiResponse::success(
            'Review retrieved.',
            new BookingReviewResource($booking->review)
        );
    }

    #[OA\Post(
        path: '/api/v1/bookings/{booking}/review',
        tags: ['Bookings'],
        summary: 'Review a booking as the authenticated user',
        security: [['sanctum' => []]],
        parameters: [
            new OA\Parameter(
                name: 'booking',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer')
            ),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['rating'],
                properties: [
                    new OA\Property(property: 'rating', type: 'integer', minimum: 1, maximum: 5, example: 5),
                    new OA\Property(property: 'comment', type: 'string', maxLength: 2000, nullable: true),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 201, description: 'Booking reviewed'),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 404, description: 'Booking not found'),
            new OA\Response(response: 422, description: 'Validation error or booking not reviewable'),
        ]
    )]
    public function store(StoreBookingReviewRequest $request, ExpertBooking $booking): JsonResponse
    {
        if ($booking->user_id !== $request->user()->id) {
            return ApiResponse::error('Booking not found.', null, 404);
        }

        $review = $this->bookingReviewService->create(
            $request->user(),
            $booking->loadMissing('review'),
            $request->validated()
        );

        return ApiResponse::success(
            'Booking reviewed.',
            new BookingReviewResource($review),
            201
        );
    }

    #[OA\Put(
        path: '/api/v1/bookings/{booking}/review',
        tags: ['Bookings'],
        summary: 'Update the authenticated user’s review for a booking',
        security: [['sanctum' => []]],
        parameters: [
            new OA\Parameter(
                name: 'booking',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer')
            ),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['rating'],
                properties: [
                    new OA\Property(property: 'rating', type: 'integer', minimum: 1, maximum: 5, example: 4),
                    new OA\Property(property: 'comment', type: 'string', maxLength: 2000, nullable: true),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Review updated'),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 404, description: 'Booking not found'),
            new OA\Response(response: 422, description: 'Validation error or review missing'),
        ]
    )]
    public function update(StoreBookingReviewRequest $request, ExpertBooking $booking): JsonResponse
    {
        if ($booking->user_id !== $request->user()->id) {
            return ApiResponse::error('Booking not found.', null, 404);
        }

        $review = $this->bookingReviewService->update(
            $request->user(),
            $booking->loadMissing('review'),
            $request->validated()
        );

        return ApiResponse::success(
            'Review updated.',
            new BookingReviewResource($review)
        );
    }

    private function canViewBooking(Request $request, ExpertBooking $booking): bool
    {
        $user = $request->user();

        return $user !== null
            && ($booking->user_id === $user->id || $booking->expert_user_id === $user->id);
    }
}
