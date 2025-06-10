<?php

namespace App\Controllers;

use App\Models\ChatMessage;
use App\Models\Doctor;
use App\Models\Patient;
use App\Models\ChatRating;
use App\Models\Appointment;
use App\Services\FCMService;
use App\Services\BertSentimentService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

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
        $user = Auth::user();

        if (!$user) {
            return response()->json(['error' => 'Unauthenticated user'], 401);
        }

        $senderId = $user->id;
        $userType = $user instanceof Patient ? 'patient' : ($user instanceof Doctor ? 'doctor' : null);

        if (!$userType) {
            return response()->json(['error' => 'Unable to determine user type'], 400);
        }

        $data = $request->validate([
            'appointment_id' => 'required|exists:appointments,id',
            'message' => 'required|string',
        ]);

        $appointment = Appointment::find($data['appointment_id']);

        if (!$appointment) {
            return response()->json(['error' => 'Appointment not found'], 404);
        }

        if (($userType === 'doctor' && $appointment->doctor_id !== $senderId) ||
            ($userType === 'patient' && $appointment->patient_id !== $senderId)) {
            return response()->json(['error' => 'Not authorized for this appointment'], 403);
        }

        $senderType = $userType;
        $receiverType = $userType === 'doctor' ? 'patient' : 'doctor';
        $receiverId = $receiverType === 'doctor' ? $appointment->doctor_id : $appointment->patient_id;
        $receiver = $receiverType === 'doctor' ? Doctor::find($receiverId) : Patient::find($receiverId);

        try {
            $chat = ChatMessage::create([
                'appointment_id' => $data['appointment_id'],
                'sender_type' => $senderType,
                'sender_id' => $senderId,
                'message' => $data['message'],
                'is_read' => false,
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to save message'], 500);
        }

        $rating = null;
        if ($senderType === 'doctor') {
            try {
                $bertResult = $this->bert->analyze($data['message']);

                $ratingValue = match (strtolower($bertResult['label'])) {
                    'positive' => 5,
                    'neutral'  => 3,
                    'negative' => 1,
                    default    => 2,
                };

                $rating = ChatRating::create([
                    'appointment_id'   => $data['appointment_id'],
                    'patient_id'       => $receiverId,
                    'rating'           => $ratingValue,
                    'feedback'         => $bertResult['feedback'],
                    'sentiment_score'  => $bertResult['score'],
                    'sentiment_label'  => $bertResult['label'],
                ]);
            } catch (\Exception $e) {
                \Log::error('BERT error: ' . $e->getMessage());
            }
        }

        try {
            $firebaseMessage = [
                'sender_type' => $senderType,
                'sender_id' => $senderId,
                'message' => $data['message'],
                'timestamp' => now()->toISOString(),
            ];

            $client = new \GuzzleHttp\Client();
            $firebaseBase = 'https://therapist-app-4c42e-default-rtdb.firebaseio.com/';
            $firebasePath = $firebaseBase . 'messages/' . $data['appointment_id'] . '.json';

            $client->post($firebasePath, ['json' => $firebaseMessage]);
        } catch (\Exception $e) {
            // Log firebase error if needed
        }

        if ($receiver && $receiver->fcm_token) {
            try {
                $this->fcm->sendToUser(
                    $receiver->fcm_token,
                    'رسالة جديدة',
                    $user->name . ': ' . substr($chat->message, 0, 50),
                    [
                        'chat_id' => $chat->id,
                        'appointment_id' => $data['appointment_id'],
                        'sender_type' => $senderType,
                        'sender_id' => $senderId,
                    ]
                );
            } catch (\Exception $e) {
                // Log FCM error if needed
            }
        }

        $response = $chat->toArray();
        if ($rating) {
            $response['sentiment_score'] = $rating->sentiment_score;
            $response['sentiment_label'] = $rating->sentiment_label;
            $response['feedback'] = $rating->feedback;
            $response['rating'] = $rating->rating;
        }

        return response()->json(['data' => $response], 201);
    }

    public function getMessages(int $appointmentId): JsonResponse
    {
        $user = Auth::user();
        $userType = $user instanceof Patient ? 'patient' : ($user instanceof Doctor ? 'doctor' : null);
        $userId = $user->id;

        if (!$userType) {
            return response()->json(['message' => 'نوع المستخدم غير محدد'], 400);
        }

        $appointment = Appointment::find($appointmentId);

        if (!$appointment) {
            return response()->json(['message' => 'الموعد غير موجود'], 404);
        }

        if (($userType === 'doctor' && $appointment->doctor_id !== $userId) ||
            ($userType === 'patient' && $appointment->patient_id !== $userId)) {
            return response()->json(['message' => 'غير مصرح بالوصول لهذا الموعد'], 403);
        }

        $messages = ChatMessage::where('appointment_id', $appointmentId)
            ->orderBy('created_at')
            ->get();

        return response()->json($messages);
    }

    public function markAsRead(Request $request): JsonResponse
    {
        $data = $request->validate([
            'appointment_id' => 'required|exists:appointments,id',
        ]);

        $user = Auth::user();
        $userType = $user instanceof Patient ? 'patient' : ($user instanceof Doctor ? 'doctor' : null);
        $userId = $user->id;

        if (!$userType) {
            return response()->json(['message' => 'نوع المستخدم غير محدد'], 400);
        }

        ChatMessage::where('appointment_id', $data['appointment_id'])
            ->where(function ($query) use ($userType, $userId) {
                $query->where('sender_type', '!=', $userType)
                      ->orWhere('sender_id', '!=', $userId);
            })
            ->where('is_read', false)
            ->update(['is_read' => true]);

        return response()->json(['success' => true]);
    }

   public function getRecentChats(): JsonResponse
{
    $user = Auth::user();
    $userType = $user instanceof Patient ? 'patient' : ($user instanceof Doctor ? 'doctor' : null);
    $userId = $user->id;

    if (!$userType) {
        return response()->json(['message' => 'نوع المستخدم غير محدد'], 400);
    }

    // الحصول على كل مواعيد المستخدم (دكتور أو مريض)
    $appointmentIds = Appointment::where($userType . '_id', $userId)
        ->pluck('id')
        ->toArray();

    // المواعيد اللي فيها رسائل فقط
    $appointmentIdsWithMessages = ChatMessage::whereIn('appointment_id', $appointmentIds)
        ->pluck('appointment_id')
        ->unique()
        ->toArray();

    $recentChats = [];

    foreach ($appointmentIdsWithMessages as $appointmentId) {
        $latestMessage = ChatMessage::where('appointment_id', $appointmentId)
            ->latest('created_at')
            ->first();

        if ($latestMessage) {
            $unreadCount = ChatMessage::where('appointment_id', $appointmentId)
                ->where('sender_type', '!=', $userType)
                ->where('is_read', false)
                ->count();

            $appointment = Appointment::with(['doctor', 'patient'])->find($appointmentId);

            // تجهيز الطرف الآخر حسب نوع المستخدم
            $otherParty = $userType === 'patient'
                ? [
                    'id' => $appointment->doctor->id,
                    'name' => $appointment->doctor->name,
                    'specialization' => $appointment->doctor->specialization,
                    'image' => $appointment->doctor->profile_image,
                ]
                : [
                    'id' => $appointment->patient->id,
                    'name' => $appointment->patient->name,
                    'image' => $appointment->doctor->profile_image,
                ];

            $recentChats[] = [
                'appointment_id' => $appointmentId,
                'latest_message' => $latestMessage,
                'unread_count' => $unreadCount,
                $userType === 'patient' ? 'doctor' : 'patient' => $otherParty,
            ];
        }
    }

    // ترتيب المحادثات حسب أحدث رسالة
    usort($recentChats, function ($a, $b) {
        return strtotime($b['latest_message']['created_at']) - strtotime($a['latest_message']['created_at']);
    });

    return response()->json($recentChats);
}

}
