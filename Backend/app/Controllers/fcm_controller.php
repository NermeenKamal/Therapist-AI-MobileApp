<?php

namespace App\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use App\Services\FCMService;

class FCMController extends Controller
{
    protected FCMService $fcmService;

    public function __construct(FCMService $fcmService)
    {
        $this->fcmService = $fcmService;
    }

    /**
     * تحديث رمز FCM للمستخدم الحالي
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function updateToken(Request $request): JsonResponse
    {
        $request->validate([
            'fcm_token' => 'required|string'
        ]);

        $user = Auth::user();
        $userType = $user->role;
        $fcmToken = $request->input('fcm_token');

        // تحديث رمز FCM حسب نوع المستخدم (دكتور أو مريض)
        if ($userType === 'doctor') {
            $doctor = $user->doctor; // بافتراض وجود علاقة بين نموذج المستخدم والدكتور
            $doctor->fcm_token = $fcmToken;
            $doctor->save();
        } else if ($userType === 'patient') {
            $patient = $user->patient; // بافتراض وجود علاقة بين نموذج المستخدم والمريض
            $patient->fcm_token = $fcmToken;
            $patient->save();
        }

        return response()->json([
            'success' => true,
            'message' => 'تم تحديث رمز FCM بنجاح'
        ]);
    }

    /**
     * الاشتراك في موضوع معين
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function subscribeTopic(Request $request): JsonResponse
    {
        $request->validate([
            'topic' => 'required|string',
        ]);

        $user = Auth::user();
        $userType = $user->role;
        $topic = $request->input('topic');
        $fcmToken = null;

        // الحصول على رمز FCM للمستخدم الحالي
        if ($userType === 'doctor') {
            $doctor = $user->doctor;
            $fcmToken = $doctor->fcm_token;
        } else if ($userType === 'patient') {
            $patient = $user->patient;
            $fcmToken = $patient->fcm_token;
        }

        if (!$fcmToken) {
            return response()->json([
                'success' => false,
                'message' => 'لم يتم العثور على رمز FCM للمستخدم'
            ], 400);
        }

        // الاشتراك في الموضوع
        $result = $this->fcmService->subscribeToTopic([$fcmToken], $topic);

        return response()->json([
            'success' => $result['success'],
            'message' => $result['message']
        ]);
    }

    /**
     * إرسال إشعار للمستخدمين (محدود للمسؤولين فقط)
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function sendNotification(Request $request): JsonResponse
    {
        $request->validate([
            'topic' => 'required_without:tokens|string',
            'tokens' => 'required_without:topic|array',
            'tokens.*' => 'string',
            'title' => 'required|string',
            'body' => 'required|string',
            'data' => 'sometimes|array'
        ]);

        $title = $request->input('title');
        $body = $request->input('body');
        $data = $request->input('data', []);

        // إرسال إشعار إما لموضوع أو لرموز محددة
        if ($request->has('topic')) {
            $topic = $request->input('topic');
            $result = $this->fcmService->sendToTopic($topic, $title, $body, $data);
        } else {
            $tokens = $request->input('tokens');
            $result = $this->fcmService->sendToMultipleUsers($tokens, $title, $body, $data);
        }

        return response()->json([
            'success' => $result['success'],
            'message' => $result['message'],
            'result' => $result['result'] ?? null
        ]);
    }
}
