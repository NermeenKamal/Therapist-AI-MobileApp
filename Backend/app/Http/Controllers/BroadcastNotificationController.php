<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Services\FCMService;

class BroadcastNotificationController extends Controller
{
    public function sendToAll(Request $request)
    {
        $request->validate([
            'title' => 'required|string',
            'body' => 'required|string',
            'role' => 'nullable|string',
        ]);

        $query = User::query();

        if ($request->has('role')) {
            $query->where('role', $request->role);
        }

        $users = $query->whereNotNull('device_token')->get();

        foreach ($users as $user) {
            app(FCMService::class)->sendNotification(
                $user->device_token,
                $request->title,
                $request->body
            );
        }

        return response()->json(['message' => 'Broadcast notification sent to all.']);
    }
}
