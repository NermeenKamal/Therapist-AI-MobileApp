<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Jobs\ImportArticlesJob;

class ImportArticles extends Command
{
    protected $signature = 'import:articles';
    protected $description = 'Import articles from RSS feed';

    public function handle()
    {
        $this->info('Starting articles import...');
        
        dispatch(new ImportArticlesJob());
        
        $this->info('Articles import job has been dispatched successfully!');
    }
} 