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
}
