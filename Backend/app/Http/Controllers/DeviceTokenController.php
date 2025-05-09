<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\DeviceToken;
use Illuminate\Support\Facades\Auth;

class DeviceTokenController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'device_token' => 'required|string',
            'platform' => 'nullable|string',
        ]);

        $userId = Auth::id();

        // لو التوكن ده موجود لأي يوزر تاني، امسحه
        DeviceToken::where('device_token', $request->device_token)->delete();

        // احفظ توكن جديد لهذا اليوزر (لو مش موجود)
        $existing = DeviceToken::where('user_id', $userId)
            ->where('device_token', $request->device_token)
            ->first();

        if (!$existing) {
            DeviceToken::create([
                'user_id' => $userId,
                'device_token' => $request->device_token,
                'platform' => $request->platform,
            ]);
        }

        return response()->json(['message' => 'Token saved successfully']);
    }
}
