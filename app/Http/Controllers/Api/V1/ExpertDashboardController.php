<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\BookingReviewResource;
use App\Http\Resources\ExpertBookingResource;
use App\Http\Responses\ApiResponse;
use App\Models\User;
use App\Services\ExpertDashboardService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

class ExpertDashboardController extends Controller
{
    public function __construct(
        private ExpertDashboardService $expertDashboardService
    ) {}

    #[OA\Get(
        path: '/api/v1/expert/dashboard',
        tags: ['Expert Dashboard'],
        summary: 'Get dashboard stats for the authenticated expert',
        security: [['sanctum' => []]],
        responses: [
            new OA\Response(response: 200, description: 'Dashboard retrieved'),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 403, description: 'Expert account required'),
            new OA\Response(response: 404, description: 'Expert details not found'),
        ]
    )]
    public function show(Request $request): JsonResponse
    {
        $user = $request->user();
        if ($user->user_type !== User::USER_TYPE_EXPERT) {
            return ApiResponse::error('Only expert accounts can view the dashboard.', null, 403);
        }

        if ($user->expertDetail === null) {
            return ApiResponse::error('Expert details not found.', null, 404);
        }

        $dashboard = $this->expertDashboardService->forExpert($user);

        return ApiResponse::success('Dashboard retrieved.', [
            'profile' => $dashboard['profile'],
            'stats' => $dashboard['stats'],
            'upcoming_bookings' => ExpertBookingResource::collection($dashboard['upcoming_bookings']),
            'recent_reviews' => BookingReviewResource::collection($dashboard['recent_reviews']),
        ]);
    }
}
