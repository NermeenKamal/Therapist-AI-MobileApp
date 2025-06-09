<?php

namespace App\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Jobs\AnalyzeChatSentiment;
use Illuminate\Support\Facades\Log;

class SentimentAnalysisController extends Controller
{
    public function analyze(Request $request): JsonResponse
{
    $request->validate([
        'appointment_id' => 'required|integer',
        'patient_id' => 'required|integer',
        'message' => 'required|string',
    ]);

    try {
        AnalyzeChatSentiment::dispatch(
            $request->appointment_id,
            $request->patient_id,
            $request->message
        );

        Log::info("Job dispatched", [
            'appointment_id' => $request->appointment_id,
            'patient_id' => $request->patient_id,
            'message' => $request->message,
        ]);

        return response()->json([
            'status' => 'queued',
            'message' => 'Chat message sent for sentiment analysis.',
        ]);
    } catch (\Exception $e) {
        Log::error('Failed to dispatch job', ['error' => $e->getMessage()]);
        return response()->json([
            'status' => 'error',
            'message' => 'Failed to queue job: ' . $e->getMessage(),
        ], 500);
    }
}


}
