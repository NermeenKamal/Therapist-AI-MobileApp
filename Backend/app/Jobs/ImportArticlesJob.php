<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

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
            Article::updateOrCreate(
                ['title' => (string) $item->title],
                [
                    'description' => (string) $item->description,
                    'publisher_name' => 'المصدر الرسمي',
                    'published_at' => new \DateTime((string) $item->pubDate),
                    'article_image' => (string) $item->enclosure['url'] ?? null,
                ]
            );
        }
    }

}
