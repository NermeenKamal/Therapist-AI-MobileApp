<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use App\Models\ChatRating;

class ChatSentimentController extends Controller
{
    public function analyze(Request $request)
    {
        $request->validate([
            'chat_text' => 'required|string|min:5',
            'doctor_id' => 'required|exists:users,id',
            'patient_id' => 'required|exists:users,id',
        ]);

        $response = Http::post('http://5002-izw2x95fu492p51h4uy5z-5f8e2608.manus.computer/ai/sentiment/analyze-chat', [
            'chat_text' => $request->chat_text
        ]);

        if (!$response->ok()) {
            return response()->json([
                'error' => $response->json()['error'] ?? 'Sentiment API error'
            ], 500);
        }

        $data = $response->json();

        // حفظ النتيجة في قاعدة البيانات
        $rating = ChatRating::create([
            'doctor_id' => $request->doctor_id,
            'patient_id' => $request->patient_id,
            'chat_text' => $request->chat_text,
            'sentiment_score' => $data['sentiment_score'],
            'sentiment_label' => $data['label'],
        ]);

        return response()->json([
            'message' => 'Sentiment analyzed successfully',
            'data' => $rating,
        ]);
    }
}
