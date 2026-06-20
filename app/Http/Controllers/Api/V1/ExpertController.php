<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\ListExpertAvailableSlotsRequest;
use App\Http\Requests\Api\V1\ListExpertsRequest;
use App\Http\Resources\ExpertResource;
use App\Http\Responses\ApiResponse;
use App\Models\User;
use App\Services\ExpertBookingService;
use App\Services\ExpertDiscoveryService;
use Illuminate\Http\JsonResponse;

class ExpertController extends Controller
{
    public function __construct(
        private ExpertDiscoveryService $expertDiscoveryService,
        private ExpertBookingService $expertBookingService
    ) {}

    public function index(ListExpertsRequest $request): JsonResponse
    {
        $experts = $this->expertDiscoveryService->list($request->validated());

        return ApiResponse::success(
            'Experts retrieved.',
            ExpertResource::collection($experts)
        );
    }

    public function show(User $user): JsonResponse
    {
        $expert = $this->expertDiscoveryService->findPublicExpert($user);

        if ($expert === null) {
            return ApiResponse::error('Expert not found.', null, 404);
        }

        return ApiResponse::success(
            'Expert retrieved.',
            new ExpertResource($expert)
        );
    }

    public function availableSlots(ListExpertAvailableSlotsRequest $request, User $user): JsonResponse
    {
        $expert = $this->expertDiscoveryService->findPublicExpert($user);

        if ($expert === null) {
            return ApiResponse::error('Expert not found.', null, 404);
        }

        $slots = $this->expertBookingService->availableSlotsForDate(
            $expert,
            $request->validated('date')
        );

        return ApiResponse::success('Available slots retrieved.', [
            'date' => $request->validated('date'),
            'slots' => $slots,
        ]);
    }
}
