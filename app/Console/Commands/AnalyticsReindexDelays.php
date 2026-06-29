<?php

namespace App\Console\Commands;

use App\Jobs\IndexDelayedCases;
use Illuminate\Console\Command;

class AnalyticsReindexDelays extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'analytics:reindex-delays {--sync : Run synchronously instead of queueing}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Queue delayed cases reindexing from PostgreSQL to Elasticsearch';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        if ($this->option('sync')) {
            // Synchronous execution (direct)
            $this->info('🚀 Running reindex synchronously...');
            IndexDelayedCases::dispatch();
            $this->info('✅ Job dispatched and executed!');
        } else {
            // Queue the job for background processing
            $this->info('📋 Queuing reindex job...');
            IndexDelayedCases::dispatch();
            $this->info('✅ Job queued successfully!');
            $this->info('Run "php artisan queue:work" to process queued jobs.');
        }

        return self::SUCCESS;
    }
}
