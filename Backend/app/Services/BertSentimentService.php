<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class BertSentimentService
{
    protected string $bertEndpoint;

    public function __construct()
    {
        $this->bertEndpoint = config('services.ai.bert_endpoint');
    }

    /**
     * Analyze a message using the BERT model.
     * @param string $message
     * @return array ['score' => float, 'label' => string]
     */
    public function analyze(string $message): array
    {
        $response = Http::post($this->bertEndpoint, [
            'message' => $message
        ]);

        if ($response->successful()) {
            return $response->json();
        }

        return [
            'score' => null,
            'label' => 'unknown',
            'error' => 'BERT model unavailable'
        ];
    }
} 