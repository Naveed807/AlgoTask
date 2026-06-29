<?php

namespace App\Console\Commands;

use App\Models\ApplicantCase;
use App\Services\DelayDocumentBuilderService;
use App\Services\ElasticsearchService;
use App\Services\ElasticsearchMappingService;
use Illuminate\Console\Command;

class AnalyticsReindexDelays extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'analytics:reindex-delays';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Reindex all delayed cases from PostgreSQL to Elasticsearch with mapping';

    /**
     * Execute the console command.
     */
    public function handle(
        ElasticsearchService $esService,
        DelayDocumentBuilderService $documentBuilder,
        ElasticsearchMappingService $mappingService
    ) {
        $this->info('🚀 Starting Elasticsearch reindex for delayed cases...');

        // Step 1: Delete existing index
        $this->info('Step 1: Deleting existing index...');
        $indexName = $esService->index();
        if ($esService->indexExists()) {
            $esService->deleteIndex();
            $this->line("✓ Index '{$indexName}' deleted");
        } else {
            $this->line("✓ Index '{$indexName}' does not exist");
        }

        // Step 2: Create index with mapping
        $this->info('Step 2: Creating index with mapping...');
        $mapping = $mappingService->getDelayedCasesMapping();
        $esService->createIndex($mapping);
        $this->line("✓ Index '{$indexName}' created with mapping");

        // Step 3: Read PostgreSQL and bulk index
        $this->info('Step 3: Reading cases from PostgreSQL and indexing...');
        
        $totalCases = ApplicantCase::count();
        $this->info("Total cases in database: {$totalCases}");

        $chunkSize = 100;
        $bulkDocs = [];
        $indexedCount = 0;
        $skippedCount = 0;

        $progressBar = $this->output->createProgressBar($totalCases);

        ApplicantCase::chunk($chunkSize, function ($cases) use (
            $documentBuilder,
            $esService,
            &$bulkDocs,
            &$indexedCount,
            &$skippedCount,
            &$progressBar,
            $chunkSize
        ) {
            foreach ($cases as $case) {
                $document = $documentBuilder->build($case);

                if ($document === null) {
                    // Case is completed, skip it
                    $skippedCount++;
                    $progressBar->advance();
                    continue;
                }

                // Prepare bulk index operation
                $bulkDocs[] = [
                    'index' => [
                        '_index' => $esService->index(),
                        '_id' => $case->id,
                    ]
                ];
                $bulkDocs[] = $document;

                $indexedCount++;
                $progressBar->advance();

                // If bulk docs reach 200 operations (100 documents), index them
                if (count($bulkDocs) >= 200) {
                    $result = $esService->bulkIndex($bulkDocs);
                    $bulkDocs = []; // Reset for next batch
                }
            }

            // Index remaining documents
            if (!empty($bulkDocs)) {
                $result = $esService->bulkIndex($bulkDocs);
                $bulkDocs = [];
            }
        });

        $progressBar->finish();
        $this->newLine();

        // Summary
        $this->info('Step 4: Summary');
        $this->line("✓ Indexed: <fg=green>{$indexedCount}</> delayed cases");
        $this->line("✓ Skipped: <fg=yellow>{$skippedCount}</> completed cases");
        $this->line("✓ Total processed: <fg=blue>" . ($indexedCount + $skippedCount) . "</>");

        if ($indexedCount > 0) {
            $this->info("✅ Reindexing completed successfully!");
        } else {
            $this->warn("⚠️  No delayed cases found to index");
        }
    }
}
