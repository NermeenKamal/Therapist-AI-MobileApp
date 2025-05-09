<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use App\Models\DoctorChatRating;

class SentimentAnalysisController extends Controller
{
    // دالة تحليل المحادثة
    public function analyzeChat(Request $request)
    {
        // التحقق من وجود النص في الطلب
        $request->validate([
            'doctor_id' => 'required|exists:doctors,id',
            'patient_id' => 'required|exists:patients,id',
            'session_id' => 'required',
            'chat_text' => 'required|string'
        ]);

        // إرسال النص إلى خدمة الـ Sentiment Analysis
        $response = Http::post('http://5002-izw2x95fu492p51h4uy5z-5f8e2608.manus.computer/ai/sentiment/analyze-chat', [
            'chat_text' => $request->chat_text
        ]);

        // التحقق إذا كان الاتصال بالخدمة نجح
        if ($response->failed()) {
            return response()->json([
                'error' => 'Failed to connect to Sentiment Analysis Service.',
                'details' => $response->json()
            ], 500);
        }

        // استخراج البيانات من الـ Response
        $data = $response->json();
        $sentimentScore = $data['sentiment_score'] ?? null;
        $label = $data['label'] ?? 'Unknown';

        // تخزين النتيجة في قاعدة البيانات
        $rating = DoctorChatRating::create([
            'doctor_id' => $request->doctor_id,
            'patient_id' => $request->patient_id,
            'session_id' => $request->session_id,
            'sentiment_score' => $sentimentScore,
            'label' => $label
        ]);

        return response()->json([
            'message' => 'Sentiment analysis successful',
            'rating' => $rating
        ], 200);
    }
}
