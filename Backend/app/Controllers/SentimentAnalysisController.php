<?php

namespace App\Controllers;

use Illuminate\Http\Request;
use App\Services\BertSentimentService;
use App\Models\ChatRating;

class SentimentAnalysisController extends Controller
{
    public function analyze(Request $request)
    {
        $validated = $request->validate([
            'user_type' => 'required|in:doctor',
            'user_id' => 'required|integer',
            'appointment_id' => 'required|integer',
            'message' => 'required|string',
            'patient_id' => 'required|integer', // Ensure it's passed
        ]);

        try {
            $bert = new BertSentimentService();
            $result = $bert->analyze($validated['message']);

            // Save into chat_ratings table
            $chatRating = ChatRating::create([
                'appointment_id'    => $validated['appointment_id'],
                'patient_id'        => $validated['patient_id'],
                'rating'            => $result['label'] === 'positive' ? 5 : ($result['label'] === 'negative' ? 1 : 3),
                'feedback'          => $result['feedback'],
                'sentiment_score'   => $result['score'],
                'sentiment_label'   => $result['label'],
            ]);

            return response()->json([
                'status' => 'success',
                'data' => $chatRating
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to analyze sentiment',
                'detail' => $e->getMessage(),
            ], 500);
        }
    }
}
