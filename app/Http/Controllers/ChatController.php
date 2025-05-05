<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use App\Models\ChatMessage;

class ChatController extends Controller
{
    /**
     * Send a message from the authenticated patient to the AI chatbot.
     *
     * @paramRequest  $request
     * @returnJsonResponse
     */
    public function sendMessage(Request $request): JsonResponse
    {
        $request->validate([
            'message' => 'required|string',
        ]);

        $user = Auth::user();

        // 1. Save patient's message
        ChatMessage::create([
            'user_id' => $user->id,
            'sender'  => 'patient',
            'message' => $request->message,
        ]);

        // 2. Send message to AI Team API
        $response = Http::withToken(config('services.ai.token'))
            ->post(config('services.ai.endpoint') . '/chat/process', [
                'patient_id' => $user->id,
                'message'    => $request->message,
            ]);

        // 3. Handle response
        if ($response->successful() && $response->json('reply')) {
            $botReply = $response->json('reply');

            // Save bot reply
            ChatMessage::create([
                'user_id' => $user->id,
                'sender'  => 'bot',
                'message' => $botReply,
            ]);

            return response()->json([
                'status' => 'success',
                'reply'  => $botReply,
            ]);
        }

        return response()->json([
            'status'  => 'error',
            'message' => 'Failed to get response from AI.',
        ], 502);
    }

    /**
     * Receive a reply from the AI Team (webhook) and store it.
     *
     * @paramRequest  $request
     * @returnJsonResponse
     */
    public function botReply(Request $request): JsonResponse
    {
        $request->validate([
            'patient_id' => 'required|exists:users,id',
            'reply'      => 'required|string',
        ]);

        ChatMessage::create([
            'user_id' => $request->patient_id,
            'sender'  => 'bot',
            'message' => $request->reply,
        ]);

        return response()->json([
            'status'  => 'success',
            'message' => 'Bot reply saved.',
        ], 201);
    }

    /**
     * Get full chat history between the authenticated patient and the bot.
     *
     * @returnJsonResponse
     */
    public function getMessages(): JsonResponse
    {
        $user = Auth::user();

        $messages = ChatMessage::where('user_id', $user->id)
            ->orderBy('created_at', 'asc')
            ->get(['sender', 'message', 'created_at']);

        return response()->json([
            'messages' => $messages,
        ]);
    }
}
