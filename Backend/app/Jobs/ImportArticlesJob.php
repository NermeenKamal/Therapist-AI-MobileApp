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
        Log::info('ImportArticlesJob started from Psychology Today');

        $this->ensureStorageDirectories();

        $newArticles = [];
        $html = file_get_contents('https://www.psychologytoday.com/us/essentials');
        $dom = new \DOMDocument();
        @$dom->loadHTML($html);
        $xpath = new \DOMXPath($dom);

        foreach ($xpath->query('//article') as $node) {
            $titleNode = $xpath->query('.//h2', $node)->item(0);
            $title = $titleNode ? trim($titleNode->textContent) : '';

            $linkNode = $xpath->query('.//a', $node)->item(0);
            $url = $linkNode ? 'https://www.psychologytoday.com' . $linkNode->getAttribute('href') : '';

            $imgNode = $xpath->query('.//img', $node)->item(0);
            $image = $imgNode ? $imgNode->getAttribute('src') : self::DEFAULT_IMAGE;

            $descNode = $xpath->query('.//p', $node)->item(0);
            $description = $descNode ? trim($descNode->textContent) : '';

            $authorNode = $xpath->query('.//span[contains(@class,"author")]', $node)->item(0);
            $author = $authorNode ? trim($authorNode->textContent) : 'Psychology Today';

            $dateNode = $xpath->query('.//span[contains(@class,"date")]', $node)->item(0);
            $published_at = $dateNode ? trim($dateNode->textContent) : null;

            if ($title && $url) {
                $newArticles[] = [
                    'title' => $title,
                    'description' => $description,
                    'publisher_name' => $author,
                    'published_at' => $published_at,
                    'article_image' => $image,
                ];
            }
        }

        dd($newArticles);

        // 2. إذا نجح جلب عدد كافٍ من المقالات (مثلاً >= 10)
        if (count($newArticles) > 0) {
            \App\Models\Article::truncate();
            foreach ($newArticles as $data) {
                \App\Models\Article::create($data);
            }
            \Log::info('Articles imported successfully', ['count' => count($newArticles)]);
        } else {
            \Log::error('No articles were imported');
        }

        $total = \App\Models\Article::count();
        if ($total > 100) {
            $toDelete = $total - 100;
            \App\Models\Article::orderBy('created_at')->limit($toDelete)->delete();
            \Log::info('Deleted old articles', ['count' => $toDelete]);
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
