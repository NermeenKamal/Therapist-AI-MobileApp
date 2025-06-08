<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class AnalyzeChatSentiment implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct()
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle()
    {
        try {
            $bertResult = app(BertSentimentService::class)->analyze($this->message);
            ChatRating::create([
                'appointment_id' => $this->appointmentId,
                'patient_id' => $this->patientId,
                'sentiment_score' => $bertResult['score'],
                'sentiment_label' => $bertResult['label'],
            ]);
        } catch (\Exception $e) {
            \Log::error('BERT analysis failed in job: ' . $e->getMessage());
        }
    }

}
