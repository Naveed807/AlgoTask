<?php

namespace App\Services;

class DashboardService
{
    public function __construct(
        protected ElasticsearchService $elasticsearchService
    ) {
    }

    /**
     * Get complete dashboard data.
     */
    public function getDashboard(array $filters = []): array
    {
        return [
            'kpis' => $this->getKPIs($filters),
            'charts' => $this->getCharts($filters),
            'delayed_cases' => $this->getDelayedCases($filters),
            'filter_options' => $this->getFilterOptions(),
        ];
    }

    /**
     * Get KPI metrics.
     */
    public function getKPIs(array $filters = []): array
    {
        $query = $this->buildQuery($filters);

        // Get total and severity distribution
        $response = $this->elasticsearchService->search([
            'size' => 0,
            'query' => $query,
            'aggs' => [
                'total_cases' => ['value_count' => ['field' => 'case_uuid']],
                'severity_counts' => [
                    'terms' => [
                        'field' => 'severity',
                        'size' => 10,
                    ],
                ],
            ],
        ]);

        $total = $response['aggregations']['total_cases']['value'] ?? 0;
        $severityBuckets = $response['aggregations']['severity_counts']['buckets'] ?? [];

        $severityCounts = [
            'total' => $total,
            'green' => 0,
            'yellow' => 0,
            'amber' => 0,
            'red' => 0,
        ];

        foreach ($severityBuckets as $bucket) {
            $severityCounts[strtolower($bucket['key'])] = $bucket['doc_count'];
        }

        return $severityCounts;
    }

    /**
     * Get chart data for dashboard visualizations.
     */
    public function getCharts(array $filters = []): array
    {
        $query = $this->buildQuery($filters);

        // Get aggregations for charts
        $response = $this->elasticsearchService->search([
            'size' => 0,
            'query' => $query,
            'aggs' => [
                'severity_distribution' => [
                    'terms' => [
                        'field' => 'severity',
                        'size' => 10,
                    ],
                ],
                'stage_distribution' => [
                    'terms' => [
                        'field' => 'stage_label',
                        'size' => 10,
                    ],
                ],
                'district_distribution' => [
                    'terms' => [
                        'field' => 'district_norm',
                        'size' => 20,
                    ],
                ],
            ],
        ]);

        $aggs = $response['aggregations'] ?? [];

        return [
            'severity_distribution' => $this->formatBucketsForChart(
                $aggs['severity_distribution']['buckets'] ?? [],
                'Severity'
            ),
            'stage_distribution' => $this->formatBucketsForChart(
                $aggs['stage_distribution']['buckets'] ?? [],
                'Stage'
            ),
            'district_distribution' => $this->formatBucketsForChart(
                $aggs['district_distribution']['buckets'] ?? [],
                'District'
            ),
        ];
    }

    /**
     * Get paginated delayed cases for table.
     */
    public function getDelayedCases(array $filters = [], int $page = 1, int $perPage = 25): array
    {
        $query = $this->buildQuery($filters);

        $from = ($page - 1) * $perPage;

        // Get sort field and direction
        $sortField = $filters['sort_by'] ?? 'stage_start_date';
        $sortOrder = $filters['sort_order'] ?? 'asc';

        // Map UI sort fields to Elasticsearch fields
        $sortMapping = [
            'days_waiting' => 'days_waiting',
            'stage' => 'stage_label',
            'severity' => 'severity',
            'date' => 'stage_start_date',
        ];

        $sortField = $sortMapping[$sortField] ?? 'stage_start_date';

        $response = $this->elasticsearchService->search([
            'from' => $from,
            'size' => $perPage,
            'query' => $query,
            'sort' => [
                [
                    $sortField => [
                        'order' => $sortOrder,
                        'unmapped_type' => 'long',
                    ],
                ],
            ],
            '_source' => [
                'case_uuid',
                'applicant_name',
                'applicant_cnic',
                'district',
                'partner_name',
                'stage_label',
                'days_waiting',
                'severity',
                'stage_start_date',
            ],
        ]);

        $hits = $response['hits'] ?? [];

        return [
            'data' => array_map(function ($hit) {
                return array_merge(
                    ['id' => $hit['_id']],
                    $hit['_source']
                );
            }, $hits['hits'] ?? []),
            'total' => $hits['total']['value'] ?? 0,
            'page' => $page,
            'per_page' => $perPage,
            'total_pages' => ceil(($hits['total']['value'] ?? 0) / $perPage),
        ];
    }

    /**
     * Get filter options for the dashboard filters.
     */
    public function getFilterOptions(): array
    {
        // Get all unique values for filter dropdowns
        $response = $this->elasticsearchService->search([
            'size' => 0,
            'aggs' => [
                'districts' => [
                    'terms' => [
                        'field' => 'district_norm',
                        'size' => 100,
                    ],
                ],
                'tehsils' => [
                    'terms' => [
                        'field' => 'tehsil_norm',
                        'size' => 200,
                    ],
                ],
                'partners' => [
                    'terms' => [
                        'field' => 'partner_norm',
                        'size' => 100,
                    ],
                ],
                'banks' => [
                    'terms' => [
                        'field' => 'bank_norm',
                        'size' => 100,
                    ],
                ],
                'severities' => [
                    'terms' => [
                        'field' => 'severity',
                        'size' => 10,
                    ],
                ],
                'stages' => [
                    'terms' => [
                        'field' => 'stage_label',
                        'size' => 10,
                    ],
                ],
            ],
        ]);

        $aggs = $response['aggregations'] ?? [];

        return [
            'districts' => $this->extractBucketNames($aggs['districts']['buckets'] ?? []),
            'tehsils' => $this->extractBucketNames($aggs['tehsils']['buckets'] ?? []),
            'partners' => $this->extractBucketNames($aggs['partners']['buckets'] ?? []),
            'banks' => $this->extractBucketNames($aggs['banks']['buckets'] ?? []),
            'severities' => $this->extractBucketNames($aggs['severities']['buckets'] ?? []),
            'stages' => $this->extractBucketNames($aggs['stages']['buckets'] ?? []),
        ];
    }

    /**
     * Search cases by name, CNIC, or UUID.
     */
    public function search(string $term, int $page = 1, int $perPage = 25): array
    {
        $from = ($page - 1) * $perPage;

        // Use search_blob for full-text search
        $response = $this->elasticsearchService->search([
            'from' => $from,
            'size' => $perPage,
            'query' => [
                'multi_match' => [
                    'query' => $term,
                    'fields' => [
                        'search_blob^2',
                        'case_uuid^3',
                        'applicant_cnic^2',
                    ],
                ],
            ],
            '_source' => [
                'case_uuid',
                'applicant_name',
                'applicant_cnic',
                'district',
                'partner_name',
                'stage_label',
                'days_waiting',
                'severity',
            ],
        ]);

        $hits = $response['hits'] ?? [];

        return [
            'data' => array_map(function ($hit) {
                return array_merge(
                    ['id' => $hit['_id']],
                    $hit['_source']
                );
            }, $hits['hits'] ?? []),
            'total' => $hits['total']['value'] ?? 0,
            'page' => $page,
            'per_page' => $perPage,
        ];
    }

    /**
     * Build Elasticsearch query from filters.
     */
    private function buildQuery(array $filters = []): array
    {
        $must = [];

        if (!empty($filters['district'])) {
            $must[] = ['term' => ['district_norm' => strtolower($filters['district'])]];
        }

        if (!empty($filters['tehsil'])) {
            $must[] = ['term' => ['tehsil_norm' => strtolower($filters['tehsil'])]];
        }

        if (!empty($filters['partner'])) {
            $must[] = ['term' => ['partner_norm' => strtolower($filters['partner'])]];
        }

        if (!empty($filters['bank'])) {
            $must[] = ['term' => ['bank_norm' => strtolower($filters['bank'])]];
        }

        if (!empty($filters['severity'])) {
            $must[] = ['term' => ['severity' => strtolower($filters['severity'])]];
        }

        if (!empty($filters['stage'])) {
            $must[] = ['term' => ['stage_label' => $filters['stage']]];
        }

        if (empty($must)) {
            return ['match_all' => (object) []];
        }

        return ['bool' => ['must' => $must]];
    }

    /**
     * Format aggregation buckets for chart display.
     */
    private function formatBucketsForChart(array $buckets, string $label): array
    {
        return [
            'label' => $label,
            'labels' => array_map(fn ($bucket) => $bucket['key'], $buckets),
            'data' => array_map(fn ($bucket) => $bucket['doc_count'], $buckets),
        ];
    }

    /**
     * Extract bucket names from aggregation response.
     */
    private function extractBucketNames(array $buckets): array
    {
        return array_map(fn ($bucket) => $bucket['key'], $buckets);
    }
}
