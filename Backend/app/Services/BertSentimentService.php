<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class BertSentimentService
{
    protected string $bertEndpoint;
    protected string $token;

    public function __construct()
    {
        $this->bertEndpoint = config('services.bert.endpoint');
        $this->token = config('services.bert.token');

        \Log::info('Initializing BertSentimentService', [
            'endpoint' => $this->bertEndpoint,
            'token_present' => !empty($this->token)
        ]);

        if (empty($this->bertEndpoint)) {
            throw new \RuntimeException('BERT API endpoint is not configured properly.');
        }

        if (empty($this->token)) {
            throw new \RuntimeException('Hugging Face token (HF_TOKEN) is missing.');
        }
    }

    public function analyze(string $text): array
    {
        try {
            \Log::info('Sending request to BERT API', ['text' => $text]);

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->token,
            ])->post($this->bertEndpoint, [
                'inputs' => $text,
            ]);

            \Log::info('BERT API Response', [
                'status' => $response->status(),
                'body' => $response->body()
            ]);

            if ($response->failed()) {
                \Log::error('BERT API request failed', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);
                return $this->defaultResult('API request failed');
            }

            $data = $response->json();

            if (!is_array($data) || !isset($data[0]['label'])) {
                \Log::warning('Unexpected BERT response format', ['data' => $data]);
                return $this->defaultResult('Unexpected response format');
            }

            $results = collect($data);
            $positive = $results->firstWhere('label', 'POSITIVE')['score'] ?? 0;
            $negative = $results->firstWhere('label', 'NEGATIVE')['score'] ?? 0;

            $total = $positive + $negative;
            $rawRating = $total > 0 ? ($positive / $total) * 5 : 2.5;
            $roundedRating = round($rawRating * 2) / 2;

            $label = $positive > $negative ? 'positive' : 'negative';
            $score = max($positive, $negative);

            $feedback = match ($label) {
                'positive' => 'The doctor\'s response was professional and supportive.',
                'negative' => 'The doctor\'s response may be inappropriate or unhelpful.',
                default    => 'The tone of the message is neutral.',
            };

            return [
                'label' => $label,
                'score' => $score,
                'feedback' => $feedback,
                'rating' => $roundedRating,
                'raw_result' => $data,
            ];
        } catch (\Exception $e) {
            \Log::error('Exception during sentiment analysis', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return $this->defaultResult($e->getMessage());
        }
    }

    protected function defaultResult(string $reason = 'Unknown error'): array
    {
        return [
            'label' => 'neutral',
            'score' => null,
            'feedback' => 'Could not analyze sentiment.',
            'rating' => 3,
            'raw_result' => ['error_reason' => $reason]
        ];
    }
}
