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
use SimpleXMLElement;

class ImportArticlesJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, SerializesModels;

    private const DEFAULT_IMAGE = 'https://www.nimh.nih.gov/sites/default/files/images/nimh-logo.png';
    private const RSS_FEED_URL = 'https://www.nimh.nih.gov/site-info/index-rss.atom';

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
            ]);
            
            Log::info('Test article created successfully', ['id' => $testArticle->id]);
            
            // حذف المقالة الاختبارية بعد التأكد من نجاح الاتصال
            $testArticle->delete();
            Log::info('Test article deleted');
        } catch (\Exception $e) {
            Log::error('Failed to create test article', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return;
        }
        
        // استخدام Guzzle لجلب محتوى RSS feed
        try {
            $client = new Client();
            $response = $client->get(self::RSS_FEED_URL, [
                'headers' => [
                    'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/123.0 Safari/537.36',
                ],
                'timeout' => 30,
            ]);
            $xml = $response->getBody()->getContents();
            Log::info('Successfully fetched RSS feed', ['content_length' => strlen($xml)]);
        } catch (GuzzleException $e) {
            Log::error('Failed to fetch RSS feed', ['error' => $e->getMessage()]);
            return;
        } catch (\Exception $e) {
            Log::error('Unexpected error when fetching RSS feed', ['error' => $e->getMessage()]);
            return;
        }

        try {
            // تحليل محتوى XML
            $feed = new SimpleXMLElement($xml);
            $namespace = $feed->getNamespaces(true);
            
            Log::info('RSS feed parsed successfully', ['title' => (string)$feed->title, 'entries' => count($feed->entry)]);
            
            $newArticles = [];
            
            // تعريف صور متنوعة بناءً على الكلمات المفتاحية في العنوان
            $imageKeywords = [
                'brain' => 'https://www.nimh.nih.gov/sites/default/files/images/brain-research.jpg',
                'memory' => 'https://www.nimh.nih.gov/sites/default/files/images/memory-research.jpg',
                'depression' => 'https://www.nimh.nih.gov/sites/default/files/images/depression.jpg',
                'suicide' => 'https://www.nimh.nih.gov/sites/default/files/images/suicide-prevention.jpg',
                'mental health' => 'https://www.nimh.nih.gov/sites/default/files/images/mental-health.jpg',
                'anxiety' => 'https://www.nimh.nih.gov/sites/default/files/images/anxiety.jpg',
                'bipolar' => 'https://www.nimh.nih.gov/sites/default/files/images/bipolar.jpg',
                'psychosis' => 'https://www.nimh.nih.gov/sites/default/files/images/psychosis.jpg',
                'treatment' => 'https://www.nimh.nih.gov/sites/default/files/images/treatment.jpg',
                'research' => 'https://www.nimh.nih.gov/sites/default/files/images/research.jpg',
            ];
            
            // تعريف أسماء ناشرين متنوعة
            $publisherOptions = [
                'NIMH Research Team',
                'Mental Health Experts',
                'NIMH Editorial Staff',
                'Psychology Research Group',
                'Mental Health Specialists',
                'NIMH Science Division',
                'Behavioral Health Researchers',
                'Clinical Psychology Team',
                'Psychiatric Research Unit',
                'Mental Wellness Foundation'
            ];
            
            // استخراج المقالات من الـ feed
            foreach ($feed->entry as $entry) {
                try {
                    $title = (string)$entry->title;
                    $description = (string)$entry->summary;
                    $link = '';
                    
                    // استخراج الرابط
                    foreach ($entry->link as $linkElement) {
                        $attributes = $linkElement->attributes();
                        if (isset($attributes['rel']) && (string)$attributes['rel'] === 'alternate') {
                            $link = (string)$attributes['href'];
                            break;
                        }
                    }
                    
                    // استخراج تاريخ النشر
                    $published_at = date('Y-m-d', strtotime((string)$entry->updated));
                    
                    // اختيار صورة بناءً على الكلمات المفتاحية في العنوان
                    $image = self::DEFAULT_IMAGE;
                    foreach ($imageKeywords as $keyword => $img) {
                        if (stripos($title, $keyword) !== false) {
                            $image = $img;
                            break;
                        }
                    }
                    
                    // اختيار ناشر بناءً على hash من عنوان المقالة للحصول على نتيجة ثابتة لنفس المقالة
                    $publisherIndex = crc32($title) % count($publisherOptions);
                    $publisher_name = $publisherOptions[$publisherIndex];
                    
                    Log::info('Found article', [
                        'title' => $title,
                        'link' => $link,
                        'published_at' => $published_at,
                        'publisher' => $publisher_name,
                        'image' => $image
                    ]);
                    
                    $newArticles[] = [
                        'title' => $title,
                        'description' => $description,
                        'publisher_name' => $publisher_name,
                        'published_at' => $published_at,
                        'article_image' => $image,
                    ];
                } catch (\Exception $e) {
                    Log::error('Error processing RSS entry', ['error' => $e->getMessage()]);
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
                    
                    Log::info('Articles import process completed successfully', ['total_articles' => count($newArticles)]);
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
            Log::error('Error processing RSS feed content', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }
}
