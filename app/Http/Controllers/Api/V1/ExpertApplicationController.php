<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\StoreExpertApplicationRequest;
use App\Http\Resources\ExpertApplicationResource;
use App\Http\Responses\ApiResponse;
use App\Services\ExpertApplicationService;
use Illuminate\Http\JsonResponse;

class ExpertApplicationController extends Controller
{
    public function __construct(
        private ExpertApplicationService $expertApplicationService
    ) {}

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
