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

        // اختاري أي موديل تستخدمينه هنا
        $useGradio = true;

        if ($useGradio) {
            return $this->sendToGradio($userMessage);
        } else {
            return $this->sendToGemini($userMessage);
        }
    }

    private function sendToGradio($message)
    {
        try {
            // الخطوة الأولى: إرسال POST للحصول على event_id
            $postResponse = Http::withHeaders([
                'Content-Type' => 'application/json'
            ])->post('https://nermeenkamal888-therapy.hf.space/gradio_api/call/predict', [
                'data' => [$message]
            ]);

            if ($postResponse->failed()) {
                return response()->json([
                    'error' => 'فشل في POST للموديل.',
                    'details' => $postResponse->json()
                ], 500);
            }

            $eventId = $postResponse->json()['event_id'] ?? null;

            if (!$eventId) {
                return response()->json([
                    'error' => 'لم يتم الحصول على event_id من Gradio.'
                ], 500);
            }

            // الخطوة الثانية: GET للنتيجة باستخدام event_id
            sleep(2); // انتظار بسيط لو البيانات لسه بتتحمل

            $getResponse = Http::withOptions(['stream' => true])
                ->get("https://nermeenkamal888-therapy.hf.space/gradio_api/call/predict/{$eventId}");

            if ($getResponse->failed()) {
                return response()->json([
                    'error' => 'فشل في GET للنتيجة من الموديل.',
                    'details' => $getResponse->body()
                ], 500);
            }

            // نقرأ البيانات كـ text ونستخرج الرد من داخل data:
            $body = $getResponse->body();

            preg_match('/data:\s*(\[.*\])/', $body, $matches);
            $jsonData = $matches[1] ?? null;

            if ($jsonData) {
                $resultArray = json_decode($jsonData, true);
                return response()->json(['response' => $resultArray[0] ?? '']);
            }

            return response()->json([
                'error' => 'فشل في استخراج البيانات من الرد.'
            ], 500);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Exception في الاتصال بـ Gradio.',
                'details' => $e->getMessage()
            ], 500);
        }
    }

    private function sendToGemini($message)
    {
        try {
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
                return response()->json([
                    'error' => 'فشل في الاتصال بـ Gemini API.',
                    'details' => $response->json()
                ], 500);
            }

            return response()->json([
                'response' => $response->json()['candidates'][0]['content']['parts'][0]['text'] ?? 'لا يوجد رد'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Exception في الاتصال بـ Gemini.',
                'details' => $e->getMessage()
            ], 500);
        }
    }
}

