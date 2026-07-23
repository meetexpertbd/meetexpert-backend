<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Media / Upload Disk
    |--------------------------------------------------------------------------
    |
    | Disk used for public uploads (avatars, documents, etc.).
    | Use any disk from config/filesystems.php (public, s3, ...).
    |
    */

    'disk' => env('MEDIA_DISK', 'public'),

];
