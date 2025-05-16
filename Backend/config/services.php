<?php

return [

    // ... other service configs ...

    'fcm' => [
        'server_key' => env('FCM_SERVER_KEY'),
    ],

    'ai' => [
        'chatbot_endpoint' => env('AI_CHATBOT_ENDPOINT', 'http://localhost:5000/chat'),
    ],

];
