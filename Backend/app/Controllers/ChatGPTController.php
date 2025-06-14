<?php

namespace App\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ChatGPTController extends Controller
{
    // دالة public للـ route الخاص بـ Gradio
    public function sendToGradio(Request $request)
    {
        $message = $request->input('message');
        if (!$message) {
            return response()->json(['error' => 'حقل message مطلوب'], 400);
        }
        return $this->handleSendToGradio($message);
    }

    // دالة public للـ route الخاص بـ Gemini
    public function sendToGemini(Request $request)
    {
        $message = $request->input('message');
        if (!$message) {
            return response()->json(['error' => 'حقل message مطلوب'], 400);
        }
        return $this->handleSendToGemini($message);
    }

    // دالة خاصة تعالج طلب Gradio فعليًا
    private function handleSendToGradio(string $message)
    {
        try {
            Log::info('Sending message to Gradio model', ['message' => $message]);

            // طلب POST للحصول على event_id
            $postResponse = Http::withHeaders([
                'Content-Type' => 'application/json'
            ])->post('https://nermeenkamal888-therapy.hf.space/gradio_api/call/predict', [
                'data' => [$message]
            ]);

            if ($postResponse->failed()) {
                Log::error('Failed POST to Gradio model', ['response' => $postResponse->json()]);
                return response()->json([
                    'error' => 'فشل في POST للموديل.',
                    'details' => $postResponse->json()
                ], 500);
            }

            $eventId = $postResponse->json()['event_id'] ?? null;
            if (!$eventId) {
                Log::error('No event_id received from Gradio response', ['response' => $postResponse->json()]);
                return response()->json([
                    'error' => 'لم يتم الحصول على event_id من Gradio.'
                ], 500);
            }

            Log::info('Received event_id from Gradio', ['event_id' => $eventId]);

            // ننتظر قليلاً ثم نطلب النتيجة
            sleep(2);

            $getResponse = Http::withOptions(['stream' => true])
                ->get("https://nermeenkamal888-therapy.hf.space/gradio_api/call/predict/{$eventId}");

            if ($getResponse->failed()) {
                Log::error('Failed GET result from Gradio model', ['body' => $getResponse->body()]);
                return response()->json([
                    'error' => 'فشل في GET للنتيجة من الموديل.',
                    'details' => $getResponse->body()
                ], 500);
            }

            $body = $getResponse->body();

            // نبحث عن data في نص الرد
            preg_match('/data:\s*(\[.*\])/', $body, $matches);
            $jsonData = $matches[1] ?? null;

            if ($jsonData) {
                $resultArray = json_decode($jsonData, true);
                Log::info('Received response from Gradio', ['response' => $resultArray[0] ?? '']);
                return response()->json(['response' => $resultArray[0] ?? '']);
            }

            Log::error('Failed to extract data from Gradio response', ['body' => $body]);
            return response()->json([
                'error' => 'فشل في استخراج البيانات من الرد.'
            ], 500);

        } catch (\Exception $e) {
            Log::error('Exception connecting to Gradio', ['exception' => $e->getMessage()]);
            return response()->json([
                'error' => 'Exception في الاتصال بـ Gradio.',
                'details' => $e->getMessage()
            ], 500);
        }
    }

    // دالة خاصة تعالج طلب Gemini فعليًا
    private function handleSendToGemini(string $message)
    {
        try {
            Log::info('Sending message to Gemini model', ['message' => $message]);

            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
            ])->post(
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
                Log::error('Failed to connect to Gemini API', ['response' => $response->json()]);
                return response()->json([
                    'error' => 'فشل في الاتصال بـ Gemini API.',
                    'details' => $response->json()
                ], 500);
            }

            $text = $response->json()['candidates'][0]['content']['parts'][0]['text'] ?? 'لا يوجد رد';

            Log::info('Received response from Gemini', ['response' => $text]);

            return response()->json([
                'response' => $text
            ]);
        } catch (\Exception $e) {
            Log::error('Exception connecting to Gemini', ['exception' => $e->getMessage()]);
            return response()->json([
                'error' => 'Exception في الاتصال بـ Gemini.',
                'details' => $e->getMessage()
            ], 500);
        }
    }
}
