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
    $data = $request->validate([
        'appointment_id' => 'required|exists:appointments,id',
        'message' => 'required|string',
    ]);

    $user = Auth::user();
    $userType = $user->role;

    if ($userType === 'doctor') {
        $senderType = 'doctor';
        $senderId = $user->doctor->id;
        $appointment = \App\Models\Appointment::find($data['appointment_id']);
        $receiverType = 'patient';
        $receiverId = $appointment->patient_id;
        $receiver = Patient::find($receiverId);
    } else {
        $senderType = 'patient';
        $senderId = $user->patient->id;
        $appointment = \App\Models\Appointment::find($data['appointment_id']);
        $receiverType = 'doctor';
        $receiverId = $appointment->doctor_id;
        $receiver = Doctor::find($receiverId);
    }

    // إنشاء الرسالة في قاعدة بيانات MySQL
    $chat = ChatMessage::create([
        'appointment_id' => $data['appointment_id'],
        'sender_type' => $senderType,
        'sender_id' => $senderId,
        'message' => $data['message'],
        'is_read' => false,
    ]);

    // تحليل المشاعر (لو المرسل دكتور)
    $rating = null;
    if ($senderType === 'doctor') {
        $bertResult = $this->bert->analyze($data['message']);

        $rating = ChatRating::create([
            'appointment_id' => $data['appointment_id'],
            'patient_id' => $receiverId,
            'sentiment_score' => $bertResult['score'],
            'sentiment_label' => $bertResult['label'],
        ]);
    }

    // إرسال الرسالة إلى Firebase Realtime Database
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
        $client->post($firebasePath, [
            'json' => $firebaseMessage
        ]);
    } catch (\Exception $e) {
        // سجل الخطأ بدون تعطيل العملية
        \Log::error('Firebase Error: ' . $e->getMessage());
    }

    // إرسال إشعار FCM لو فيه توكن
    if ($receiver && $receiver->fcm_token) {
        $senderName = ($senderType === 'doctor') ? $user->doctor->name : $user->patient->name;

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
    }

    // تحضير الرد النهائي
    $response = $chat->toArray();
    if ($rating) {
        $response['sentiment_score'] = $rating->sentiment_score;
        $response['sentiment_label'] = $rating->sentiment_label;
    }

    return response()->json($response, 201);
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
    public function getMessages(int $appointmentId): JsonResponse
    {
        // التحقق من وصول المستخدم للموعد
        $user = Auth::user();
        $userType = $user->role;
        
        $appointment = \App\Models\Appointment::find($appointmentId);
        
        if (!$appointment) {
            return response()->json(['message' => 'الموعد غير موجود'], 404);
        }
        
        // التحقق من أن المستخدم الحالي هو جزء من هذا الموعد
        $hasAccess = false;
        
        if ($userType === 'doctor' && $appointment->doctor_id === $user->doctor->id) {
            $hasAccess = true;
        } else if ($userType === 'patient' && $appointment->patient_id === $user->patient->id) {
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
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function markAsRead(Request $request): JsonResponse
    {
        $data = $request->validate([
            'appointment_id' => 'required|exists:appointments,id',
        ]);
        
        $user = Auth::user();
        $userType = $user->role;
        
        // تحديد نوع المستقبل ومعرفه
        $receiverType = $userType;
        $receiverId = ($userType === 'doctor') ? $user->doctor->id : $user->patient->id;
        
        // تحديث حالة القراءة للرسائل التي ليست من المستخدم الحالي
        ChatMessage::where('appointment_id', $data['appointment_id'])
            ->where(function($query) use ($receiverType, $receiverId) {
                $query->where('sender_type', '!=', $receiverType)
                    ->orWhere('sender_id', '!=', $receiverId);
            })
            ->where('is_read', false)
            ->update(['is_read' => true]);
        
        return response()->json(['success' => true]);
    }
    
    /**
     * الحصول على المحادثات الحديثة للمستخدم الحالي
     * 
     * @return JsonResponse
     */
    public function getRecentChats(): JsonResponse
    {
        $user = Auth::user();
        $userType = $user->role;
        $userId = ($userType === 'doctor') ? $user->doctor->id : $user->patient->id;
        
        // الحصول على معرفات المواعيد حيث شارك المستخدم في المحادثة
        $appointmentIds = [];
        
        if ($userType === 'doctor') {
            // حالة الدكتور: جلب المواعيد الخاصة به
            $appointmentIds = \App\Models\Appointment::where('doctor_id', $userId)
                ->pluck('id')
                ->toArray();
        } else {
            // حالة المريض: جلب المواعيد الخاصة به
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
}
