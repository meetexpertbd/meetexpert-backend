<?php

return [
    'app_id' => env('AGORA_APP_ID'),
    'app_certificate' => env('AGORA_APP_CERTIFICATE'),
    'join_early_minutes' => (int) env('AGORA_JOIN_EARLY_MINUTES', 15),
    'join_late_minutes' => (int) env('AGORA_JOIN_LATE_MINUTES', 15),
    'token_ttl_seconds' => (int) env('AGORA_TOKEN_TTL_SECONDS', 3600),
];
