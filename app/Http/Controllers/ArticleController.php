<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class ArticleController extends Controller
{
    public final function index()
    {
        $response = Http::get('https://newsapi.org/v2/top-headlines', [
            'category' => 'health',
            'language' => 'en',
            'apiKey' => '0cd45313eb2e4ec3b7cbcc581603888c'
        ]);

        return response()->json($response->json());
    }

}
