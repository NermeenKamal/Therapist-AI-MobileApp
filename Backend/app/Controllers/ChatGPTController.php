<?php

namespace App\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use OpenAI\Laravel\Facades\OpenAI;
use Illuminate\Support\Facades\Log;

class ChatGPTController extends Controller
{

public function sendMessage(Request $request)
{
    $userMessage = $request->input('message');

    $response = Http::withHeaders([
        'Content-Type' => 'application/json',
    ])->post('https://generativelanguage.googleapis.com/v1beta/models/gemini-2.0-flash:generateContent?key=' . env('GEMINI_API_KEY'), [
        'contents' => [
            [
                'parts' => [
                    ['text' => $userMessage]
                ]
            ]
        ]
    ]);

    if ($response->failed()) {
        return response()->json([
            'error' => 'حدث خطأ أثناء الاتصال بـ Gemini API.',
            'details' => $response->json()
        ], 500);
    }

    return response()->json([
        'response' => $response->json()['candidates'][0]['content']['parts'][0]['text'] ?? 'لا يوجد رد'
    ]);
}



    public function getMessages(Request $req)
    {
        $req->validate(['conversation_id'=>'required|string']);
        Log::info('Get messages conversation', ['cid'=>$req->conversation_id]);

        return response()->json([
            'status'=>'success',
            'data'=> [
                ['role'=>'user','content'=>'مرحبا ؟'],
                ['role'=>'assistant','content'=>'أهلاً، كيف يمكنني مساعدتك؟'],
            ]
        ]);
    }

    public function generateReport(Request $req)
    {
        $req->validate(['conversation'=>'required|array']);
        Log::info('Generate report payload', $req->conversation);

        try {
            $prompt = "Generate a summary report based on this conversation:\n";
            foreach ($req->conversation as $msg) {
                $prompt .= strtoupper($msg['role']).": ".$msg['content']."\n";
            }

            $resp = OpenAI::chat()->create([
                'model'=>'gpt-4o', 
                'messages'=>[
                    ['role'=>'system','content'=>'You are a helpful assistant.'],
                    ['role'=>'user','content'=>$prompt]
                ],
            ]);

            $report = $resp['choices'][0]['message']['content'];
            Log::info('Generated report', ['report'=>$report]);

            return response()->json(['status'=>'success','report'=>$report]);

        } catch (\Exception $e) {
            Log::error('Report error', ['error'=>$e->getMessage()]);
            return response()->json(['status'=>'error','message'=>$e->getMessage()],500);
        }
    }
}
