<?php

return [

    // ... other service configs ...

    'fcm' => [
        'server_key' => env('FCM_SERVER_KEY'),
    ],

    'ai' => [
        'chatbot_endpoint' => env('AI_CHATBOT_ENDPOINT', 'https://cozy-renewal-production.up.railway.app/'),
        'bert_endpoint' => env('BERT_SENTIMENT_ENDPOINT', 'https://therapist-ai-mobileapp-production.up.railway.app/'),
    ],

];
