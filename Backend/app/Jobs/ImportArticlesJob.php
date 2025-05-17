<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Storage;

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
        $rssFeed = simplexml_load_file('https://news.google.com/rss/search?q=mental+health');
        foreach ($rssFeed->channel->item as $item) {
            // استخراج رابط الصورة من <image> أو <media:content> أو استخدام صورة افتراضية
            $imageUrl = null;
            $namespaces = $item->getNameSpaces(true);
            if (isset($namespaces['media'])) {
                $media = $item->children($namespaces['media']);
                if (isset($media->content)) {
                    $imageUrl = (string) $media->content->attributes()->url;
                }
            }
            if (!$imageUrl && isset($item->image)) {
                $imageUrl = (string) $item->image->url;
            }
            // صورة افتراضية إذا لم توجد صورة في الخبر
            if (!$imageUrl) {
                $imageUrl = 'https://th.bing.com/th/id/OIP.a0NRZ33m0j4afFvhw-nvSQHaGC?cb=iwc2&rs=1&pid=ImgDetMain';
            }

            // تحميل الصورة وتخزينها محليًا
            $imageName = null;
            try {
                $imageContents = \Illuminate\Support\Facades\Http::get($imageUrl)->body();
                $imageName = 'articles/' . uniqid() . '.jpg';
                \Illuminate\Support\Facades\Storage::disk('public')->put($imageName, $imageContents);
            } catch (\Exception $e) {
                $imageName = null;
            }

            \App\Models\Article::updateOrCreate(
                ['title' => (string) $item->title],
                [
                    'description' => strip_tags((string) $item->description),
                    'publisher_name' => isset($item->source) ? (string) $item->source : 'Google News',
                    'published_at' => new \DateTime((string) $item->pubDate),
                    'article_image' => $imageName,
                ]
            );
        }
    }

}
