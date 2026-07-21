<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\StoreExpertApplicationRequest;
use App\Http\Resources\ExpertApplicationResource;
use App\Http\Responses\ApiResponse;
use App\Services\ExpertApplicationService;
use Illuminate\Http\JsonResponse;
use OpenApi\Attributes as OA;

class ExpertApplicationController extends Controller
{
    public function __construct(
        private ExpertApplicationService $expertApplicationService
    ) {}

    #[OA\Post(
        path: '/api/v1/expert/application',
        tags: ['Expert Application'],
        summary: 'Submit an expert application',
        security: [['sanctum' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['category_id', 'subcategory_id', 'professional_headline', 'bio', 'skill_ids'],
                properties: [
                    new OA\Property(property: 'category_id', type: 'integer'),
                    new OA\Property(property: 'subcategory_id', type: 'integer'),
                    new OA\Property(property: 'professional_headline', type: 'string', maxLength: 255),
                    new OA\Property(property: 'bio', type: 'string', maxLength: 10000),
                    new OA\Property(
                        property: 'education',
                        type: 'array',
                        maxItems: 20,
                        items: new OA\Items(
                            required: ['institution'],
                            properties: [
                                new OA\Property(property: 'institution', type: 'string', maxLength: 255),
                                new OA\Property(property: 'degree', type: 'string', maxLength: 255, nullable: true),
                                new OA\Property(property: 'year', type: 'integer', minimum: 1900, maximum: 2100, nullable: true),
                            ],
                            type: 'object'
                        ),
                        nullable: true
                    ),
                    new OA\Property(
                        property: 'experience',
                        type: 'array',
                        maxItems: 30,
                        items: new OA\Items(
                            required: ['title'],
                            properties: [
                                new OA\Property(property: 'title', type: 'string', maxLength: 255),
                                new OA\Property(property: 'organization', type: 'string', maxLength: 255, nullable: true),
                                new OA\Property(property: 'start_year', type: 'integer', minimum: 1900, maximum: 2100, nullable: true),
                                new OA\Property(property: 'end_year', type: 'integer', minimum: 1900, maximum: 2100, nullable: true),
                                new OA\Property(property: 'description', type: 'string', maxLength: 2000, nullable: true),
                            ],
                            type: 'object'
                        ),
                        nullable: true
                    ),
                    new OA\Property(
                        property: 'portfolio',
                        type: 'array',
                        maxItems: 20,
                        items: new OA\Items(
                            required: ['url'],
                            properties: [
                                new OA\Property(property: 'title', type: 'string', maxLength: 255, nullable: true),
                                new OA\Property(property: 'url', type: 'string', format: 'uri', maxLength: 2048),
                            ],
                            type: 'object'
                        ),
                        nullable: true
                    ),
                    new OA\Property(
                        property: 'skill_ids',
                        type: 'array',
                        minItems: 1,
                        maxItems: 50,
                        items: new OA\Items(type: 'integer')
                    ),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 201, description: 'Application submitted'),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
    public function store(StoreExpertApplicationRequest $request): JsonResponse
    {
        $application = $this->expertApplicationService->submit(
            $request->user(),
            $request->validated()
        );

        return ApiResponse::success(
            'Expert application submitted successfully. It will be reviewed by an administrator.',
            new ExpertApplicationResource($application),
            201
        );
    }
}
