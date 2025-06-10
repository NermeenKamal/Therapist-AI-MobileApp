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

        \Log::info('SentimentAnalysisController: incoming request', $validated);

        try {
            $bert = new BertSentimentService();
            $bertResponse = $bert->analyze($validated['message']);

            \Log::info('Parsed BERT result', $bertResponse);

            $label = $bertResponse['label'];
            $score = $bertResponse['score'];
            $rating = $bertResponse['rating'];
            $feedback = $bertResponse['feedback'];
            $rawResult = $bertResponse['raw_result'];

            $chatRating = ChatRating::create([
                'appointment_id'   => $validated['appointment_id'],
                'patient_id'       => $validated['patient_id'],
                'rating'           => $rating,
                'feedback'         => $feedback,
                'sentiment_score'  => $score,
                'sentiment_label'  => $label,
            ]);

            \Log::info('Saved ChatRating to DB', ['id' => $chatRating->id]);

            return response()->json([
                'status' => 'success',
                'data' => $chatRating,
                'debug' => [
                    'bert_raw_result' => $rawResult,
                    'positive_score' => $rawResult[0]['label'] === 'POSITIVE' ? $rawResult[0]['score'] : ($rawResult[1]['label'] === 'POSITIVE' ? $rawResult[1]['score'] : null),
                    'negative_score' => $rawResult[0]['label'] === 'NEGATIVE' ? $rawResult[0]['score'] : ($rawResult[1]['label'] === 'NEGATIVE' ? $rawResult[1]['score'] : null),
                ],
            ]);
        } catch (\Exception $e) {
            \Log::error('Exception in SentimentAnalysisController', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to analyze sentiment',
                'detail' => $e->getMessage(),
            ], 500);
        }
    }
}
