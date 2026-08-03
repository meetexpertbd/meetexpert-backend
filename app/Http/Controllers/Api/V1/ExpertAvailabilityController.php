<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\SyncExpertAvailabilityRequest;
use App\Http\Requests\Api\V1\UpdateExpertSlotPriceRequest;
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

    #[OA\Get(
        path: '/api/v1/expert/slot-price',
        tags: ['Expert Availability'],
        summary: 'Get the authenticated expert’s slot price',
        security: [['sanctum' => []]],
        responses: [
            new OA\Response(response: 200, description: 'Slot price retrieved'),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 403, description: 'Expert account required'),
            new OA\Response(response: 404, description: 'Expert profile not found'),
        ]
    )]
    public function showSlotPrice(Request $request): JsonResponse
    {
        $user = $request->user();
        if ($user->user_type !== User::USER_TYPE_EXPERT) {
            return ApiResponse::error('Only expert accounts can view slot price.', null, 403);
        }

        $detail = $user->expertDetail;
        if ($detail === null) {
            return ApiResponse::error('Expert profile not found.', null, 404);
        }

        return ApiResponse::success('Slot price retrieved.', [
            'slot_price' => $user->expertSlotPrice?->price !== null
                ? (float) $user->expertSlotPrice->price
                : null,
        ]);
    }

    #[OA\Put(
        path: '/api/v1/expert/slot-price',
        tags: ['Expert Availability'],
        summary: 'Set the authenticated expert’s slot price',
        security: [['sanctum' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['slot_price'],
                properties: [
                    new OA\Property(
                        property: 'slot_price',
                        type: 'number',
                        format: 'float',
                        minimum: 0,
                        example: 500.00
                    ),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Slot price saved'),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 403, description: 'Expert account required'),
            new OA\Response(response: 404, description: 'Expert profile not found'),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
    public function updateSlotPrice(UpdateExpertSlotPriceRequest $request): JsonResponse
    {
        $user = $request->user();
        if ($user->user_type !== User::USER_TYPE_EXPERT) {
            return ApiResponse::error('Only expert accounts can set slot price.', null, 403);
        }

        $detail = $user->expertDetail;
        if ($detail === null) {
            return ApiResponse::error('Expert profile not found.', null, 404);
        }

        $slotPrice = $user->expertSlotPrice()->updateOrCreate(
            ['user_id' => $user->id],
            ['price' => $request->validated('slot_price')]
        );

        return ApiResponse::success('Slot price saved.', [
            'slot_price' => (float) $slotPrice->price,
        ]);
    }
}
