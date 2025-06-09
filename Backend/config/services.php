<?php

return [

    // ... other service configs ...

    'fcm' => [
        'server_key' => env('FCM_SERVER_KEY'),
    ],
    
    'bert' => [
    'endpoint' => env('BERT_API_ENDPOINT'),
    ],


    'ai' => [
        'chatbot_endpoint' => env('AI_CHATBOT_ENDPOINT', 'https://cozy-renewal-production.up.railway.app/'),
        'bert_endpoint' => env('BERT_SENTIMENT_ENDPOINT', 'https://bert-model-production.up.railway.app/'),
    ],

    'mailgun' => [
        'domain' => env('MAILGUN_DOMAIN'),
        'secret' => env('MAILGUN_SECRET'),
        'endpoint' => env('MAILGUN_ENDPOINT', 'api.mailgun.net'),
        'scheme' => 'https',
    ],

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'sendgrid' => [
        'key' => env('SENDGRID_API_KEY'),
    ],

     'cloudinary' => [
        'cloud_name' => env('CLOUDINARY_CLOUD_NAME'),
        'api_key' => env('CLOUDINARY_API_KEY'),
        'api_secret' => env('CLOUDINARY_API_SECRET'),
    ],

];
