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
            'text' => $text,
        ]);
        
        \Log::info('Raw BERT API response:', ['body' => $response->body()]);


        if ($response->failed()) {
            \Log::error('BERT API request failed', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
            return [
                'label' => 'error',
                'score' => null,
                'feedback' => null,
            ];
        }

        $data = $response->json();

        // If the response is a list (e.g., huggingface-style output)
        if (is_array($data) && isset($data[0]['label'])) {
            $labelRaw = $data[0]['label'];
            $score = $data[0]['score'];
        }
        // If it's a dict-style response
        elseif (is_array($data) && isset($data['label'], $data['score'])) {
            $labelRaw = $data['label'];
            $score = $data['score'];
        } else {
            throw new \Exception('Invalid format');
        }

        // Map label
        $label = match (strtoupper($labelRaw)) {
            'LABEL_0', 'NEGATIVE' => 'negative',
            'LABEL_1', 'POSITIVE' => 'positive',
            default => 'neutral',
        };

        // Simple feedback logic
        $feedback = match ($label) {
            'positive' => 'ممتاز! استمر في مشاركة مشاعرك الإيجابية.',
            'negative' => 'يبدو أنك تمر بمشاعر صعبة، نحن هنا لدعمك.',
            default => 'شكرًا لمشاركتك، يرجى المحاولة مجددًا إذا كنت تريد نتيجة أدق.',
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
            'feedback' => null,
        ];
    }
}

}
