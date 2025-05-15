<?php

namespace App\Services;

use GuzzleHttp\Client;
use Illuminate\Support\Facades\Log;

class FCMService
{
    protected Client $http;
    protected string $serverKey;

    public function __construct()
    {
        $this->http = new Client();
        $this->serverKey = config('services.fcm.server_key');
    }

    public function sendToUser(string $token, string $title, string $body, array $data = []): void
    {
        try {
            $response = $this->http->post('https://fcm.googleapis.com/fcm/send', [
                'headers' => [
                    'Authorization' => 'key=' . $this->serverKey,
                    'Content-Type'  => 'application/json',
                ],
                'json' => [
                    'to'   => $token,
                    'notification' => ['title' => $title, 'body'  => $body],
                    'data' => $data,
                ],
            ]);
            Log::info('FCM sendToUser', ['status' => $response->getStatusCode()]);
        } catch (\Exception $e) {
            Log::error('FCM sendToUser failed', ['error' => $e->getMessage()]);
        }
    }

    public function sendBulk(array $tokens, string $title, string $body, array $data = []): void
    {
        if (empty($tokens)) return;
        try {
            $response = $this->http->post('https://fcm.googleapis.com/fcm/send', [
                'headers' => [
                    'Authorization' => 'key=' . $this->serverKey,
                    'Content-Type'  => 'application/json',
                ],
                'json' => [
                    'registration_ids' => $tokens,
                    'notification' => ['title' => $title, 'body'  => $body],
                    'data' => $data,
                ],
            ]);
            Log::info('FCM sendBulk', ['status' => $response->getStatusCode()]);
        } catch (\Exception $e) {
            Log::error('FCM sendBulk failed', ['error' => $e->getMessage()]);
        }
    }
}
