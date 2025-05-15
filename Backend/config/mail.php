<?php

return [
    'default' => env('MAIL_MAILER', 'log'),

    'mailers' => [
        'log' => [
        'transport' => 'log',
    ],
        
        // إبقاء الإعدادات الأخرى كما هي
        'ses' => [
            'transport' => 'ses',
        ],
        
        'postmark' => [
            'transport' => 'postmark',
        ],
        
        'log' => [
            'transport' => 'log',
            'channel' => env('MAIL_LOG_CHANNEL'),
        ],
        
        'array' => [
            'transport' => 'array',
        ],
        
        'failover' => [
            'transport' => 'failover',
            'mailers' => [
                'smtp',
                'log',
            ],
        ],
    ],

    'from' => [
        'address' => env('MAIL_FROM_ADDRESS', 'hello@example.com'),
        'name' => env('MAIL_FROM_NAME', 'Example'),
    ],
];
