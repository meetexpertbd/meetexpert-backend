<?php

return [

    'expiry_minutes' => (int) env('OTP_EXPIRY_MINUTES', 1),

    'registration_complete_ttl_minutes' => (int) env('OTP_REGISTRATION_COMPLETE_TTL_MINUTES', 30),

    'resend' => [
        'max_attempts' => (int) env('OTP_RESEND_MAX_ATTEMPTS', 5),
        'decay_minutes' => (int) env('OTP_RESEND_DECAY_MINUTES', 60),
    ],

];
