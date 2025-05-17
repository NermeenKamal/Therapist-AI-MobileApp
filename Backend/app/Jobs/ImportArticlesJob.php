<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class TestDatabaseConnection extends Command
{
    protected $signature = 'db:test';
    protected $description = 'Test database connection and create a test record';

    public function handle()
    {
        $this->info('Testing database connection...');
        
        try {
            DB::connection()->getPdo();
            $this->info('✓ Database connection successful: ' . DB::connection()->getDatabaseName());
            
            $this->info('Testing if articles table exists...');
            if (Schema::hasTable('articles')) {
                $this->info('✓ Articles table exists');
                
                $this->info('Testing insert into articles table...');
                $id = DB::table('articles')->insertGetId([
                    'title' => 'Test Article ' . date('Y-m-d H:i:s'),
                    'description' => 'This is a test article to verify database connectivity',
                    'publisher_name' => 'Database Test Command',
                    'published_at' => now()->format('Y-m-d'),
                    'article_image' => 'https://example.com/test-image.jpg',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                
                $this->info('✓ Successfully inserted test article with ID: ' . $id);
                
                $count = DB::table('articles')->count();
                $this->info('Total articles in database: ' . $count);
            } else {
                $this->error('✗ Articles table does not exist!');
                
                $this->info('Available tables:');
                $tables = DB::select('SHOW TABLES');
                foreach ($tables as $table) {
                    $tableName = array_values(get_object_vars($table))[0];
                    $this->line(' - ' . $tableName);
                }
            }
        } catch (\Exception $e) {
            $this->error('✗ Database connection failed: ' . $e->getMessage());
        }
        
        return Command::SUCCESS;
    }
}
