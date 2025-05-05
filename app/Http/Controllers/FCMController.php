<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\FCMService;

class FCMController extends Controller
{
    protected $fcmService;

    public function __construct(FCMService $fcmService)
    {
        $this->fcmService = $fcmService;
    }

    public final function sendNotification(Request $request) : \Illuminate\Http\JsonResponse
    {
        // تحقق من صحة البيانات المدخلة
        $request->validate([
            'token' => 'required|string', // توكن الجهاز الذي سيتم إرسال الإشعار إليه
            'title' => 'required|string', // عنوان الإشعار
            'body' => 'required|string',  // محتوى الإشعار
        ]);

        // إرسال الإشعار عبر الـ FCM
        try {
            // استدعاء الخدمة لإرسال الإشعار
            $this->fcmService->sendNotification(
                $request->token,
                $request->title,
                $request->body
            );

            // إرسال استجابة عند نجاح الإرسال
            return response()->json(['message' => 'Notification sent successfully.'], 200);
        } catch (\Exception $e) {
            // التعامل مع الأخطاء إذا فشل إرسال الإشعار
            return response()->json([
                'message' => 'Failed to send notification.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

}
