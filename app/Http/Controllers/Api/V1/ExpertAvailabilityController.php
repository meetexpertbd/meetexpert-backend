<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\SyncExpertAvailabilityRequest;
use App\Http\Responses\ApiResponse;
use App\Models\User;
use App\Services\ExpertAvailabilityService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

class ExpertAvailabilityController extends Controller
{
    public function __construct(
        private ExpertAvailabilityService $expertAvailabilityService
    ) {}

    #[OA\Get(
        path: '/api/v1/expert/availability',
        tags: ['Expert Availability'],
        summary: 'Get the authenticated expert’s weekly availability',
        security: [['sanctum' => []]],
        responses: [
            new OA\Response(response: 200, description: 'Availability retrieved'),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 403, description: 'Expert account required'),
        ]
    )]
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

    #[OA\Put(
        path: '/api/v1/expert/availability',
        tags: ['Expert Availability'],
        summary: 'Replace the authenticated expert’s weekly availability',
        security: [['sanctum' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['days'],
                properties: [
                    new OA\Property(
                        property: 'days',
                        type: 'array',
                        minItems: 7,
                        maxItems: 7,
                        items: new OA\Items(
                            required: ['day_of_week', 'enabled'],
                            properties: [
                                new OA\Property(property: 'day_of_week', type: 'integer', minimum: 0, maximum: 6),
                                new OA\Property(property: 'enabled', type: 'boolean'),
                                new OA\Property(
                                    property: 'slots',
                                    type: 'array',
                                    maxItems: 20,
                                    items: new OA\Items(
                                        required: ['start', 'end'],
                                        properties: [
                                            new OA\Property(property: 'start', type: 'string', format: 'time', example: '09:00'),
                                            new OA\Property(property: 'end', type: 'string', format: 'time', example: '10:00'),
                                        ],
                                        type: 'object'
                                    ),
                                    nullable: true
                                ),
                            ],
                            type: 'object'
                        )
                    ),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Availability saved'),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 403, description: 'Expert account required'),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
    public function update(SyncExpertAvailabilityRequest $request): JsonResponse
    {
        $user = $request->user();
        $this->expertAvailabilityService->syncSchedule($user, $request->validated('days'));

        return ApiResponse::success('Availability schedule saved.', [
            'days' => $this->expertAvailabilityService->getSchedule($user),
        ]);
    }
}
