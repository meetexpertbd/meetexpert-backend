<?php

namespace App\Http\Controllers;

use OpenApi\Attributes as OA;

#[OA\Info(version: '1.0.0', title: 'MeetExpert API')]
#[OA\Server(url: 'http://127.0.0.1:8000', description: 'Local server')]
#[OA\SecurityScheme(
    securityScheme: 'sanctum',
    type: 'http',
    scheme: 'bearer',
    description: 'Laravel Sanctum bearer token'
)]
abstract class Controller
{
    //
}
