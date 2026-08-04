<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\UpdateExpertDetailRequest;
use App\Http\Resources\ExpertDetailResource;
use App\Http\Responses\ApiResponse;
use App\Models\ExpertDetail;
use App\Models\User;
use App\Services\ExpertAvailabilityService;
use App\Services\ExpertDetailService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

class ExpertDetailController extends Controller
{
    public function __construct(
        private ExpertDetailService $expertDetailService,
        private ExpertAvailabilityService $expertAvailabilityService
    ) {}

    #[OA\Get(
        path: '/api/v1/expert/details',
        tags: ['Expert Details'],
        summary: 'Get the authenticated expert’s profile details, weekly slots, and slot price',
        security: [['sanctum' => []]],
        responses: [
            new OA\Response(response: 200, description: 'Expert details retrieved'),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 403, description: 'Expert account required'),
            new OA\Response(response: 404, description: 'Expert details not found'),
        ]
    )]
    public function show(Request $request): JsonResponse
    {
        $user = $request->user();
        if ($user->user_type !== User::USER_TYPE_EXPERT) {
            return ApiResponse::error('Only expert accounts can view expert details.', null, 403);
        }

        $detail = $user->expertDetail()->with(['category', 'subcategory', 'skills'])->first();
        if ($detail === null) {
            return ApiResponse::error('Expert details not found.', null, 404);
        }

        return ApiResponse::success(
            'Expert details retrieved.',
            $this->detailPayload($user, $detail)
        );
    }

    #[OA\Post(
        path: '/api/v1/expert/details',
        tags: ['Expert Details'],
        summary: 'Update the authenticated expert’s profile details',
        security: [['sanctum' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\MediaType(
                mediaType: 'multipart/form-data',
                schema: new OA\Schema(
                    required: [
                        'category_id',
                        'subcategory_id',
                        'professional_headline',
                        'bio',
                        'years_of_experience',
                        'registration_value',
                        'languages',
                        'skill_ids',
                    ],
                    properties: [
                        new OA\Property(property: 'category_id', type: 'integer'),
                        new OA\Property(property: 'subcategory_id', type: 'integer'),
                        new OA\Property(property: 'professional_headline', type: 'string', maxLength: 255),
                        new OA\Property(property: 'bio', type: 'string', maxLength: 10000),
                        new OA\Property(property: 'years_of_experience', type: 'integer', minimum: 0, maximum: 80),
                        new OA\Property(property: 'registration_value', type: 'string', maxLength: 255),
                        new OA\Property(
                            property: 'intro_video',
                            description: 'Video file upload or external video URL. Omit to keep current.',
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
                        new OA\Property(property: 'education', type: 'string', nullable: true),
                        new OA\Property(property: 'experience', type: 'string', nullable: true),
                        new OA\Property(property: 'portfolio', type: 'string', nullable: true),
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
            new OA\Response(response: 200, description: 'Expert details updated'),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 403, description: 'Expert account required'),
            new OA\Response(response: 404, description: 'Expert details not found'),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
    public function update(UpdateExpertDetailRequest $request): JsonResponse
    {
        $user = $request->user();
        $detail = $user->expertDetail;
        if ($detail === null) {
            return ApiResponse::error('Expert details not found.', null, 404);
        }

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
        if ($introVideoFile === null && $request->filled('intro_video')) {
            $validated['intro_video'] = $request->input('intro_video');
        }

        $detail = $this->expertDetailService->updateByExpert(
            $detail,
            $validated,
            $request->file('avatar'),
            $documents,
            $introVideoFile
        );

        return ApiResponse::success(
            'Expert details updated.',
            $this->detailPayload($user, $detail)
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function detailPayload(User $user, ExpertDetail $detail): array
    {
        $user->loadMissing('expertSlotPrice');
        $detail->loadMissing(['category', 'subcategory', 'skills']);

        return array_merge(
            (new ExpertDetailResource($detail))->resolve(),
            [
                'slot_price' => $user->expertSlotPrice?->price !== null
                    ? (float) $user->expertSlotPrice->price
                    : null,
                'days' => $this->expertAvailabilityService->getSchedule($user),
            ]
        );
    }
}
