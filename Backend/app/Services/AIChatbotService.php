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

    public function sendMessage(string $message, string $userId): array
    {
        // Generate a unique conversation ID if not exists
        $conversationId = Cache::get("user_{$userId}_conversation") ?? Str::uuid()->toString();
        Cache::put("user_{$userId}_conversation", $conversationId, now()->addHours(24));

        // Store the message in processing state
        $cacheKey = "chat_{$conversationId}_response";
        Cache::put($cacheKey, ['status' => 'processing'], now()->addMinutes(5));

        // Send request to AI service asynchronously
        Http::async()->post($this->aiEndpoint, [
            'message' => $message,
            'conversation_id' => $conversationId,
            'user_id' => $userId
        ])->then(function ($response) use ($cacheKey) {
            if ($response->successful()) {
                Cache::put($cacheKey, [
                    'status' => 'completed',
                    'response' => $response->json('response'),
                ], now()->addMinutes(30));
            } else {
                Cache::put($cacheKey, [
                    'status' => 'error',
                    'message' => 'Failed to process message'
                ], now()->addMinutes(5));
            }
        });

        return [
            'conversation_id' => $conversationId,
            'status' => 'processing'
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