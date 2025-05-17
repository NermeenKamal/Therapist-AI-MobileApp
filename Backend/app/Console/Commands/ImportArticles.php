<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Jobs\ImportArticlesJob;

class ImportArticles extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'import:articles';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import articles from RSS feed';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting articles import...');
        
        try {
            dispatch(new ImportArticlesJob());
            $this->info('Articles import job has been dispatched successfully!');
        } catch (\Exception $e) {
            $this->error('Failed to import articles: ' . $e->getMessage());
        }
    }
} 
