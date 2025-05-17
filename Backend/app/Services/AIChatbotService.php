<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class AIChatbotService
{
    protected string $aiEndpoint;
    
    public function __construct()
    {
        $this->aiEndpoint = config('services.ai.chatbot_endpoint');
    }

    public function sendMessage(string $message, ?string $userId = null, array $chatHistory = []): array
    {
        $response = Http::post($this->aiEndpoint . '/chat', [
            'message' => $message,
            'chat_history' => $chatHistory
        ]);

        if ($response->successful()) {
            return $response->json();
        }

        return [
            'response' => null,
            'error' => 'Chatbot model unavailable'
        ];
    }

    public function getResponse(string $conversationId): array
    {
        $cacheKey = "chat_{$conversationId}_response";
        $response = Cache::get($cacheKey);

        if (!$response) {
            return [
                'status' => 'error',
                'message' => 'Response not found'
            ];
        }

        return $response;
    }
} 
