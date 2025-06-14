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

    private function sendToGradio($message, &$debugLog)
    {
        try {
            $debugLog[] = "Sending to Gradio model...";
            Log::info("Sending message to Gradio", ['message' => $message]);

            $postResponse = Http::withHeaders([
                'Content-Type' => 'application/json'
            ])->timeout(60)->post('https://nermeenkamal888-therapy.hf.space/gradio_api/call/predict', [
                'data' => [$message]
            ]);

            if ($postResponse->failed()) {
                $debugLog[] = "POST failed. Response: " . $postResponse->body();
                Log::error("Gradio POST failed", ['response' => $postResponse->body()]);
                return response()->json([
                    'error' => 'فشل في POST للموديل.',
                    'details' => $postResponse->json(),
                    'log' => $debugLog
                ], 500);
            }

            $eventId = $postResponse->json()['event_id'] ?? null;
            $debugLog[] = "Event ID: $eventId";

            if (!$eventId) {
                return response()->json([
                    'error' => 'لم يتم الحصول على event_id من Gradio.',
                    'log' => $debugLog
                ], 500);
            }

            sleep(2);

            $getResponse = Http::withOptions(['stream' => true])
                ->timeout(60)
                ->get("https://nermeenkamal888-therapy.hf.space/gradio_api/call/predict/{$eventId}");

            if ($getResponse->failed()) {
                $debugLog[] = "GET failed. Body: " . $getResponse->body();
                Log::error("Gradio GET failed", ['response' => $getResponse->body()]);
                return response()->json([
                    'error' => 'فشل في GET للنتيجة من الموديل.',
                    'details' => $getResponse->body(),
                    'log' => $debugLog
                ], 500);
            }

            $body = $getResponse->body();
            $debugLog[] = "GET body: $body";

            if (preg_match('/data:\s*(\[.*\])/', $body, $matches)) {
                $jsonData = $matches[1];
                $resultArray = json_decode($jsonData, true);
                $debugLog[] = "Extracted response: " . ($resultArray[0] ?? '');

                return response()->json([
                    'response' => $resultArray[0] ?? '',
                    'log' => $debugLog
                ]);
            }

            $debugLog[] = "Regex failed to extract response";
            return response()->json([
                'error' => 'فشل في استخراج البيانات من الرد.',
                'log' => $debugLog
            ], 500);
        } catch (\Exception $e) {
            $debugLog[] = "Exception: " . $e->getMessage();
            Log::error("Exception في الاتصال بـ Gradio", ['error' => $e->getMessage()]);

            return response()->json([
                'error' => 'Exception في الاتصال بـ Gradio.',
                'details' => $e->getMessage(),
                'log' => $debugLog
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
