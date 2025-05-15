<?php

namespace App\Services;

class SentimentAnalysisService
{
    public function analyze(array $chatHistory): array
    {
        $score = 0.75;
        $label = 'positive';
        return ['score' => $score, 'label' => $label];
    }
}
