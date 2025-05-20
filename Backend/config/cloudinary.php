<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Cloudinary Configuration
    |--------------------------------------------------------------------------
    |
    | This is the configuration for Cloudinary integration.
    |
    */
    'cloud_name' => env('CLOUDINARY_CLOUD_NAME', ''),
    'api_key' => env('CLOUDINARY_API_KEY', ''),
    'api_secret' => env('CLOUDINARY_API_SECRET', ''),
    'url' => [
        'secure' => true
    ],
    'logging' => [
        'enabled' => env('CLOUDINARY_LOGGING', true),
        'level' => 'debug'
    ]
];
