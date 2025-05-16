<?php

namespace App\Http\Controllers;

use App\Models\ChatMessage;
use App\Services\FCMService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use App\Services\BertSentimentService;
use App\Models\ChatRating;
use App\Models\User;

class ChatController extends Controller
{
    protected FCMService $fcm;
    protected BertSentimentService $bert;

    public function __construct(FCMService $fcm, BertSentimentService $bert)
    {
        $this->fcm = $fcm;
        $this->bert = $bert;
    }

    public function sendMessage(Request $request): JsonResponse
    {
        $data = $request->validate([
            'receiver_id' => 'required|exists:users,id',
            'message' => 'required|string',
        ]);
        $data['sender_id'] = Auth::id();
        $chat = ChatMessage::create($data);

        // BERT sentiment analysis if sender is doctor
        $sender = User::find(Auth::id());
        $rating = null;
        if ($sender && $sender->role === 'doctor') {
            $bertResult = $this->bert->analyze($data['message']);
            $rating = ChatRating::create([
                'doctor_id' => $sender->id,
                'patient_id' => $data['receiver_id'],
                'chat_text' => $data['message'],
                'sentiment_score' => $bertResult['score'],
                'sentiment_label' => $bertResult['label'],
            ]);
        }

        $receiver = $chat->receiver;
        if ($receiver && $receiver->fcm_token) {
            $this->fcm->sendToUser(
                $receiver->fcm_token,
                'رسالة جديدة',
                Auth::user()->name . ': ' . substr($chat->message, 0, 50),
                ['chat_id' => $chat->id]
            );
        }

        $response = $chat->toArray();
        if ($rating) {
            $response['sentiment_score'] = $rating->sentiment_score;
            $response['sentiment_label'] = $rating->sentiment_label;
        }

        return response()->json($response, 201);
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
