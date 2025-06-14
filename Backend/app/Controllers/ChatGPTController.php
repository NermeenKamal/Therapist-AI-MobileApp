<?php

namespace App\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ChatGPTController extends Controller
{
    public function sendMessage(Request $request)
    {
        $userMessage = $request->input('message');
        $debugLog = [];

        $debugLog[] = "Received user message: $userMessage";

        $useGradio = true;

        if ($useGradio) {
            return $this->sendToGradio($userMessage, $debugLog);
        } else {
            return $this->sendToGemini($userMessage, $debugLog);
        }
    }

    public function sendToGradio($message)
{
    try {
        $postResponse = Http::withHeaders([
            'Content-Type' => 'application/json'
        ])->post('https://nermeenkamal888-therapy.hf.space/gradio_api/call/predict', [
            'data' => [$message]
        ]);

        // رجعي الرد كامل بدون أي فلترة للتأكد من محتوى الرد
        return response()->json([
            'status' => $postResponse->status(),
            'body' => $postResponse->body()
        ]);

    } catch (\Exception $e) {
        return response()->json([
            'error' => 'Exception في الاتصال بـ Gradio.',
            'details' => $e->getMessage()
        ], 500);
    }
}

    private function sendToGemini($message, &$debugLog)
    {
        try {
            $debugLog[] = "Sending to Gemini...";
            Log::info("Sending message to Gemini", ['message' => $message]);

            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
            ])->timeout(60)->post(
                'https://generativelanguage.googleapis.com/v1beta/models/gemini-2.0-flash:generateContent?key=' . env('GEMINI_API_KEY'),
                [
                    'contents' => [
                        [
                            'parts' => [
                                ['text' => $message]
                            ]
                        ]
                    ]
                ]
            );

            if ($response->failed()) {
                $debugLog[] = "Gemini API failed. Response: " . json_encode($response->json());
                Log::error("Gemini API failed", ['response' => $response->json()]);
                return response()->json([
                    'error' => 'فشل في الاتصال بـ Gemini API.',
                    'details' => $response->json(),
                    'log' => $debugLog
                ], 500);
            }

            $result = $response->json()['candidates'][0]['content']['parts'][0]['text'] ?? 'لا يوجد رد';
            $debugLog[] = "Gemini returned: $result";

            return response()->json([
                'response' => $result,
                'log' => $debugLog
            ]);
        } catch (\Exception $e) {
            $debugLog[] = "Exception: " . $e->getMessage();
            Log::error("Exception في الاتصال بـ Gemini", ['error' => $e->getMessage()]);

            return response()->json([
                'error' => 'Exception في الاتصال بـ Gemini.',
                'details' => $e->getMessage(),
                'log' => $debugLog
            ], 500);
        }
    }
}
