<?php

namespace App\Jobs;

use App\Models\ApplicantCase;
use App\Services\DelayDocumentBuilderService;
use App\Services\ElasticsearchService;
use App\Services\ElasticsearchMappingService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class IndexDelayedCases implements ShouldQueue
{
    use Queueable;

    /**
     * The number of seconds the job can run before timing out.
     */
    public int $timeout = 3600; // 1 hour

    /**
     * The number of seconds to wait before retrying a failed job.
     */
    public int $retryAfter = 300; // 5 minutes

    /**
     * The maximum number of unhandled exceptions to allow.
     */
    public int $maxExceptions = 3;

    /**
     * Create a new job instance.
     */
    public function __construct()
    {
    }

    /**
     * Execute the job.
     */
    public function handle(
        ElasticsearchService $esService,
        DelayDocumentBuilderService $documentBuilder,
        ElasticsearchMappingService $mappingService
    ): void
    {
        echo "🚀 Starting async Elasticsearch reindex for delayed cases...\n";

        // Step 1: Delete existing index
        echo "Step 1: Deleting existing index...\n";
        $indexName = $esService->index();
        if ($esService->indexExists()) {
            $esService->deleteIndex();
            echo "✓ Index '{$indexName}' deleted\n";
        } else {
            echo "✓ Index '{$indexName}' does not exist\n";
        }

        // Step 2: Create index with mapping
        echo "Step 2: Creating index with mapping...\n";
        $mapping = $mappingService->getDelayedCasesMapping();
        $esService->createIndex($mapping);
        echo "✓ Index '{$indexName}' created with mapping\n";

        // Step 3: Read PostgreSQL and bulk index
        echo "Step 3: Reading cases from PostgreSQL and indexing...\n";
        
        $totalCases = ApplicantCase::count();
        echo "Total cases in database: {$totalCases}\n";

        $chunkSize = 100;
        $bulkDocs = [];
        $indexedCount = 0;
        $skippedCount = 0;

        ApplicantCase::chunk($chunkSize, function ($cases) use (
            $documentBuilder,
            $esService,
            &$bulkDocs,
            &$indexedCount,
            &$skippedCount,
            $chunkSize
        ) {
            foreach ($cases as $case) {
                $document = $documentBuilder->build($case);

                if ($document === null) {
                    // Case is completed, skip it
                    $skippedCount++;
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

        // Summary
        echo "Step 4: Summary\n";
        echo "✓ Indexed: {$indexedCount} delayed cases\n";
        echo "✓ Skipped: {$skippedCount} completed cases\n";
        echo "✓ Total processed: " . ($indexedCount + $skippedCount) . "\n";

        if ($indexedCount > 0) {
            echo "✅ Reindexing completed successfully!\n";
        } else {
            echo "⚠️  No delayed cases found to index\n";
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('IndexDelayedCases job failed', [
            'message' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString(),
        ]);
    }
}
