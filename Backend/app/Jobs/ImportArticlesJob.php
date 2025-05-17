<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class ImportArticlesJob implements ShouldQueue
{
    use Queueable;

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
                $imageUrl = $channelImage;
            }

            // تحميل الصورة وتخزينها محليًا
            $imageName = null;
            Log::info('Trying to fetch image', ['url' => $imageUrl]);
            try {
                $response = \Illuminate\Support\Facades\Http::get($imageUrl);
                Log::info('Image HTTP status', ['status' => $response->status()]);
                if ($response->status() !== 200) {
                    Log::error('Image HTTP error', ['status' => $response->status(), 'url' => $imageUrl]);
                }
                $imageContents = $response->body();
                $imageName = 'articles/' . uniqid() . '.jpg';
                \Illuminate\Support\Facades\Storage::disk('public')->put($imageName, $imageContents);
                Log::info('Image saved', ['name' => $imageName]);
                if (Storage::disk('public')->exists($imageName)) {
                    Log::info('Image exists after save', ['name' => $imageName]);
                } else {
                    Log::error('Image NOT saved', ['name' => $imageName]);
                }
            } catch (\Exception $e) {
                $imageName = null;
                Log::error('Image fetch failed', ['error' => $e->getMessage(), 'url' => $imageUrl]);
            }

            $newArticles[] = [
                'title' => (string) $item->title,
                'description' => strip_tags((string) $item->description),
                'publisher_name' => isset($item->source) ? (string) $item->source : 'Google News',
                'published_at' => new \DateTime((string) $item->pubDate),
                'article_image' => $imageName,
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
        } else {
            // لا تمسح القديم إذا لم ينجح الاستيراد
            // ويمكنك إرسال إشعار أو تسجيل خطأ
        }

        // 3. لو لأي سبب زاد العدد عن 100 (مثلاً لو أضفت مقالات يدوياً)، احذف الأقدم:
        $total = \App\Models\Article::count();
        if ($total > 100) {
            $toDelete = $total - 100;
            \App\Models\Article::orderBy('created_at')->limit($toDelete)->delete();
        }
    }

}
