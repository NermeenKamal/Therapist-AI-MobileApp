<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\FCMService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class BroadcastNotificationController extends Controller
{
    protected FCMService $fcm;

    public function __construct(FCMService $fcm)
    {
        $this->fcm = $fcm;
    }

    public function broadcast(Request $request): JsonResponse
    {
        $data = $request->validate([
            'title' => 'required|string',
            'body'  => 'required|string',
        ]);

        $tokens = [];
        User::chunk(100, function ($users) use ($data, &$tokens) {
            foreach ($users as $user) {
                $user->notifications()->create(['title' => $data['title'], 'body' => $data['body']]);
                if ($user->fcm_token) {
                    $tokens[] = $user->fcm_token;
                }
            }
        });

        $this->fcm->sendBulk($tokens, $data['title'], $data['body']);

        return response()->json(['message' => 'Broadcast sent'], 200);
    }
}
