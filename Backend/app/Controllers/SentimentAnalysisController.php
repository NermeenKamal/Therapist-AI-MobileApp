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
            'user_type'      => 'required|in:doctor',
            'user_id'        => 'required|integer',
            'appointment_id' => 'required|integer',
            'message'        => 'required|string',
            'patient_id'     => 'required|integer',
        ]);

        try {
            $bert = new BertSentimentService();
            $response = $bert->analyze($validated['message']);

            $chatRating = ChatRating::create([
                'appointment_id'   => $validated['appointment_id'],
                'patient_id'       => $validated['patient_id'],
                'rating'           => $response['rating'],
                'feedback'         => $response['feedback'],
                'sentiment_score'  => $response['score'],
                'sentiment_label'  => $response['label'],
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
