<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Jobs\ImportArticlesJob;

class ImportArticlesCommand extends Command
{
    protected $signature = 'articles:import';
    protected $description = 'Import articles from RSS feed';

    public function handle()
    {
        $this->info('Starting articles import...');
        
        dispatch(new ImportArticlesJob());
        
        $this->info('Articles import job has been dispatched successfully!');
    }
} 