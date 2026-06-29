<?php

namespace App\Services;

use Elastic\Elasticsearch\Client;
use Elastic\Elasticsearch\ClientBuilder;

class ElasticsearchService
{
    protected Client $client;

    protected string $index;

    public function __construct()
    {
        $this->client = ClientBuilder::create()
            ->setHosts([
                config('elasticsearch.host')
            ])
            ->build();

        $this->index = config('elasticsearch.index');
    }

    /**
     * Get Elasticsearch client.
     */
    public function client(): Client
    {
        return $this->client;
    }

    /**
     * Get default index.
     */
    public function index(): string
    {
        return $this->index;
    }

    /**
     * Check whether index exists.
     */
    public function indexExists(?string $index = null): bool
    {
        return $this->client->indices()->exists([
            'index' => $index ?? $this->index,
        ])->asBool();
    }

    /**
     * Delete index.
     */
    public function deleteIndex(?string $index = null): bool
    {
        $index = $index ?? $this->index;

        if (!$this->indexExists($index)) {
            return true;
        }

        return $this->client->indices()->delete([
            'index' => $index,
        ])->asBool();
    }

    /**
     * Create index.
     */
    public function createIndex(array $body, ?string $index = null): bool
    {
        $index = $index ?? $this->index;

        if ($this->indexExists($index)) {
            return true;
        }

        return $this->client->indices()->create([
            'index' => $index,
            'body' => $body,
        ])->asBool();
    }

    /**
     * Index a single document.
     */
    public function indexDocument(array $document, string|int $id): bool
    {
        return $this->client->index([
            'index' => $this->index,
            'id' => $id,
            'body' => $document,
        ])->asBool();
    }

    /**
     * Bulk index documents.
     */
    public function bulkIndex(array $documents): array
    {
        return $this->client->bulk([
            'body' => $documents,
        ])->asArray();
    }

    /**
     * Search documents.
     */
    public function search(array $query): array
    {
        return $this->client->search([
            'index' => $this->index,
            'body' => $query,
        ])->asArray();
    }

    /**
     * Delete document.
     */
    public function deleteDocument(string|int $id): bool
    {
        return $this->client->delete([
            'index' => $this->index,
            'id' => $id,
        ])->asBool();
    }

    /**
     * Execute aggregation query.
     *
     * @param array $aggs
     * @param array $query
     * @return array
     */
    public function aggregate(array $aggs, array $query = []): array
    {
        $params = [
            'index' => $this->index,
            'body' => [
                'size' => 0,
                'query' => empty($query)
                    ? ['match_all' => (object) []]
                    : $query,
                'aggs' => $aggs,
            ],
        ];

        return $this->client
            ->search($params)
            ->asArray();
    }
}