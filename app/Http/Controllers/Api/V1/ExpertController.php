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
use OpenApi\Attributes as OA;

class ExpertController extends Controller
{
    public function __construct(
        private ExpertDiscoveryService $expertDiscoveryService,
        private ExpertBookingService $expertBookingService
    ) {}

    #[OA\Get(
        path: '/api/v1/experts',
        tags: ['Experts'],
        summary: 'List approved experts',
        parameters: [
            new OA\Parameter(name: 'category_id', in: 'query', schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'subcategory_id', in: 'query', schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'per_page', in: 'query', schema: new OA\Schema(type: 'integer', minimum: 1, maximum: 100)),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Experts retrieved'),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
    public function index(ListExpertsRequest $request): JsonResponse
    {
        $experts = $this->expertDiscoveryService->list($request->validated());

        return ApiResponse::success(
            'Experts retrieved.',
            ExpertResource::collection($experts)
        );
    }

    #[OA\Get(
        path: '/api/v1/experts/{user}',
        tags: ['Experts'],
        summary: 'Get an approved expert',
        parameters: [
            new OA\Parameter(
                name: 'user',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer')
            ),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Expert retrieved'),
            new OA\Response(response: 404, description: 'Expert not found'),
        ]
    )]
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

    #[OA\Get(
        path: '/api/v1/experts/{user}/available-slots',
        tags: ['Experts'],
        summary: 'List an expert’s available slots for a date',
        parameters: [
            new OA\Parameter(
                name: 'user',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer')
            ),
            new OA\Parameter(
                name: 'date',
                in: 'query',
                required: true,
                schema: new OA\Schema(type: 'string', format: 'date')
            ),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Available slots retrieved'),
            new OA\Response(response: 404, description: 'Expert not found'),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
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
