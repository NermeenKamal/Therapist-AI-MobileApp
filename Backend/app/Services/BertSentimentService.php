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

            return $this->defaultResult();
        }

        $data = $response->json();

        if (!is_array($data)) {
            throw new \Exception('Invalid response from BERT');
        }

        // Huggingface-style list
        if (isset($data[0]['label'])) {
            $labelRaw = $data[0]['label'];
            $score = $data[0]['score'];
        }
        // Dict-style
        elseif (isset($data['label'], $data['score'])) {
            $labelRaw = $data['label'];
            $score = $data['score'];
        } else {
            throw new \Exception('Unexpected BERT response format');
        }

        $label = match (strtoupper($labelRaw)) {
            'LABEL_0', 'NEGATIVE' => 'negative',
            'LABEL_1', 'POSITIVE' => 'positive',
            default => 'neutral',
        };

        $feedback = match ($label) {
            'positive' => 'The doctor\'s response was professional and supportive.',
            'negative' => 'The doctor\'s response may be inappropriate or unhelpful.',
            default => 'The tone of the message is neutral. Consider being more clear or supportive.',
        };

        return [
            'label' => $label,
            'score' => $score,
            'feedback' => $feedback,
        ];

    } catch (\Exception $e) {
        \Log::error('Exception during sentiment analysis', [
            'error' => $e->getMessage(),
        ]);

        return $this->defaultResult();
    }
}

protected function defaultResult(): array
{
    return [
        'label' => 'neutral',
        'score' => null,
        'feedback' => 'Could not analyze sentiment.',
    ];
}

}
