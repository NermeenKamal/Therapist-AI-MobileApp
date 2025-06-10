<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class BertSentimentService
{
    protected string $bertEndpoint;

    public function __construct()
    {
        $this->bertEndpoint = config('services.bert.endpoint');

        if (empty($this->bertEndpoint)) {
            throw new \RuntimeException('BERT API endpoint is not configured properly.');
        }
    }

    public function analyze(string $text): array
    {
        try {
            $response = Http::post($this->bertEndpoint, [
                'inputs' => $text,
            ]);

            \Log::info('BERT Response Status', ['status' => $response->status()]);
            \Log::info('BERT Raw Body', ['body' => $response->body()]);

            if ($response->failed()) {
                \Log::error('BERT API request failed', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);
                return [
                    'label' => 'error',
                    'score' => null,
                    'feedback' => 'Could not analyze sentiment.',
                ];
            }

            $data = $response->json();

            if (is_array($data) && isset($data[0]['label'])) {
                $labelRaw = $data[0]['label'];
                $score = $data[0]['score'];
            } elseif (is_array($data) && isset($data['label'], $data['score'])) {
                $labelRaw = $data['label'];
                $score = $data['score'];
            } else {
                throw new \Exception('Invalid format');
            }

            $label = match (strtoupper($labelRaw)) {
                'LABEL_0', 'NEGATIVE' => 'negative',
                'LABEL_1', 'POSITIVE' => 'positive',
                default => 'neutral',
            };

            $feedback = match ($label) {
                'positive' => 'This doctor seems helpful and supportive.',
                'negative' => 'This doctor might not be appropriate for you.',
                default => 'Thanks for your message. Further analysis might be needed.',
            };

            return [
                'label' => $label,
                'score' => $score,
                'feedback' => $feedback,
            ];

        } catch (\Exception $e) {
            \Log::error('Exception while calling BERT API', [
                'message' => $e->getMessage(),
            ]);

            return [
                'label' => 'error',
                'score' => null,
                'feedback' => 'Could not analyze sentiment.',
            ];
        }
    }
}
