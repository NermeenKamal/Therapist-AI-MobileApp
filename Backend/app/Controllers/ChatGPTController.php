<?php

namespace App\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use OpenAI\Laravel\Facades\OpenAI;
use Illuminate\Support\Facades\Log;

class ChatGPTController extends Controller
{
   use Illuminate\Support\Facades\Http;

public function sendMessage(Request $request)
{
    $userMessage = $request->input('message');

    $response = Http::withHeaders([
        'Content-Type' => 'application/json',
        'x-rapidapi-host' => 'cheapest-gpt-4-turbo-gpt-4-vision-chatgpt-openai-ai-api.p.rapidapi.com',
        'x-rapidapi-key' => env('RAPIDAPI_KEY'), // ضيفي المفتاح في .env
    ])->post('https://cheapest-gpt-4-turbo-gpt-4-vision-chatgpt-openai-ai-api.p.rapidapi.com/v1/chat/completions', [
        'model' => 'gpt-4o',
        'messages' => [
            ['role' => 'user', 'content' => $userMessage]
        ],
        'max_tokens' => 100,
        'temperature' => 0.9
    ]);

    if ($response->failed()) {
        return response()->json([
            'error' => 'حدث خطأ أثناء الاتصال بـ RapidAPI.',
            'details' => $response->json()
        ], 500);
    }

    return response()->json([
        'response' => $response->json()['choices'][0]['message']['content']
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
                'model'=>'gpt-4o', // استخدمي gpt-4o أو gpt-3.5 حسب الحاجة
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
