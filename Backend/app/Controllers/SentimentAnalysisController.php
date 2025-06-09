<?php

namespace App\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Jobs\AnalyzeChatSentiment;

class SentimentAnalysisController extends Controller
{
    public function analyze(Request $request): JsonResponse
    {
        $request->validate([
            'appointment_id' => 'required|integer',
            'patient_id' => 'required|integer',
            'message' => 'required|string',
        ]);

        // إرسال Job للعمل في الخلفية
        AnalyzeChatSentiment::dispatch(
            $request->appointment_id,
            $request->patient_id,
            $request->message
        );

        return response()->json([
            'status' => 'queued',
            'message' => 'Chat message sent for sentiment analysis.'
        ]);
    }
}
