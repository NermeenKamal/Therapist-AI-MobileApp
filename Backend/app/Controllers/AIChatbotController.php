<?php

namespace App\Http\Controllers;

use App\Services\AIChatbotService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class AIChatbotController extends Controller
{
    protected AIChatbotService $chatbot;

    public function __construct(AIChatbotService $chatbot)
    {
        $this->chatbot = $chatbot;
    }

    public function sendMessage(Request $request): JsonResponse
    {
        $request->validate([
            'message' => 'required|string|max:1000',
        ]);

        $result = $this->chatbot->sendMessage(
            $request->input('message'),
            Auth::id()
        );

        return response()->json($result, 202);
    }

    public function getResponse(string $conversationId): JsonResponse
    {
        $response = $this->chatbot->getResponse($conversationId);
        
        return response()->json($response);
    }
} 