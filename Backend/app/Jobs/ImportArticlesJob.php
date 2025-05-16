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
            \App\Models\Article::updateOrCreate(
                ['title' => (string) $item->title],
                [
                    'description' => strip_tags((string) $item->description),
                    'publisher_name' => isset($item->source) ? (string) $item->source : 'Google News',
                    'published_at' => new \DateTime((string) $item->pubDate),
                    'article_image' => null,
                ]
            );
        }
    }

}
