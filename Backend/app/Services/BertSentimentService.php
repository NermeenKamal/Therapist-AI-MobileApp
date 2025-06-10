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
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->post($this->bertEndpoint, [
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

        // تحقق إن الريسبونس عبارة عن ليست فيها label/score
        if (!is_array($data) || !isset($data[0]['label'])) {
            throw new \Exception('Invalid BERT response format');
        }

        // نستخرج درجات الإيجابي والسلبي
        $results = collect($data);
        $positive = $results->firstWhere('label', 'POSITIVE')['score'] ?? 0;
        $negative = $results->firstWhere('label', 'NEGATIVE')['score'] ?? 0;

        // Normalize rating من 1 إلى 5
        $rawRating = ($positive / ($positive + $negative)) * 5;
        $roundedRating = round($rawRating * 2) / 2;

        // نختار الأكتر
        $label = $positive > $negative ? 'positive' : 'negative';
        $score = max($positive, $negative);

        $feedback = match ($label) {
            'positive' => 'The doctor\'s response was professional and supportive.',
            'negative' => 'The doctor\'s response may be inappropriate or unhelpful.',
            default => 'The tone of the message is neutral.',
        };

        return [
            'label' => $label,
            'score' => $score,
            'feedback' => $feedback,
            'rating' => $roundedRating,
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
        'rating' => 3,
    ];
}


}
