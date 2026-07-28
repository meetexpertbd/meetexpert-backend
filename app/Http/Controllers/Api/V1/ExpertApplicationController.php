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
            content: new OA\MediaType(
                mediaType: 'multipart/form-data',
                schema: new OA\Schema(
                    required: ['category_id', 'subcategory_id', 'professional_headline', 'bio', 'years_of_experience', 'registration_value', 'languages', 'skill_ids'],
                    properties: [
                        new OA\Property(property: 'category_id', type: 'integer'),
                        new OA\Property(property: 'subcategory_id', type: 'integer'),
                        new OA\Property(property: 'professional_headline', type: 'string', maxLength: 255),
                        new OA\Property(property: 'bio', type: 'string', maxLength: 10000),
                        new OA\Property(property: 'years_of_experience', type: 'integer', minimum: 0, maximum: 80),
                        new OA\Property(property: 'registration_value', type: 'string', maxLength: 255),
                        new OA\Property(
                            property: 'intro_video',
                            description: 'Either a video file upload or an external video URL',
                            nullable: true,
                            oneOf: [
                                new OA\Schema(type: 'string', format: 'binary'),
                                new OA\Schema(type: 'string', format: 'uri', maxLength: 2048),
                            ]
                        ),
                        new OA\Property(
                            property: 'languages',
                            description: 'JSON array string or repeated form fields',
                            type: 'string',
                            example: '["en","bn"]'
                        ),
                        new OA\Property(property: 'avatar', type: 'string', format: 'binary', nullable: true),
                        new OA\Property(
                            property: 'documents',
                            type: 'array',
                            maxItems: 10,
                            items: new OA\Items(
                                required: ['name', 'file'],
                                properties: [
                                    new OA\Property(property: 'name', type: 'string', maxLength: 255),
                                    new OA\Property(property: 'file', type: 'string', format: 'binary'),
                                ],
                                type: 'object'
                            ),
                            nullable: true
                        ),
                        new OA\Property(
                            property: 'education',
                            description: 'JSON array string',
                            type: 'string',
                            nullable: true
                        ),
                        new OA\Property(
                            property: 'experience',
                            description: 'JSON array string',
                            type: 'string',
                            nullable: true
                        ),
                        new OA\Property(
                            property: 'portfolio',
                            description: 'JSON array string',
                            type: 'string',
                            nullable: true
                        ),
                        new OA\Property(
                            property: 'skill_ids',
                            description: 'JSON array string or repeated form fields',
                            type: 'string',
                            example: '[1,2,3]'
                        ),
                    ]
                )
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
        $validated = $request->safe()->except(['avatar', 'documents', 'intro_video']);
        $documents = [];

        foreach ($request->input('documents', []) as $index => $document) {
            $file = $request->file("documents.{$index}.file");
            if ($file === null) {
                continue;
            }

            $documents[] = [
                'name' => $document['name'] ?? '',
                'file' => $file,
            ];
        }

        $introVideoFile = $request->hasFile('intro_video') ? $request->file('intro_video') : null;
        if ($introVideoFile === null && $request->exists('intro_video')) {
            $validated['intro_video'] = $request->input('intro_video');
        }

        $application = $this->expertApplicationService->submit(
            $request->user(),
            $validated,
            $request->file('avatar'),
            $documents,
            $introVideoFile
        );

        return ApiResponse::success(
            'Expert application submitted successfully. It will be reviewed by an administrator.',
            new ExpertApplicationResource($application),
            201
        );
    }
}
