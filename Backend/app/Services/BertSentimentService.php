<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class BertSentimentService
{
    public string $bertEndpoint;

    public function __construct()
    {
        $this->bertEndpoint = config('services.bert.endpoint');
    }

    /**
     * Analyze a message using the BERT model.
     * @param string $message
     * @return array ['score' => float, 'label' => string]
     */
    public function analyze(string $message): array
{
    $response = Http::post($this->bertEndpoint . '/analyze', [
        'text' => $message
    ]);

    if ($response->successful()) {
        $data = $response->json();

        return [
            'score' => $data['score'] ?? null,
            'label' => $data['label'] ?? 'unknown',
            'feedback' => $data['feedback'] ?? null,
        ];
    }

    return [
        'score' => null,
        'label' => 'unknown',
        'feedback' => null,
        'error' => 'BERT model unavailable'
    ];
}

} 
