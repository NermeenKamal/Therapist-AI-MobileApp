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
        try {
            $bertService = new BertSentimentService();
            $bertResult = $bertService->analyze($this->message);

            if ($bertResult) {
                ChatRating::create([
                    'appointment_id'   => $this->appointmentId,
                    'patient_id'       => $this->patientId,
                    'rating'           => round($bertResult['score'] * 5), // لو كان بين 0 و 1
                    'feedback'         => $bertResult['feedback'] ?? null,
                    'sentiment_score'  => $bertResult['score'],
                    'sentiment_label'  => $bertResult['label'],
                ]);
            }
        } catch (\Exception $e) {
            \Log::error('AnalyzeChatSentiment Job failed', ['message' => $e->getMessage()]);
        }
    }
}
