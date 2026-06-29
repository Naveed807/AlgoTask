<?php

namespace App\Services;

use Elastic\Elasticsearch\Client;
use Elastic\Elasticsearch\ClientBuilder;
use GuzzleHttp\Client as GuzzleClient;

class ElasticsearchService
{
    protected ?Client $client;
    protected GuzzleClient $guzzleClient;
    protected string $baseUrl;
    protected string $index;

    public function __construct()
    {
        // Get base URL and index from config
        $hosts = config('elasticsearch.hosts', ['http://localhost:9200']);
        $this->baseUrl = rtrim($hosts[0], '/');
        $this->index = config('elasticsearch.index', 'delay_cases');

        // Initialize Guzzle for direct HTTP requests (to bypass version header issue)
        $this->guzzleClient = new GuzzleClient([
            'base_uri' => $this->baseUrl,
            'http_errors' => false,
        ]);

        // Try to initialize the Elasticsearch client
        try {
            $this->client = ClientBuilder::create()
                ->setHosts($hosts)
                ->build();
        } catch (\Exception $e) {
            // If client initialization fails, we'll use Guzzle directly
            $this->client = null;
        }
    }

    /**
     * Get Elasticsearch client.
     */
    public function client(): ?Client
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
        $index = $index ?? $this->index;
        
        try {
            if ($this->client) {
                return $this->client->indices()->exists([
                    'index' => $index,
                ])->asBool();
            }
        } catch (\Exception $e) {
            // Fall back to Guzzle
        }

        // Use Guzzle directly
        $response = $this->guzzleClient->head("/{$index}");
        return $response->getStatusCode() === 200;
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

        try {
            if ($this->client) {
                return $this->client->indices()->delete([
                    'index' => $index,
                ])->asBool();
            }
        } catch (\Exception $e) {
            // Fall back to Guzzle
        }

        // Use Guzzle directly
        $response = $this->guzzleClient->delete("/{$index}");
        return $response->getStatusCode() >= 200 && $response->getStatusCode() < 300;
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

        try {
            if ($this->client) {
                return $this->client->indices()->create([
                    'index' => $index,
                    'body' => $body,
                ])->asBool();
            }
        } catch (\Exception $e) {
            // Fall back to Guzzle
        }

        // Use Guzzle directly
        $response = $this->guzzleClient->put("/{$index}", [
            'json' => $body,
        ]);
        
        return $response->getStatusCode() >= 200 && $response->getStatusCode() < 300;
    }

    /**
     * Index a single document.
     */
    public function indexDocument(array $document, string|int $id): bool
    {
        try {
            if ($this->client) {
                return $this->client->index([
                    'index' => $this->index,
                    'id' => $id,
                    'body' => $document,
                ])->asBool();
            }
        } catch (\Exception $e) {
            // Fall back to Guzzle
        }

        // Use Guzzle directly
        $response = $this->guzzleClient->put("/{$this->index}/_doc/{$id}", [
            'json' => $document,
        ]);
        
        return $response->getStatusCode() >= 200 && $response->getStatusCode() < 300;
    }

    /**
     * Bulk index documents.
     */
    public function bulkIndex(array $documents): array
    {
        try {
            if ($this->client) {
                return $this->client->bulk([
                    'body' => $documents,
                ])->asArray();
            }
        } catch (\Exception $e) {
            // Fall back to Guzzle
        }

        // Use Guzzle directly - format as NDJSON
        $body = '';
        foreach ($documents as $doc) {
            $body .= json_encode($doc) . "\n";
        }

        $response = $this->guzzleClient->post('/_bulk', [
            'body' => $body,
            'headers' => ['Content-Type' => 'application/x-ndjson'],
        ]);

        $result = json_decode($response->getBody(), true) ?? [];
        return $result;
    }

    /**
     * Search documents.
     */
    public function search(array $query): array
    {
        try {
            if ($this->client) {
                return $this->client->search([
                    'index' => $this->index,
                    'body' => $query,
                ])->asArray();
            }
        } catch (\Exception $e) {
            // Fall back to Guzzle
        }

        // Use Guzzle directly
        $response = $this->guzzleClient->post("/{$this->index}/_search", [
            'json' => $query,
        ]);

        return json_decode($response->getBody(), true) ?? [];
    }

    /**
     * Delete document.
     */
    public function deleteDocument(string|int $id): bool
    {
        try {
            if ($this->client) {
                return $this->client->delete([
                    'index' => $this->index,
                    'id' => $id,
                ])->asBool();
            }
        } catch (\Exception $e) {
            // Fall back to Guzzle
        }

        // Use Guzzle directly
        $response = $this->guzzleClient->delete("/{$this->index}/_doc/{$id}");
        
        return $response->getStatusCode() >= 200 && $response->getStatusCode() < 300;
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
        $body = [
            'size' => 0,
            'query' => empty($query)
                ? ['match_all' => (object) []]
                : $query,
            'aggs' => $aggs,
        ];

        try {
            if ($this->client) {
                return $this->client->search([
                    'index' => $this->index,
                    'body' => $body,
                ])->asArray();
            }
        } catch (\Exception $e) {
            // Fall back to Guzzle
        }

        // Use Guzzle directly
        $response = $this->guzzleClient->post("/{$this->index}/_search", [
            'json' => $body,
        ]);

        return json_decode($response->getBody(), true) ?? [];
    }
}