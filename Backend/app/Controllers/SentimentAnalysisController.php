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
            'patient_id' => 'required|integer',
        ]);

        try {
            $bert = new BertSentimentService();
            $response = $bert->analyze($validated['message']); // expect `result` array

            $results = collect($response['result']);
            $positive = $results->firstWhere('label', 'POSITIVE')['score'] ?? 0;
            $negative = $results->firstWhere('label', 'NEGATIVE')['score'] ?? 0;

            // Normalize to rating out of 5
            $total = $positive + $negative;
            $raw_rating = $total > 0 ? ($positive / $total) * 5 : 2.5; // default neutral
            $rounded_rating = round($raw_rating * 2) / 2; // round to nearest 0.5

            // Choose feedback
            $label = $positive > $negative ? 'positive' : 'negative';
            $feedback = $label === 'positive'
                ? 'User had a positive experience.'
                : 'User had a negative experience.';

            // Save to DB
            $chatRating = ChatRating::create([
                'appointment_id'    => $validated['appointment_id'],
                'patient_id'        => $validated['patient_id'],
                'rating'            => $rounded_rating,
                'feedback'          => $feedback,
                'sentiment_score'   => max($positive, $negative),
                'sentiment_label'   => $label,
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
