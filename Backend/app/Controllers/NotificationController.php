<?php

namespace App\Controllers;

use App\Services\FCMService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

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
        $notif = $request->user()->notifications()->findOrFail($id);
        $notif->is_read = true;
        $notif->save();

        if ($request->user()->fcm_token) {
            $this->fcm->sendToUser(
                $request->user()->fcm_token,
                'Notification Read',
                'تم قراءة الإشعار بنجاح',
                ['notification_id' => $notif->id]
            );
        }

        return response()->json($notif);
    }
}
