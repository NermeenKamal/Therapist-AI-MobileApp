<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('import:articles', function () {
    $this->info('Starting articles import...');
    dispatch(new \App\Jobs\ImportArticlesJob());
    $this->info('Articles import job has been dispatched successfully!');
})->purpose('Import articles from RSS feed');
