<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class BertSentimentService
{
    protected string $bertEndpoint;
    protected ?string $token;

    public function __construct()
    {
        $this->bertEndpoint = config('services.bert.endpoint');
        $this->token = config('services.bert.token');

        if (empty($this->bertEndpoint)) {
            throw new \RuntimeException('BERT API endpoint is not configured properly.');
        }
    }

    public function analyze(string $text): array
    {
        try {
            $request = Http::withHeaders(
                $this->token ? ['Authorization' => 'Bearer ' . $this->token] : []
            )->post($this->bertEndpoint, [
                'inputs' => $text,
            ]);

            \Log::info('BERT response status', ['status' => $request->status()]);
            \Log::info('BERT raw body', ['body' => $request->body()]);

            if ($request->failed()) {
                \Log::error('BERT API request failed', [
                    'status' => $request->status(),
                    'body' => $request->body(),
                ]);
                return $this->defaultResult();
            }

            $data = $request->json();
            \Log::debug('BERT decoded JSON:', $data);

            // تحقق من الشكل المتوقع للرد
            if (!is_array($data) || !isset($data[0]['label'], $data[0]['score'])) {
                \Log::warning('Invalid or incomplete BERT response', ['response' => $data]);
                return $this->defaultResult();
            }

            $results = collect($data);
            $positive = $results->firstWhere('label', 'POSITIVE')['score'] ?? 0;
            $negative = $results->firstWhere('label', 'NEGATIVE')['score'] ?? 0;

            $total = $positive + $negative;
            $rawRating = $total > 0 ? ($positive / $total) * 5 : 2.5;
            $roundedRating = round($rawRating * 2) / 2;

            $label = $positive > $negative ? 'positive' : ($negative > $positive ? 'negative' : 'neutral');
            $score = max($positive, $negative);

            $feedback = match ($label) {
                'positive' => 'The doctor\'s response was professional and supportive.',
                'negative' => 'The doctor\'s response may be inappropriate or unhelpful.',
                default => 'Could not analyze sentiment.',
            };

            return [
                'label' => $label,
                'score' => $score,
                'feedback' => $feedback,
                'rating' => $roundedRating,
                'result' => $data, // نحتفظ بالرد الأصلي
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
            'result' => [],
        ];
    }
}
