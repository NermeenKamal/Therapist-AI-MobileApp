<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use App\Models\Article;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;

class ImportArticlesJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, SerializesModels;

    private const DEFAULT_IMAGE = 'https://th.bing.com/th/id/OIP.a0NRZ33m0j4afFvhw-nvSQHaGC?cb=iwc2&rs=1&pid=ImgDetMain';

    public function __construct()
    {
        //
    }

    public function handle()
{
    Log::info('ImportArticlesJob started...');
    
    // اختبار الاتصال بقاعدة البيانات
    try {
        $testArticle = Article::create([
            'title' => 'Test Article ' . date('Y-m-d H:i:s'),
            'description' => 'This is a test article',
            'publisher_name' => 'Test',
            'published_at' => date('Y-m-d'),
            'article_image' => 'https://example.com/test.jpg',
        ] );
        
        Log::info('Test article created successfully', ['id' => $testArticle->id]);
    } catch (\Exception $e) {
        Log::error('Failed to create test article', [
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]);
    }
    
    // استخدام Guzzle بدلاً من Browsershot
    try {
        $client = new \GuzzleHttp\Client();
        $response = $client->get('https://www.psychologytoday.com/us/essentials', [
            'headers' => [
                'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64 ) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/123.0 Safari/537.36',
            ],
            'timeout' => 30,
        ]);
        $html = $response->getBody()->getContents();
        Log::info('Successfully fetched page with Guzzle', ['content_length' => strlen($html)]);
    } catch (\Exception $e) {
        Log::error('Failed to fetch page with Guzzle', ['error' => $e->getMessage()]);
        return;
    }

    try {
        $dom = new \DOMDocument();
        @$dom->loadHTML($html);
        $xpath = new \DOMXPath($dom);

        $newArticles = [];

        foreach ($xpath->query('//article') as $index => $node) {
            try {
                $titleNode = $xpath->query('.//h2', $node)->item(0);
                $title = $titleNode ? trim($titleNode->textContent) : null;

                $linkNode = $xpath->query('.//a', $node)->item(0);
                $url = $linkNode ? 'https://www.psychologytoday.com' . $linkNode->getAttribute('href' ) : null;

                $imgNode = $xpath->query('.//img', $node)->item(0);
                $image = $imgNode ? $imgNode->getAttribute('src') : 'https://example.com/default.jpg';

                $descNode = $xpath->query('.//p', $node )->item(0);
                $description = $descNode ? trim($descNode->textContent) : '';

                $authorNode = $xpath->query('.//span[contains(@class,"author")]', $node)->item(0);
                $author = $authorNode ? trim($authorNode->textContent) : 'Psychology Today';

                $dateNode = $xpath->query('.//span[contains(@class,"date")]', $node)->item(0);
                $published_at = $dateNode ? trim($dateNode->textContent) : date('Y-m-d');

                if ($title && $url) {
                    Log::info('Found article', ['index' => $index, 'title' => $title]);
                    $newArticles[] = [
                        'title' => $title,
                        'description' => $description,
                        'publisher_name' => $author,
                        'published_at' => $published_at,
                        'article_image' => $image,
                    ];
                }
            } catch (\Exception $e) {
                Log::error('Error processing article node', ['index' => $index, 'error' => $e->getMessage()]);
            }
        }

        Log::info('Parsed articles', ['count' => count($newArticles)]);

        if (count($newArticles) > 0) {
            try {
                // حذف المقالات القديمة
                Log::info('Truncating articles table');
                Article::truncate();
                Log::info('Articles table truncated successfully');
                
                // إضافة المقالات الجديدة
                foreach ($newArticles as $index => $article) {
                    try {
                        $result = Article::create($article);
                        Log::info('Article created', ['index' => $index, 'id' => $result->id, 'title' => $result->title]);
                    } catch (\Exception $e) {
                        Log::error('Failed to create article', [
                            'index' => $index,
                            'article' => $article,
                            'error' => $e->getMessage()
                        ]);
                    }
                }
                
                Log::info('Articles import process completed');
            } catch (\Exception $e) {
                Log::error('Failed to save articles to database', [
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
            }
        } else {
            Log::warning('No articles were parsed or available.');
        }
    } catch (\Exception $e) {
        Log::error('Error processing HTML content', [
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]);
    }
}

}
