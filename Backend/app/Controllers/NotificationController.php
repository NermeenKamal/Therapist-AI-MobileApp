<?php

namespace App\Controllers;

use App\Models\Notification;
use App\Services\FCMService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    protected FCMService $fcm;

    public function __construct(FCMService $fcm)
    {
        $this->fcm = $fcm;
    }

    public function index(Request $request): JsonResponse
    {
        $notifications = $request->user()->notifications()->orderBy('created_at', 'desc')->get();
        return response()->json($notifications);
    }

    public function markRead(Request $request, int $id): JsonResponse
    {
        $notification = $request->user()->notifications()->findOrFail($id);
        $notification->is_read = true;
        $notification->save();

        $this->fcm->sendToUser(
            $request->user()->fcm_token,
            'Notification Read',
            'Notification Has Been Read',
            ['notification_id' => $notification->id]
        );

        return response()->json($notification);
    }

    public function unreadCount(Request $request): JsonResponse
{
    $user = $request->user();

    if (!$user) {
        return response()->json(['error' => 'Unauthorized'], 401);
    }

    $count = $user->notifications()->where('is_read', false)->count();

    return response()->json(['unread_count' => $count]);
}


    public function storeFCMToken(Request $request): JsonResponse
    {
        $request->validate([
            'fcm_token' => 'required|string'
        ]);

        $request->user()->update([
            'fcm_token' => $request->input('fcm_token')
        ]);

        return response()->json(['message' => 'FCM token saved']);
    }

    public function sendNotification(Request $request): JsonResponse
{
    $request->validate([
        'title' => 'required|string',
        'message' => 'required|string',
        'user_type' => 'required|in:doctor,patient',
        'user_id' => 'required|integer'
    ]);

    $sender = $request->user(); // المستخدم الحالي اللي بيبعت الإشعار

    // حدد مستقبل الإشعار حسب النوع اللي اتبعت
    $recipientModel = $request->user_type === 'doctor'
        ? \App\Models\Doctor::class
        : \App\Models\Patient::class;

    $recipient = $recipientModel::findOrFail($request->user_id);

    // سجل الإشعار عند المستقبل
    $notification = $recipient->notifications()->create([
        'title' => $request->title,
        'message' => $request->message,
        'sender_id' => $sender->id,
        'sender_type' => get_class($sender),
    ]);

    // ابعت الإشعار عن طريق FCM لو عنده توكن
    if ($recipient->fcm_token) {
        $this->fcm->sendToUser(
            $recipient->fcm_token,
            $request->title,
            $request->message,
            [
                'notification_id' => $notification->id,
                'sender_id' => $sender->id,
                'sender_type' => get_class($sender),
            ]
        );
    }

    return response()->json(['message' => 'Notification sent successfully']);
}

}
