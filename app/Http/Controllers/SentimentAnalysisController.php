<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Services\SentimentAnalysisService;

class SentimentAnalysisController extends Controller
{
    protected SentimentAnalysisService $sentimentService;

    public function __construct(SentimentAnalysisService $sentimentService)
    {
        $this->sentimentService = $sentimentService;
    }

    public function analyze(Request $request): JsonResponse
    {
        $request->validate([
            'chat_history' => 'required|array|min:1',
            'chat_history.*.message' => 'required|string',
        ]);

        $result = $this->sentimentService->analyze($request->input('chat_history'));

        return response()->json([
            'sentiment_score' => $result['score'],
            'label' => $result['label'],
        ]);
    }
}
