<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\ContactMessageStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\StoreContactMessageRequest;
use App\Http\Resources\ContactMessageResource;
use App\Http\Responses\ApiResponse;
use App\Models\ContactMessage;
use Illuminate\Http\JsonResponse;
use OpenApi\Attributes as OA;

class ContactController extends Controller
{
    #[OA\Post(
        path: '/api/v1/contact',
        tags: ['Contact'],
        summary: 'Submit a contact us message',
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['name', 'phone', 'subject', 'message'],
                properties: [
                    new OA\Property(property: 'name', type: 'string', maxLength: 255, example: 'Mentors'),
                    new OA\Property(property: 'email', type: 'string', format: 'email', nullable: true),
                    new OA\Property(property: 'phone', type: 'string', maxLength: 32, example: '01700000000'),
                    new OA\Property(property: 'subject', type: 'string', maxLength: 255),
                    new OA\Property(property: 'message', type: 'string', maxLength: 5000),
                    new OA\Property(property: 'preferred_language', type: 'string', enum: ['bn', 'en'], nullable: true),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 201, description: 'Message submitted'),
            new OA\Response(response: 422, description: 'Validation error'),
            new OA\Response(response: 429, description: 'Too many requests'),
        ]
    )]
    public function store(StoreContactMessageRequest $request): JsonResponse
    {
        $user = $request->user('sanctum');
        $validated = $request->validated();

        $message = ContactMessage::query()->create([
            'user_id' => $user?->id,
            'name' => $validated['name'],
            'email' => $validated['email'] ?? $user?->email,
            'phone' => $validated['phone'],
            'subject' => $validated['subject'],
            'message' => $validated['message'],
            'preferred_language' => $validated['preferred_language']
                ?? $user?->profile?->preferred_language,
            'status' => ContactMessageStatus::Pending,
        ]);

        return ApiResponse::success(
            'Your message has been submitted. We will get back to you soon.',
            new ContactMessageResource($message),
            201
        );
    }
}
