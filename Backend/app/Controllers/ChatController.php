<?php

namespace App\Controllers;

use App\Models\ChatMessage;
use App\Models\Doctor;
use App\Models\Patient;
use App\Services\FCMService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use App\Services\BertSentimentService;
use App\Models\ChatRating;

class ChatController extends Controller
{
    protected FCMService $fcm;
    protected BertSentimentService $bert;
    
    public function __construct(FCMService $fcm, BertSentimentService $bert)
    {
        $this->fcm = $fcm;
        $this->bert = $bert;
    }
    
    /**
     * إرسال رسالة في الدردشة
     * 
     * @param Request $request
     * @return JsonResponse
     */



  public function sendMessage(Request $request): JsonResponse
{
    logger('sendMessage entered');

    $debugLogs = [];
    $debugLogs[] = 'sendMessage called';

    $user = Auth::user();
    if (!$user) {
        $debugLogs[] = 'No authenticated user found.';
        return response()->json(['error' => 'Unauthenticated user', 'logs' => $debugLogs], 401);
    }

    // تحديد نوع المستخدم بناءً على نوع الـ model
    $userType = null;
    $senderId = $user->id;
    
    if ($user instanceof \App\Models\Patient) {
        $userType = 'patient';
        $debugLogs[] = 'Authenticated as patient: id=' . $user->id;
    } elseif ($user instanceof \App\Models\Doctor) {
        $userType = 'doctor';
        $debugLogs[] = 'Authenticated as doctor: id=' . $user->id;
    } else {
        $debugLogs[] = 'Unable to determine user type. User class: ' . get_class($user);
        return response()->json(['error' => 'Unable to determine user type', 'logs' => $debugLogs], 400);
    }

    $data = $request->validate([
        'appointment_id' => 'required|exists:appointments,id',
        'message' => 'required|string',
    ]);
    $debugLogs[] = 'Validated data: ' . json_encode($data);

    // الحصول على الموعد والتحقق من وجوده
    $appointment = \App\Models\Appointment::find($data['appointment_id']);
    if (!$appointment) {
        $debugLogs[] = 'Appointment not found.';
        return response()->json(['error' => 'Appointment not found', 'logs' => $debugLogs], 404);
    }

    // تحديد المرسل والمستقبل
    if ($userType === 'doctor') {
        // التحقق من أن الدكتور مخول لهذا الموعد
        if ($appointment->doctor_id !== $senderId) {
            $debugLogs[] = 'Doctor not authorized for this appointment.';
            return response()->json(['error' => 'Not authorized for this appointment', 'logs' => $debugLogs], 403);
        }
        
        $senderType = 'doctor';
        $receiverType = 'patient';
        $receiverId = $appointment->patient_id;
        $receiver = Patient::find($receiverId);
    } else { // patient
        // التحقق من أن المريض مخول لهذا الموعد
        if ($appointment->patient_id !== $senderId) {
            $debugLogs[] = 'Patient not authorized for this appointment.';
            return response()->json(['error' => 'Not authorized for this appointment', 'logs' => $debugLogs], 403);
        }
        
        $senderType = 'patient';
        $receiverType = 'doctor';
        $receiverId = $appointment->doctor_id;
        $receiver = Doctor::find($receiverId);
    }

    $debugLogs[] = "Sender: $senderType ($senderId), Receiver: $receiverType ($receiverId)";

    try {
        $chat = ChatMessage::create([
            'appointment_id' => $data['appointment_id'],
            'sender_type' => $senderType,
            'sender_id' => $senderId,
            'message' => $data['message'],
            'is_read' => false,
        ]);
        $debugLogs[] = 'Chat message created with ID: ' . $chat->id;
    } catch (\Exception $e) {
        $debugLogs[] = 'Failed to create chat message: ' . $e->getMessage();
        return response()->json(['error' => 'Failed to save message', 'logs' => $debugLogs], 500);
    }

    // تحليل المشاعر إذا كان المرسل هو الدكتور
    $rating = null;
    if ($senderType === 'doctor') {
        try {
            $bertResult = $this->bert->analyze($data['message']);
            $debugLogs[] = 'BERT analysis result: ' . json_encode($bertResult);

            $rating = ChatRating::create([
                'appointment_id' => $data['appointment_id'],
                'patient_id' => $receiverId,
                'sentiment_score' => $bertResult['score'],
                'sentiment_label' => $bertResult['label'],
            ]);
            $debugLogs[] = 'Chat rating saved with ID: ' . $rating->id;
        } catch (\Exception $e) {
            $debugLogs[] = 'BERT analysis or rating save failed: ' . $e->getMessage();
        }
    }

    // إرسال إلى Firebase
    try {
        $firebaseBase = 'https://therapist-app-4c42e-default-rtdb.firebaseio.com/';
        $firebasePath = $firebaseBase . 'messages/' . $data['appointment_id'] . '.json';

        $firebaseMessage = [
            'sender_type' => $senderType,
            'sender_id' => $senderId,
            'message' => $data['message'],
            'timestamp' => now()->toISOString(),
        ];

        $client = new \GuzzleHttp\Client();
        $responseFirebase = $client->post($firebasePath, [
            'json' => $firebaseMessage
        ]);
        $debugLogs[] = 'Firebase response status: ' . $responseFirebase->getStatusCode();
    } catch (\Exception $e) {
        $debugLogs[] = 'Firebase Error: ' . $e->getMessage();
    }

    // إرسال إشعار FCM
    if ($receiver && $receiver->fcm_token) {
        try {
            $senderName = $user->name; // الاسم موجود مباشرة في المودل

            $this->fcm->sendToUser(
                $receiver->fcm_token,
                'رسالة جديدة',
                $senderName . ': ' . substr($chat->message, 0, 50),
                [
                    'chat_id' => $chat->id,
                    'appointment_id' => $data['appointment_id'],
                    'sender_type' => $senderType,
                    'sender_id' => $senderId
                ]
            );
            $debugLogs[] = 'FCM notification sent to token: ' . $receiver->fcm_token;
        } catch (\Exception $e) {
            $debugLogs[] = 'FCM send error: ' . $e->getMessage();
        }
    } else {
        $debugLogs[] = 'No FCM token found for receiver or receiver not found.';
    }

    $response = $chat->toArray();
    if ($rating) {
        $response['sentiment_score'] = $rating->sentiment_score;
        $response['sentiment_label'] = $rating->sentiment_label;
    }

    $debugLogs[] = 'sendMessage response prepared';

    return response()->json([
        'data' => $response,
        'logs' => $debugLogs
    ], 201);
}



    
    // public function sendMessage(Request $request): JsonResponse
    // {
    //     $data = $request->validate([
    //         'appointment_id' => 'required|exists:appointments,id',
    //         'message' => 'required|string',
    //     ]);
        
    //     $user = Auth::user();
    //     $userType = $user->role;
        
    //     // تحديد نوع المرسل والمستقبل
    //     if ($userType === 'doctor') {
    //         $senderType = 'doctor';
    //         $senderId = $user->doctor->id;
            
    //         // الحصول على معرف المريض من الموعد
    //         $appointment = \App\Models\Appointment::find($data['appointment_id']);
    //         $receiverType = 'patient';
    //         $receiverId = $appointment->patient_id;
            
    //         // جلب معلومات المريض
    //         $receiver = Patient::find($receiverId);
    //     } else {
    //         $senderType = 'patient';
    //         $senderId = $user->patient->id;
            
    //         // الحصول على معرف الدكتور من الموعد
    //         $appointment = \App\Models\Appointment::find($data['appointment_id']);
    //         $receiverType = 'doctor';
    //         $receiverId = $appointment->doctor_id;
            
    //         // جلب معلومات الدكتور
    //         $receiver = Doctor::find($receiverId);
    //     }
        
    //     // إنشاء رسالة الدردشة
    //     $chat = ChatMessage::create([
    //         'appointment_id' => $data['appointment_id'],
    //         'sender_type' => $senderType,
    //         'sender_id' => $senderId,
    //         'message' => $data['message'],
    //         'is_read' => false,
    //     ]);
        
    //     // تحليل المشاعر إذا كان المرسل هو الدكتور
    //     $rating = null;
    //     if ($senderType === 'doctor') {
    //         $bertResult = $this->bert->analyze($data['message']);
            
    //         $rating = ChatRating::create([
    //             'appointment_id' => $data['appointment_id'],
    //             'patient_id' => $receiverId,
    //             'sentiment_score' => $bertResult['score'],
    //             'sentiment_label' => $bertResult['label'],
    //         ]);
    //     }
        
    //     // إرسال إشعار بواسطة FCM
    //     if ($receiver && $receiver->fcm_token) {
    //         // استخراج اسم المرسل
    //         if ($senderType === 'doctor') {
    //             $senderName = $user->doctor->name;
    //         } else {
    //             $senderName = $user->patient->name;
    //         }
            
    //         $this->fcm->sendToUser(
    //             $receiver->fcm_token,
    //             'رسالة جديدة',
    //             $senderName . ': ' . substr($chat->message, 0, 50),
    //             [
    //                 'chat_id' => $chat->id,
    //                 'appointment_id' => $data['appointment_id'],
    //                 'sender_type' => $senderType,
    //                 'sender_id' => $senderId
    //             ]
    //         );
    //     }
        
    //     // تحضير الرد
    //     $response = $chat->toArray();
    //     if ($rating) {
    //         $response['sentiment_score'] = $rating->sentiment_score;
    //         $response['sentiment_label'] = $rating->sentiment_label;
    //     }
        
    //     return response()->json($response, 201);
    // }
    
    /**
     * الحصول على الرسائل لموعد محدد
     * 
     * @param int $appointmentId
     * @return JsonResponse
     */
   /**
 * الحصول على الرسائل لموعد محدد
 */
public function getMessages(int $appointmentId): JsonResponse
{
    $user = Auth::user();
    
    // تحديد نوع المستخدم
    $userType = null;
    $userId = null;
    
    if ($user instanceof \App\Models\Patient) {
        $userType = 'patient';
        $userId = $user->id;
    } elseif ($user instanceof \App\Models\Doctor) {
        $userType = 'doctor';
        $userId = $user->id;
    } else {
        return response()->json(['message' => 'نوع المستخدم غير محدد'], 400);
    }
    
    $appointment = \App\Models\Appointment::find($appointmentId);
    
    if (!$appointment) {
        return response()->json(['message' => 'الموعد غير موجود'], 404);
    }
    
    // التحقق من أن المستخدم الحالي هو جزء من هذا الموعد
    $hasAccess = false;
    
    if ($userType === 'doctor' && $appointment->doctor_id === $userId) {
        $hasAccess = true;
    } else if ($userType === 'patient' && $appointment->patient_id === $userId) {
        $hasAccess = true;
    }
    
    if (!$hasAccess) {
        return response()->json(['message' => 'غير مصرح بالوصول لهذا الموعد'], 403);
    }
    
    // جلب الرسائل
    $messages = ChatMessage::where('appointment_id', $appointmentId)
        ->orderBy('created_at')
        ->get();
    
    return response()->json($messages);
}

/**
 * تحديد الرسائل كمقروءة
 */
public function markAsRead(Request $request): JsonResponse
{
    $data = $request->validate([
        'appointment_id' => 'required|exists:appointments,id',
    ]);
    
    $user = Auth::user();
    
    // تحديد نوع المستخدم
    $userType = null;
    $userId = null;
    
    if ($user instanceof \App\Models\Patient) {
        $userType = 'patient';
        $userId = $user->id;
    } elseif ($user instanceof \App\Models\Doctor) {
        $userType = 'doctor';
        $userId = $user->id;
    } else {
        return response()->json(['message' => 'نوع المستخدم غير محدد'], 400);
    }
    
    // تحديث حالة القراءة للرسائل التي ليست من المستخدم الحالي
    ChatMessage::where('appointment_id', $data['appointment_id'])
        ->where(function($query) use ($userType, $userId) {
            $query->where('sender_type', '!=', $userType)
                ->orWhere('sender_id', '!=', $userId);
        })
        ->where('is_read', false)
        ->update(['is_read' => true]);
    
    return response()->json(['success' => true]);
}

/**
 * الحصول على المحادثات الحديثة للمستخدم الحالي
 */
public function getRecentChats(): JsonResponse
{
    $user = Auth::user();
    
    // تحديد نوع المستخدم
    $userType = null;
    $userId = null;
    
    if ($user instanceof \App\Models\Patient) {
        $userType = 'patient';
        $userId = $user->id;
    } elseif ($user instanceof \App\Models\Doctor) {
        $userType = 'doctor';
        $userId = $user->id;
    } else {
        return response()->json(['message' => 'نوع المستخدم غير محدد'], 400);
    }
    
    // الحصول على معرفات المواعيد
    $appointmentIds = [];
    
    if ($userType === 'doctor') {
        $appointmentIds = \App\Models\Appointment::where('doctor_id', $userId)
            ->pluck('id')
            ->toArray();
    } else {
        $appointmentIds = \App\Models\Appointment::where('patient_id', $userId)
            ->pluck('id')
            ->toArray();
    }
    
    // للمواعيد التي بها رسائل فقط
    $appointmentIdsWithMessages = ChatMessage::whereIn('appointment_id', $appointmentIds)
        ->pluck('appointment_id')
        ->unique()
        ->toArray();
    
    // جلب آخر رسالة وعدد الرسائل غير المقروءة لكل موعد
    $recentChats = [];
    foreach ($appointmentIdsWithMessages as $appointmentId) {
        $latestMessage = ChatMessage::where('appointment_id', $appointmentId)
            ->latest('created_at')
            ->first();
            
        if ($latestMessage) {
            // حساب عدد الرسائل غير المقروءة للمستخدم الحالي
            $unreadCount = ChatMessage::where('appointment_id', $appointmentId)
                ->where('sender_type', '!=', $userType)
                ->where('is_read', false)
                ->count();
            
            // جلب معلومات الموعد
            $appointment = \App\Models\Appointment::with(['doctor', 'patient'])
                ->find($appointmentId);
            
            $recentChats[] = [
                'appointment_id' => $appointmentId,
                'appointment' => $appointment,
                'latest_message' => $latestMessage,
                'unread_count' => $unreadCount,
            ];
        }
    }
    
    // ترتيب حسب أحدث رسالة
    usort($recentChats, function($a, $b) {
        return strtotime($b['latest_message']['created_at']) - strtotime($a['latest_message']['created_at']);
    });
    
    return response()->json($recentChats);
}
