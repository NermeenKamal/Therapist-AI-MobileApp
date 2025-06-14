<?php

// app/Controllers/GradioChatController.php

namespace App\Controllers;

use App\Models\GradioMessage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class GradioChatController extends Controller
{
    // Step 1: Send message to Gradio
    public function send(Request $request)
    {
        $request->validate([
            'message' => 'required|string'
        ]);

        $message = $request->input('message');

        $record = GradioMessage::create([
            'message' => $message,
            'status' => 'pending'
        ]);

        try {
            $response = Http::timeout(60)->post('https://nermeenkamal888-therapy.hf.space/gradio_api/call/predict', [
                'data' => [$message]
            ]);

            $json = $response->json();

            $record->update([
                'conversation_id' => $json['conversation_id'] ?? null,
                'status' => isset($json['conversation_id']) ? 'pending' : 'failed'
            ]);

            return response()->json([
                'message_id' => $record->id,
                'status' => 'pending'
            ]);
        } catch (\Exception $e) {
            $record->update(['status' => 'failed']);
            return response()->json(['error' => 'Gradio request failed'], 500);
        }
    }

    // Step 2: Polling to get response
    public function getResponse($id)
    {
        $record = GradioMessage::findOrFail($id);

        if (!$record->conversation_id) {
            return response()->json(['status' => 'pending']);
        }

        try {
            $response = Http::timeout(60)->get("https://nermeenkamal888-therapy.hf.space/gradio_api/response/{$record->conversation_id}");

            if ($response->status() === 404) {
                return response()->json(['status' => 'pending']);
            }

            $json = $response->json();

            $record->update([
                'response' => json_encode($json),
                'status' => 'done'
            ]);

            return response()->json([
                'status' => 'done',
                'response' => $json
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Timeout or error occurred'], 500);
        }
    }
}

