<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\SyncExpertAvailabilityRequest;
use App\Http\Responses\ApiResponse;
use App\Models\User;
use App\Services\ExpertAvailabilityService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ExpertAvailabilityController extends Controller
{
    public function __construct(
        private ExpertAvailabilityService $expertAvailabilityService
    ) {}

    public function show(Request $request): JsonResponse
    {
        $user = $request->user();
        if ($user->user_type !== User::USER_TYPE_EXPERT) {
            return ApiResponse::error('Only expert accounts can view availability.', null, 403);
        }

        $days = $this->expertAvailabilityService->getSchedule($user);

        return ApiResponse::success('Availability schedule retrieved.', [
            'days' => $days,
        ]);
    }

    public function update(SyncExpertAvailabilityRequest $request): JsonResponse
    {
        $user = $request->user();
        $this->expertAvailabilityService->syncSchedule($user, $request->validated('days'));

        return ApiResponse::success('Availability schedule saved.', [
            'days' => $this->expertAvailabilityService->getSchedule($user),
        ]);
    }
}
