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

class ExpertBookingController extends Controller
{
    public function __construct(
        private ExpertBookingService $expertBookingService,
        private AgoraMeetingService $agoraMeetingService
    ) {}

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

    public function cancel(Request $request, ExpertBooking $booking): JsonResponse
    {
        $booking = $this->expertBookingService->cancel($request->user(), $booking);

        return ApiResponse::success(
            'Booking cancelled.',
            new ExpertBookingResource($booking)
        );
    }

    public function meeting(Request $request, ExpertBooking $booking): JsonResponse
    {
        $credentials = $this->agoraMeetingService->credentialsFor($request->user(), $booking);

        return ApiResponse::success('Meeting credentials generated.', $credentials);
    }
}
