<?php

namespace App\Http\Controllers;

use OpenApi\Attributes as OA;

#[OA\Info(
    version: '1.0.0',
    title: 'MeetExpert API',
    description: 'MeetExpertBD REST API documentation (auth, user profile, experts, bookings).'
)]
#[OA\Server(url: 'http://127.0.0.1:8000', description: 'Local API server')]
#[OA\SecurityScheme(
    securityScheme: 'sanctum',
    type: 'http',
    scheme: 'bearer',
    bearerFormat: 'Token',
    description: 'Enter Sanctum personal access token'
)]
abstract class Controller
{
    //
}
