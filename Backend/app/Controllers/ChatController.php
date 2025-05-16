<?php

namespace App\Http\Controllers;

use App\Models\ChatMessage;
use App\Services\FCMService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class ChatController extends Controller
{
    protected FCMService $fcm;

    public function __construct(FCMService $fcm)
    {
        $this->fcm = $fcm;
    }

    public function sendMessage(Request $request): JsonResponse
    {
        $data = $request->validate([
            'receiver_id' => 'required|exists:users,id',
            'message' => 'required|string',
        ]);
        $data['sender_id'] = Auth::id();
        $chat = ChatMessage::create($data);

        $receiver = $chat->receiver;
        if ($receiver->fcm_token) {
            $this->fcm->sendToUser(
                $receiver->fcm_token,
                'رسالة جديدة',
                Auth::user()->name . ': ' . substr($chat->message, 0, 50),
                ['chat_id' => $chat->id]
            );
        }

        return response()->json($chat, 201);
    }

    public function getMessages(Request $request, int $userId): JsonResponse
    {
        $authId = Auth::id();
        $messages = ChatMessage::where(function($q) use ($authId, $userId) {
            $q->where('sender_id', $authId)->where('receiver_id', $userId);
        })->orWhere(function($q) use ($authId, $userId) {
            $q->where('sender_id', $userId)->where('receiver_id', $authId);
        })->orderBy('created_at')->get();
        return response()->json($messages);
    }
}
