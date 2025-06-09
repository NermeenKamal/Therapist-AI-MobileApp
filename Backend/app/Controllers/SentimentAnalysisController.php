<?php

namespace App\Controllers;

use App\Services\BertSentimentService;
use Illuminate\Http\Request;

class SentimentAnalysisController extends Controller
{
    protected $bert;

    public function __construct(BertSentimentService $bert)
    {
        $this->bert = $bert;
    }

    public function analyze(Request $request)
    {
        $request->validate([
            'text' => 'required|string|max:1000',
        ]);

        $result = $this->bert->analyze($request->input('text'));

        return response()->json([
            'input' => $request->input('text'),
            'analysis' => $result
        ]);
    }
}

