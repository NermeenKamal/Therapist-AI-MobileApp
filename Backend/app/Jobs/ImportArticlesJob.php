<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;

class ImportArticlesJob implements ShouldQueue
{
    use Queueable;

    private const DEFAULT_IMAGE = 'https://th.bing.com/th/id/OIP.a0NRZ33m0j4afFvhw-nvSQHaGC?cb=iwc2&rs=1&pid=ImgDetMain';

    /**
     * Create a new job instance.
     */
    public function __construct()
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle()
    {
        // التأكد من وجود المجلدات المطلوبة
        $this->ensureStorageDirectories();

        // التحقق من صحة الصورة الافتراضية
        try {
            $defaultImageResponse = Http::get(self::DEFAULT_IMAGE);
            if ($defaultImageResponse->status() !== 200) {
                Log::error('Default image is not accessible', [
                    'url' => self::DEFAULT_IMAGE,
                    'status' => $defaultImageResponse->status()
                ]);
            } else {
                Log::info('Default image is accessible');
            }
        } catch (\Exception $e) {
            Log::error('Failed to access default image', [
                'url' => self::DEFAULT_IMAGE,
                'error' => $e->getMessage()
            ]);
        }

        // 1. جلب المقالات وتخزينها مؤقتًا في مصفوفة
        $newArticles = [];
        $rssFeed = simplexml_load_file('https://news.google.com/rss/search?q=mental+health');

        // جلب صورة الـ feed العامة
        $channelImage = null;
        if (isset($rssFeed->channel->image->url)) {
            $channelImage = (string) $rssFeed->channel->image->url;
        }

        $count = 0;
        foreach ($rssFeed->channel->item as $item) {
            if ($count >= 100) break; // لا تتعدى 100 مقال

            // استخراج رابط الصورة من <image> أو <media:content> أو استخدام صورة افتراضية
            $imageUrl = null;
            $namespaces = $item->getNameSpaces(true);
            if (isset($namespaces['media'])) {
                $media = $item->children($namespaces['media']);
                if (isset($media->content)) {
                    $imageUrl = (string) $media->content->attributes()->url;
                }
            }
            if (!$imageUrl && $channelImage) {
                $imageUrl = $channelImage;
            }
            // صورة افتراضية إذا لم توجد صورة في الخبر
            if (!$imageUrl) {
                $imageUrl = self::DEFAULT_IMAGE;
            }

            // تحميل الصورة وتخزينها محليًا
            $imagePath = null;
            Log::info('Processing article image', [
                'article_title' => (string) $item->title,
                'image_url' => $imageUrl
            ]);

            try {
                $response = Http::get($imageUrl);
                Log::info('Image HTTP status', ['status' => $response->status()]);
                
                if ($response->status() !== 200) {
                    Log::error('Image HTTP error', [
                        'status' => $response->status(),
                        'url' => $imageUrl
                    ]);
                    $imagePath = self::DEFAULT_IMAGE;
                } else {
                    $imageContents = $response->body();
                    $imagePath = 'articles/' . uniqid() . '.jpg';
                    
                    // التأكد من وجود المجلد
                    if (!Storage::disk('public')->exists('articles')) {
                        Storage::disk('public')->makeDirectory('articles');
                    }
                    
                    $saved = Storage::disk('public')->put($imagePath, $imageContents);
                    
                    if ($saved) {
                        Log::info('Image saved successfully', [
                            'path' => $imagePath,
                            'size' => Storage::disk('public')->size($imagePath)
                        ]);
                        
                        // التحقق من وجود الملف بعد الحفظ
                        if (Storage::disk('public')->exists($imagePath)) {
                            Log::info('Image exists after save', ['path' => $imagePath]);
                            // تحويل المسار إلى URL قابل للوصول
                            $imagePath = Storage::disk('public')->url($imagePath);
                        } else {
                            Log::error('Image NOT saved', ['path' => $imagePath]);
                            $imagePath = self::DEFAULT_IMAGE;
                        }
                    } else {
                        Log::error('Failed to save image', ['path' => $imagePath]);
                        $imagePath = self::DEFAULT_IMAGE;
                    }
                }
            } catch (\Exception $e) {
                $imagePath = self::DEFAULT_IMAGE;
                Log::error('Image fetch failed', [
                    'error' => $e->getMessage(),
                    'url' => $imageUrl
                ]);
            }

            $newArticles[] = [
                'title' => (string) $item->title,
                'description' => strip_tags((string) $item->description),
                'publisher_name' => isset($item->source) ? (string) $item->source : 'Google News',
                'published_at' => new \DateTime((string) $item->pubDate),
                'article_image' => $imagePath,
            ];

            $count++;
        }

        // 2. إذا نجح جلب عدد كافٍ من المقالات (مثلاً >= 10)
        if (count($newArticles) > 0) {
            // امسح القديم
            \App\Models\Article::truncate();
            // أضف الجديد
            foreach ($newArticles as $data) {
                \App\Models\Article::create($data);
            }
            Log::info('Articles imported successfully', ['count' => count($newArticles)]);
        } else {
            Log::error('No articles were imported');
        }

        // 3. لو لأي سبب زاد العدد عن 100 (مثلاً لو أضفت مقالات يدوياً)، احذف الأقدم:
        $total = \App\Models\Article::count();
        if ($total > 100) {
            $toDelete = $total - 100;
            \App\Models\Article::orderBy('created_at')->limit($toDelete)->delete();
            Log::info('Deleted old articles', ['count' => $toDelete]);
        }
    }

    /**
     * التأكد من وجود المجلدات المطلوبة
     */
    private function ensureStorageDirectories()
    {
        // التأكد من وجود مجلد storage/app/public
        if (!Storage::disk('public')->exists('')) {
            Storage::disk('public')->makeDirectory('');
        }

        // التأكد من وجود مجلد articles
        if (!Storage::disk('public')->exists('articles')) {
            Storage::disk('public')->makeDirectory('articles');
        }

        // التأكد من وجود الرابط الرمزي
        if (!file_exists(public_path('storage'))) {
            try {
                \Illuminate\Support\Facades\Artisan::call('storage:link');
                Log::info('Storage link created successfully');
            } catch (\Exception $e) {
                Log::error('Failed to create storage link', [
                    'error' => $e->getMessage()
                ]);
            }
        }
    }
}
