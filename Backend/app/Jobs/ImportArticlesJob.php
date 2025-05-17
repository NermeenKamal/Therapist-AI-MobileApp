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
            ]);
            
            Log::info('Test article created successfully', ['id' => $testArticle->id]);
        } catch (\Exception $e) {
            Log::error('Failed to create test article', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
        
        // استخدام Guzzle بدلاً من Browsershot
        try {
            $client = new Client();
            $response = $client->get('https://www.psychologytoday.com/us/essentials', [
                'headers' => [
                    'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/123.0 Safari/537.36',
                ],
                'timeout' => 30,
            ]);
            $html = $response->getBody()->getContents();
            Log::info('Successfully fetched page with Guzzle', ['content_length' => strlen($html)]);
            
            // طباعة عينة من HTML المستخرج
            Log::info('HTML content sample', ['sample' => substr($html, 0, 1000)]);
            
        } catch (GuzzleException $e) {
            Log::error('Failed to fetch page with Guzzle', ['error' => $e->getMessage()]);
            return;
        } catch (\Exception $e) {
            Log::error('Unexpected error when fetching page', ['error' => $e->getMessage()]);
            return;
        }

        try {
            $dom = new \DOMDocument();
            @$dom->loadHTML($html);
            $xpath = new \DOMXPath($dom);
            
            // تجربة استعلامات XPath مختلفة
            $articleNodes1 = $xpath->query('//article');
            Log::info('Found article nodes with //article', ['count' => $articleNodes1->length]);
            
            $articleNodes2 = $xpath->query('//div[contains(@class, "card")]');
            Log::info('Found article nodes with //div[contains(@class, "card")]', ['count' => $articleNodes2->length]);
            
            $articleNodes3 = $xpath->query('//div[contains(@class, "article")]');
            Log::info('Found article nodes with //div[contains(@class, "article")]', ['count' => $articleNodes3->length]);
            
            $articleNodes4 = $xpath->query('//div[contains(@class, "post")]');
            Log::info('Found article nodes with //div[contains(@class, "post")]', ['count' => $articleNodes4->length]);
            
            $articleNodes5 = $xpath->query('//div[contains(@class, "essentials-card")]');
            Log::info('Found article nodes with //div[contains(@class, "essentials-card")]', ['count' => $articleNodes5->length]);
            
            // استخدام الاستعلام الذي يعطي أكبر عدد من النتائج
            $articleNodes = $articleNodes1;
            $queryUsed = '//article';
            
            if ($articleNodes2->length > $articleNodes->length) {
                $articleNodes = $articleNodes2;
                $queryUsed = '//div[contains(@class, "card")]';
            }
            
            if ($articleNodes3->length > $articleNodes->length) {
                $articleNodes = $articleNodes3;
                $queryUsed = '//div[contains(@class, "article")]';
            }
            
            if ($articleNodes4->length > $articleNodes->length) {
                $articleNodes = $articleNodes4;
                $queryUsed = '//div[contains(@class, "post")]';
            }
            
            if ($articleNodes5->length > $articleNodes->length) {
                $articleNodes = $articleNodes5;
                $queryUsed = '//div[contains(@class, "essentials-card")]';
            }
            
            Log::info('Using XPath query', ['query' => $queryUsed, 'count' => $articleNodes->length]);

            $newArticles = [];

            foreach ($articleNodes as $index => $node) {
                try {
                    // تجربة استعلامات مختلفة للعنوان
                    $titleNode = $xpath->query('.//h2', $node)->item(0);
                    if (!$titleNode) {
                        $titleNode = $xpath->query('.//h3', $node)->item(0);
                    }
                    if (!$titleNode) {
                        $titleNode = $xpath->query('.//h4', $node)->item(0);
                    }
                    if (!$titleNode) {
                        $titleNode = $xpath->query('.//*[contains(@class, "title")]', $node)->item(0);
                    }
                    
                    $title = $titleNode ? trim($titleNode->textContent) : null;
                    
                    // تجربة استعلامات مختلفة للرابط
                    $linkNode = $xpath->query('.//a', $node)->item(0);
                    $url = $linkNode ? 'https://www.psychologytoday.com' . $linkNode->getAttribute('href') : null;
                    
                    // تجربة استعلامات مختلفة للصورة
                    $imgNode = $xpath->query('.//img', $node)->item(0);
                    $image = $imgNode ? $imgNode->getAttribute('src') : self::DEFAULT_IMAGE;
                    
                    // تجربة استعلامات مختلفة للوصف
                    $descNode = $xpath->query('.//p', $node)->item(0);
                    if (!$descNode) {
                        $descNode = $xpath->query('.//*[contains(@class, "description")]', $node)->item(0);
                    }
                    if (!$descNode) {
                        $descNode = $xpath->query('.//*[contains(@class, "excerpt")]', $node)->item(0);
                    }
                    
                    $description = $descNode ? trim($descNode->textContent) : '';
                    
                    // تجربة استعلامات مختلفة للكاتب
                    $authorNode = $xpath->query('.//span[contains(@class,"author")]', $node)->item(0);
                    if (!$authorNode) {
                        $authorNode = $xpath->query('.//*[contains(@class, "author")]', $node)->item(0);
                    }
                    if (!$authorNode) {
                        $authorNode = $xpath->query('.//*[contains(@class, "byline")]', $node)->item(0);
                    }
                    
                    $author = $authorNode ? trim($authorNode->textContent) : 'Psychology Today';
                    
                    // تجربة استعلامات مختلفة للتاريخ
                    $dateNode = $xpath->query('.//span[contains(@class,"date")]', $node)->item(0);
                    if (!$dateNode) {
                        $dateNode = $xpath->query('.//*[contains(@class, "date")]', $node)->item(0);
                    }
                    if (!$dateNode) {
                        $dateNode = $xpath->query('.//*[contains(@class, "time")]', $node)->item(0);
                    }
                    
                    $published_at = $dateNode ? trim($dateNode->textContent) : date('Y-m-d');

                    if ($title) {
                        Log::info('Found article', [
                            'index' => $index, 
                            'title' => $title,
                            'url' => $url,
                            'description_length' => strlen($description),
                            'author' => $author,
                            'published_at' => $published_at
                        ]);
                        
                        $newArticles[] = [
                            'title' => $title,
                            'description' => $description ?: 'No description available',
                            'publisher_name' => $author,
                            'published_at' => $published_at,
                            'article_image' => $image,
                        ];
                    } else {
                        Log::warning('Skipping article node - no title found', ['index' => $index]);
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
