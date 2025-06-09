<?php

namespace App\Jobs;

use App\Models\ChatRating;
use App\Services\BertSentimentService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\SerializesModels;

class AnalyzeChatSentiment implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $appointmentId;
    protected $patientId;
    protected $message;

    public function __construct($appointmentId, $patientId, $message)
    {
        $this->appointmentId = $appointmentId;
        $this->patientId = $patientId;
        $this->message = $message;
    }

    public function handle()
{
    \Log::info("Starting AnalyzeChatSentiment job", [
        'appointment_id' => $this->appointmentId,
        'patient_id' => $this->patientId,
        'message' => $this->message,
    ]);

    try {
        $bertService = new BertSentimentService();
        $bertResult = $bertService->analyze($this->message);

        \Log::info("BERT service returned", $bertResult);

        $score = $bertResult['score'] ?? null;
        $label = $bertResult['label'] ?? 'unknown';

        if (is_null($score)) {
            \Log::warning("Empty or invalid score from BERT service", $bertResult);
            return;
        }

        ChatRating::create([
            'appointment_id'   => $this->appointmentId,
            'patient_id'       => $this->patientId,
            'rating'           => round($score * 5),
            'feedback'         => $bertResult['feedback'] ?? null,
            'sentiment_score'  => $score,
            'sentiment_label'  => $label,
        ]);

        \Log::info("ChatRating created successfully");

    } catch (\Exception $e) {
        \Log::error('AnalyzeChatSentiment job failed', [
            'message' => $e->getMessage(),
            'trace' => $e->getTraceAsString(),
        ]);
    }
}

}
