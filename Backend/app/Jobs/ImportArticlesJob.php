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
use Carbon\Carbon;

class ImportArticlesJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, SerializesModels;

    // الحد الأقصى لعدد المقالات المحتفظ بها
    private const MAX_ARTICLES = 100;
    
    // الحد الأقصى لعمر المقالات (بالأيام)
    private const MAX_AGE_DAYS = 30;

    private const DEFAULT_IMAGE = 'https://www.nimh.nih.gov/sites/default/files/images/nimh-logo.png';
    
    // تعريف مصادر RSS المتعددة
    private const RSS_SOURCES = [
        [
            'url' => 'https://www.nimh.nih.gov/site-info/index-rss.atom',
            'type' => 'atom',
            'name' => 'National Institute of Mental Health'
        ],
        [
            'url' => 'https://www.psychologytoday.com/us/blog/feed',
            'type' => 'rss',
            'name' => 'Psychology Today'
        ],
        [
            'url' => 'https://www.medicalnewstoday.com/category/mental-health/feed',
            'type' => 'rss',
            'name' => 'Medical News Today'
        ],
        [
            'url' => 'https://www.verywellmind.com/rss',
            'type' => 'rss',
            'name' => 'Verywell Mind'
        ],
        [
            'url' => 'https://psychcentral.com/feed',
            'type' => 'rss',
            'name' => 'PsychCentral'
        ]
    ];

    // تعريف صور متنوعة من Pexels بناءً على الكلمات المفتاحية في العنوان
    private const IMAGE_KEYWORDS = [
        'brain' => 'https://www.pexels.com/photo/an-illustration-of-a-person-s-mind-8378740/',
        'memory' => 'https://www.pexels.com/photo/woman-wearing-a-face-mask-4495118/',
        'depression' => 'https://www.pexels.com/photo/person-hands-woman-hand-6756091/',
        'suicide' => 'https://www.pexels.com/photo/light-arm-hands-people-6756086/',
        'mental health' => 'https://www.pexels.com/photo/the-words-mental-health-on-laptop-screen-3958406/',
        'anxiety' => 'https://www.pexels.com/photo/woman-in-blue-long-sleeve-shirt-holding-pen-4101206/',
        'bipolar' => 'https://www.pexels.com/photo/person-in-black-and-green-blazer-8412813/',
        'psychosis' => 'https://www.pexels.com/photo/woman-in-white-shirt-wearing-eyeglasses-6764112/',
        'treatment' => 'https://www.pexels.com/photo/close-up-photo-of-medicinal-drugs-159211/',
        'research' => 'https://www.pexels.com/photo/selective-focus-photo-of-magnifying-glass-1194775/',
        'therapy' => 'https://www.verywellmind.com/thmb/SPZXQKZFxHkgW79E8AXcPDfPrD8=/1500x0/filters:no_upscale():max_bytes(150000):strip_icc()/GettyImages-1181816240-e5bb3e3ed9d74f05be0f1e8e3ce475ad.jpg',
        'stress' => 'https://www.verywellmind.com/thmb/QcQJGmGXrE8Xz0qEd0JMvQdGUHA=/1500x0/filters:no_upscale():max_bytes(150000):strip_icc()/GettyImages-1127069581-dba3a8b7b5d44d36b91fc762a222f8c5.jpg',
        'trauma' => 'https://psychcentral.com/images/MH/trauma-ptsd.jpg',
        'addiction' => 'https://www.verywellmind.com/thmb/9-nhVYPDD5QrXZXLdJQFYzd3AAc=/1500x0/filters:no_upscale():max_bytes(150000):strip_icc()/addiction-56a6f2f75f9b58b7d0e56185.jpg'
    ];

    // تعريف أسماء ناشرين متنوعة حسب التخصص
    private const PUBLISHER_BY_SPECIALTY = [
        'brain' => 'Neuroscience Research Team',
        'memory' => 'Cognitive Psychology Experts',
        'depression' => 'Mood Disorders Specialists',
        'suicide' => 'Crisis Prevention Team',
        'mental health' => 'Mental Health Professionals',
        'anxiety' => 'Anxiety Treatment Specialists',
        'bipolar' => 'Bipolar Disorder Research Group',
        'psychosis' => 'Schizophrenia Research Unit',
        'treatment' => 'Clinical Treatment Team',
        'research' => 'Psychological Research Division',
        'therapy' => 'Therapeutic Interventions Group',
        'stress' => 'Stress Management Specialists',
        'trauma' => 'Trauma Recovery Experts',
        'addiction' => 'Addiction Treatment Center'
    ];

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
        
        $allArticles = [];
        
        // جلب المقالات من جميع المصادر
        foreach (self::RSS_SOURCES as $source) {
            try {
                Log::info('Fetching articles from source', ['source' => $source['name'], 'url' => $source['url']]);
                $articles = $this->fetchArticlesFromSource($source);
                $allArticles = array_merge($allArticles, $articles);
                Log::info('Articles fetched successfully', ['source' => $source['name'], 'count' => count($articles)]);
            } catch (\Exception $e) {
                Log::error('Error fetching articles from source', [
                    'source' => $source['name'],
                    'url' => $source['url'],
                    'error' => $e->getMessage()
                ]);
            }
        }
        
        // ترتيب المقالات حسب تاريخ النشر (الأحدث أولاً)
        usort($allArticles, function($a, $b) {
            return strtotime($b['published_at']) - strtotime($a['published_at']);
        });
        
        Log::info('Total articles fetched from all sources', ['count' => count($allArticles)]);
        
        if (count($allArticles) > 0) {
            try {
                // الحصول على عناوين المقالات الموجودة
                $existingTitles = Article::pluck('title')->toArray();
                
                // إضافة المقالات الجديدة فقط
                $newArticlesCount = 0;
                foreach ($allArticles as $index => $article) {
                    if (!in_array($article['title'], $existingTitles)) {
                        try {
                            $result = Article::create($article);
                            $newArticlesCount++;
                            Log::info('New article created', ['index' => $index, 'id' => $result->id, 'title' => $result->title]);
                        } catch (\Exception $e) {
                            Log::error('Failed to create article', [
                                'index' => $index,
                                'article' => $article,
                                'error' => $e->getMessage()
                            ]);
                        }
                    }
                }
                
                // حذف المقالات القديمة التي تجاوزت الحد الأقصى للعمر
                $cutoffDate = Carbon::now()->subDays(self::MAX_AGE_DAYS);
                $oldArticlesCount = Article::where('published_at', '<', $cutoffDate)->delete();
                Log::info('Old articles deleted', ['count' => $oldArticlesCount, 'cutoff_date' => $cutoffDate->format('Y-m-d')]);
                
                // التأكد من عدم تجاوز الحد الأقصى لعدد المقالات
                $totalArticles = Article::count();
                if ($totalArticles > self::MAX_ARTICLES) {
                    $excessCount = $totalArticles - self::MAX_ARTICLES;
                    Article::orderBy('published_at', 'asc')->limit($excessCount)->delete();
                    Log::info('Excess articles deleted to maintain limit', ['deleted_count' => $excessCount, 'max_limit' => self::MAX_ARTICLES]);
                }
                
                Log::info('Articles import process completed successfully', [
                    'new_articles' => $newArticlesCount,
                    'total_articles' => Article::count(),
                    'max_limit' => self::MAX_ARTICLES
                ]);
            } catch (\Exception $e) {
                Log::error('Failed to save articles to database', [
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
            }
        } else {
            Log::warning('No articles were parsed or available from any source.');
        }
    }
    
    /**
     * جلب المقالات من مصدر محدد
     *
     * @param array $source
     * @return array
     */
    private function fetchArticlesFromSource(array $source): array
    {
        $articles = [];
        
        try {
            // استخدام Guzzle لجلب محتوى RSS feed
            $client = new Client();
            $response = $client->get($source['url'], [
                'headers' => [
                    'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/123.0 Safari/537.36',
                ],
                'timeout' => 30,
            ]);
            $xml = $response->getBody()->getContents();
            
            // تحليل محتوى XML
            $feed = new SimpleXMLElement($xml);
            
            // معالجة مختلفة حسب نوع الـ feed (RSS أو Atom)
            if ($source['type'] === 'atom') {
                $articles = $this->parseAtomFeed($feed, $source['name']);
            } else {
                $articles = $this->parseRssFeed($feed, $source['name']);
            }
            
        } catch (GuzzleException $e) {
            Log::error('Failed to fetch RSS feed', ['source' => $source['name'], 'error' => $e->getMessage()]);
        } catch (\Exception $e) {
            Log::error('Error processing RSS feed', ['source' => $source['name'], 'error' => $e->getMessage()]);
        }
        
        return $articles;
    }
    
    /**
     * تحليل محتوى Atom feed
     *
     * @param SimpleXMLElement $feed
     * @param string $sourceName
     * @return array
     */
    private function parseAtomFeed(SimpleXMLElement $feed, string $sourceName): array
    {
        $articles = [];
        
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
                
                // اختيار صورة وناشر بناءً على محتوى المقالة
                $image = $this->selectImageForArticle($title);
                $publisher = $this->selectPublisherForArticle($title, $sourceName);
                
                $articles[] = [
                    'title' => $title,
                    'description' => $description,
                    'publisher_name' => $publisher,
                    'published_at' => $published_at,
                    'article_image' => $image,
                ];
            } catch (\Exception $e) {
                Log::error('Error processing Atom entry', ['error' => $e->getMessage()]);
            }
        }
        
        return $articles;
    }
    
    /**
     * تحليل محتوى RSS feed
     *
     * @param SimpleXMLElement $feed
     * @param string $sourceName
     * @return array
     */
    private function parseRssFeed(SimpleXMLElement $feed, string $sourceName): array
    {
        $articles = [];
        
        // التعامل مع مختلف هياكل RSS
        $items = isset($feed->channel) ? $feed->channel->item : $feed->item;
        
        foreach ($items as $item) {
            try {
                $title = (string)$item->title;
                
                // استخراج الوصف (قد يكون في عناصر مختلفة)
                $description = isset($item->description) ? (string)$item->description : '';
                if (empty($description) && isset($item->content)) {
                    $description = (string)$item->content;
                }
                if (empty($description) && isset($item->children('content', true)->encoded)) {
                    $description = (string)$item->children('content', true)->encoded;
                }
                
                // تنظيف الوصف من HTML tags
                $description = strip_tags($description);
                
                // اقتصار الوصف على 500 حرف
                if (strlen($description) > 500) {
                    $description = substr($description, 0, 497) . '...';
                }
                
                // استخراج الرابط
                $link = (string)$item->link;
                
                // استخراج تاريخ النشر
                $published_at = date('Y-m-d');
                if (isset($item->pubDate)) {
                    $published_at = date('Y-m-d', strtotime((string)$item->pubDate));
                } elseif (isset($item->children('dc', true)->date)) {
                    $published_at = date('Y-m-d', strtotime((string)$item->children('dc', true)->date));
                }
                
                // اختيار صورة وناشر بناءً على محتوى المقالة
                $image = $this->selectImageForArticle($title);
                $publisher = $this->selectPublisherForArticle($title, $sourceName);
                
                $articles[] = [
                    'title' => $title,
                    'description' => $description,
                    'publisher_name' => $publisher,
                    'published_at' => $published_at,
                    'article_image' => $image,
                ];
            } catch (\Exception $e) {
                Log::error('Error processing RSS item', ['error' => $e->getMessage()]);
            }
        }
        
        return $articles;
    }
    
    /**
     * اختيار صورة مناسبة للمقالة بناءً على العنوان
     *
     * @param string $title
     * @return string
     */
    private function selectImageForArticle(string $title): string
    {
        $title = strtolower($title);
        
        foreach (self::IMAGE_KEYWORDS as $keyword => $image) {
            if (stripos($title, $keyword) !== false) {
                return $image;
            }
        }
        
        // اختيار صورة عشوائية إذا لم يتم العثور على كلمة مفتاحية
        $randomIndex = crc32($title) % count(self::IMAGE_KEYWORDS);
        $randomKeyword = array_keys(self::IMAGE_KEYWORDS)[$randomIndex];
        
        return self::IMAGE_KEYWORDS[$randomKeyword];
    }
    
    /**
     * اختيار ناشر مناسب للمقالة بناءً على العنوان
     *
     * @param string $title
     * @param string $defaultPublisher
     * @return string
     */
    private function selectPublisherForArticle(string $title, string $defaultPublisher): string
    {
        $title = strtolower($title);
        
        foreach (self::PUBLISHER_BY_SPECIALTY as $keyword => $publisher) {
            if (stripos($title, $keyword) !== false) {
                return $publisher;
            }
        }
        
(Content truncated due to size limit. Use line ranges to read in chunks)
