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

    private const MAX_ARTICLES = 100;
    private const MAX_AGE_DAYS = 30;
    private const DEFAULT_IMAGE = 'https://www.nimh.nih.gov/sites/default/files/images/nimh-logo.png';

    private const RSS_SOURCES = [
        [
            'url'  => 'https://www.nimh.nih.gov/site-info/index-rss.atom',
            'type' => 'atom',
            'name' => 'National Institute of Mental Health',
        ],
        [
            'url'  => 'https://www.psychologytoday.com/us/blog/feed',
            'type' => 'rss',
            'name' => 'Psychology Today',
        ],
        [
            'url'  => 'https://www.medicalnewstoday.com/category/mental-health/feed',
            'type' => 'rss',
            'name' => 'Medical News Today',
        ],
        [
            'url'  => 'https://www.verywellmind.com/rss',
            'type' => 'rss',
            'name' => 'Verywell Mind',
        ],
        [
            'url'  => 'https://psychcentral.com/feed',
            'type' => 'rss',
            'name' => 'PsychCentral',
        ],
    ];

    private const IMAGE_KEYWORDS = [
        'brain' => 'https://images.pexels.com/photos/8378740/pexels-photo-8378740.jpeg?auto=compress&cs=tinysrgb&w=1260&h=750&dpr=2',
        'memory' => 'https://images.pexels.com/photos/4495118/pexels-photo-4495118.jpeg?auto=compress&cs=tinysrgb&w=1260&h=750&dpr=2',
        'depression' => 'https://images.pexels.com/photos/6756091/pexels-photo-6756091.jpeg?auto=compress&cs=tinysrgb&w=1260&h=750&dpr=2',
        'suicide' => 'https://images.pexels.com/photos/6756086/pexels-photo-6756086.jpeg?auto=compress&cs=tinysrgb&w=1260&h=750&dpr=2',
        'mental health' => 'https://images.pexels.com/photos/3958406/pexels-photo-3958406.jpeg?auto=compress&cs=tinysrgb&w=1260&h=750&dpr=2',
        'anxiety' => 'https://images.pexels.com/photos/4101206/pexels-photo-4101206.jpeg?auto=compress&cs=tinysrgb&w=1260&h=750&dpr=2',
        'bipolar' => 'https://images.pexels.com/photos/8412813/pexels-photo-8412813.jpeg?auto=compress&cs=tinysrgb&w=1260&h=750&dpr=2',
        'psychosis' => 'https://images.pexels.com/photos/6764112/pexels-photo-6764112.jpeg?auto=compress&cs=tinysrgb&w=1260&h=750&dpr=2',
        'treatment' => 'https://images.pexels.com/photos/159211/headache-pain-pills-medication-159211.jpeg?auto=compress&cs=tinysrgb&w=1260&h=750&dpr=2',
        'research' => 'https://images.pexels.com/photos/1194775/pexels-photo-1194775.jpeg?auto=compress&cs=tinysrgb&w=1260&h=750&dpr=2',
        'therapy' => 'https://images.pexels.com/photos/5699431/pexels-photo-5699431.jpeg?auto=compress&cs=tinysrgb&w=600',
        'stress' => 'https://images.pexels.com/photos/626165/pexels-photo-626165.jpeg?auto=compress&cs=tinysrgb&w=600',
        'trauma' => 'https://images.pexels.com/photos/6502500/pexels-photo-6502500.jpeg?auto=compress&cs=tinysrgb&w=600',
        'addiction' => 'https://images.pexels.com/photos/47327/medications-money-cure-tablets-47327.jpeg?auto=compress&cs=tinysrgb&w=600',
    ];

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
        'addiction' => 'Addiction Treatment Center',
    ];

    public function handle()
    {
        Log::info('ImportArticlesJob started...');

        try {
            $testArticle = Article::create([
                'title' => 'Test Article ' . date('Y-m-d H:i:s'),
                'description' => 'This is a test article',
                'publisher_name' => 'Test',
                'published_at' => date('Y-m-d'),
                'article_image' => 'https://example.com/test.jpg',
            ]);
            Log::info('Test article created successfully', ['id' => $testArticle->id]);
            $testArticle->delete();
            Log::info('Test article deleted');
        } catch (\Exception $e) {
            Log::error('Failed to create test article', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            return;
        }

        $allArticles = [];
        foreach (self::RSS_SOURCES as $source) {
            try {
                Log::info('Fetching articles from source', ['source' => $source['name'], 'url' => $source['url']]);
                $articles = $this->fetchArticlesFromSource($source);
                $allArticles = array_merge($allArticles, $articles);
                Log::info('Articles fetched successfully', ['source' => $source['name'], 'count' => count($articles)]);
            } catch (\Exception $e) {
                Log::error('Error fetching articles', ['source' => $source['name'], 'error' => $e->getMessage()]);
            }
        }

        usort($allArticles, fn($a, $b) => strtotime($b['published_at']) - strtotime($a['published_at']));
        Log::info('Total articles fetched', ['count' => count($allArticles)]);

        $newArticlesCount = 0;

        if (count($allArticles) > 0) {
            try {
                $existingTitles = Article::pluck('title')->toArray();
                foreach ($allArticles as $index => $article) {
                    if (!in_array($article['title'], $existingTitles)) {
                        try {
                            $result = Article::create($article);
                            $newArticlesCount++;
                            Log::info('New article created', ['index' => $index, 'id' => $result->id, 'title' => $result->title]);
                        } catch (\Exception $e) {
                            Log::error('Failed to create article', ['error' => $e->getMessage()]);
                        }
                    }
                }

                if ($newArticlesCount > 0) {
                    $cutoffDate = Carbon::now()->subDays(self::MAX_AGE_DAYS);
                    $oldArticlesCount = Article::where('published_at', '<', $cutoffDate)->delete();
                    Log::info('Old articles deleted', ['count' => $oldArticlesCount]);

                    $totalArticles = Article::count();
                    if ($totalArticles > self::MAX_ARTICLES) {
                        $excess = $totalArticles - self::MAX_ARTICLES;
                        Article::orderBy('published_at', 'asc')->limit($excess)->delete();
                        Log::info('Excess articles removed', ['deleted' => $excess]);
                    }
                }

                Log::info('Import completed', ['new' => $newArticlesCount, 'total' => Article::count()]);
            } catch (\Exception $e) {
                Log::error('Error saving articles to DB', ['error' => $e->getMessage()]);
            }
        } else {
            Log::warning('No articles parsed from any source.');
        }
    }

    private function fetchArticlesFromSource(array $source): array
    {
        $articles = [];
        try {
            $client = new Client();
            $response = $client->get($source['url'], ['headers' => ['User-Agent' => 'Mozilla/5.0'], 'timeout' => 30]);
            $xml = $response->getBody()->getContents();
            $feed = new SimpleXMLElement($xml);
            if ($source['type'] === 'atom') {
                $articles = $this->parseAtomFeed($feed, $source['name']);
            } else {
                $articles = $this->parseRssFeed($feed, $source['name']);
            }
        } catch (GuzzleException $e) {
            Log::error('Failed to fetch feed', ['error' => $e->getMessage()]);
        } catch (\Exception $e) {
            Log::error('Feed processing error', ['error' => $e->getMessage()]);
        }
        return $articles;
    }

    private function parseAtomFeed(SimpleXMLElement $feed, string $sourceName): array
    {
        $articles = [];
        foreach ($feed->entry as $entry) {
            try {
                $title = (string) $entry->title;
                $description = (string) $entry->summary;
                $published_at = date('Y-m-d', strtotime((string) $entry->updated));
                $article_image = $this->selectImageForArticle($title);
                $publisher_name = $this->selectPublisherForArticle($title, $sourceName);
                $articles[] = compact('title', 'description', 'publisher_name', 'published_at', 'article_image');
            } catch (\Exception $e) {
                Log::error('Atom entry error', ['error' => $e->getMessage()]);
            }
        }
        return $articles;
    }

    private function parseRssFeed(SimpleXMLElement $feed, string $sourceName): array
    {
        $articles = [];
        $items = isset($feed->channel) ? $feed->channel->item : $feed->item;
        foreach ($items as $item) {
            try {
                $title = (string) $item->title;
                $description = strip_tags((string) ($item->description ?? $item->children('content', true)->encoded ?? ''));
                if (strlen($description) > 500) $description = substr($description, 0, 497) . '...';
                $published_at = date('Y-m-d', strtotime((string) ($item->pubDate ?? $item->children('dc', true)->date ?? date('Y-m-d'))));
                $article_image = $this->selectImageForArticle($title);
                $publisher_name = $this->selectPublisherForArticle($title, $sourceName);
                $articles[] = compact('title', 'description', 'publisher_name', 'published_at', 'article_image');
            } catch (\Exception $e) {
                Log::error('RSS item error', ['error' => $e->getMessage()]);
            }
        }
        return $articles;
    }

    private function selectImageForArticle(string $title): string
    {
        $title = strtolower($title);
        foreach (self::IMAGE_KEYWORDS as $keyword => $url) {
            if (strpos($title, $keyword) !== false) {
                return $url;
            }
        }
        $keys = array_keys(self::IMAGE_KEYWORDS);
        return self::IMAGE_KEYWORDS[$keys[crc32($title) % count($keys)]];
    }

    private function selectPublisherForArticle(string $title, string $defaultPublisher): string
    {
        $title = strtolower($title);
        foreach (self::PUBLISHER_BY_SPECIALTY as $keyword => $publisher) {
            if (strpos($title, $keyword) !== false) {
                return $publisher;
            }
        }
        return $defaultPublisher;
    }
}
