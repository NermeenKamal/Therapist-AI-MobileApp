<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class BertSentimentService
{
    protected string $bertEndpoint;

    public function __construct()
    {
        $this->bertEndpoint = env('BERT_ENDPOINT');

        if (is_null($this->bertEndpoint)) {
            throw new \Exception("BERT_ENDPOINT not set in .env file");
        }
    }

    public function analyze(string $text): array
    {
        try {
            $response = Http::post($this->bertEndpoint, [
                'text' => $text,
            ]);

            if ($response->failed()) {
                \Log::error('BERT API request failed', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);
                return [
                    'score' => null,
                    'label' => 'error',
                    'feedback' => null,
                ];
            }

            return $response->json();

        } catch (\Exception $e) {
            \Log::error('Exception while calling BERT API', [
                'message' => $e->getMessage(),
            ]);

            return [
                'score' => null,
                'label' => 'exception',
                'feedback' => null,
            ];
        }
    }
}
