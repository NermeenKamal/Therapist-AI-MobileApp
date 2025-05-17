<?php

namespace App\Controllers;

use App\Models\Article;
use Illuminate\Http\JsonResponse;

class ArticleController extends Controller
{
    public function index(): JsonResponse
    {
        $articles = Article::orderBy('published_at', 'desc')
            ->paginate(10);
            
        return response()->json($articles);
    }
} 
