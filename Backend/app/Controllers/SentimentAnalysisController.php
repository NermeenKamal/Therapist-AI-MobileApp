<?php

namespace App\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Jobs\AnalyzeChatSentiment;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\App;

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

            $debugInfo = [
                'dispatched' => true,
                'appointment_id' => $request->appointment_id,
                'patient_id' => $request->patient_id,
                'message' => $request->message,
                'env' => App::environment(), // dev or production
                'queue' => config('queue.default'),
            ];

            Log::info("Job dispatched", $debugInfo);

            return response()->json([
                'status' => 'queued',
                'message' => 'Chat message sent for sentiment analysis.',
                'debug' => $debugInfo // ❗هنا بتحطي التتبع داخل الـ response
            ]);
        } catch (\Exception $e) {
            $error = [
                'status' => 'error',
                'message' => 'Failed to queue job: ' . $e->getMessage(),
                'trace' => $e->getTraceAsString(), // ❗اختياري لو بتعملي ديباج بعمق
            ];

            Log::error('Failed to dispatch job', $error);

            return response()->json($error, 500);
        }
    }
}
