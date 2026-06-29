<?php

namespace App\Services;

/**
 * Service for managing Elasticsearch index mappings.
 */
class ElasticsearchMappingService
{
    /**
     * Get mapping for delayed cases index.
     */
    public function getDelayedCasesMapping(): array
    {
        return [
            'settings' => $this->getSettings(),
            'mappings' => [
                'properties' => $this->getProperties(),
            ],
        ];
    }

    /**
     * Get index settings.
     */
    private function getSettings(): array
    {
        return [
            'number_of_shards' => 1,
            'number_of_replicas' => 0,
            'analysis' => [
                'analyzer' => [
                    'text_analyzer' => [
                        'type' => 'standard',
                        'stopwords' => '_english_',
                    ],
                ],
            ],
        ];
    }

    /**
     * Get field properties/mappings.
     */
    private function getProperties(): array
    {
        return [
            // Case Information
            'case_uuid' => $this->keywordField(),
            'applicant_name' => $this->textFieldWithKeyword('text_analyzer'),
            'applicant_cnic' => $this->keywordField(),
            'district' => $this->textFieldWithKeyword('text_analyzer'),
            'tehsil' => $this->textFieldWithKeyword('text_analyzer'),
            'partner_name' => $this->textFieldWithKeyword('text_analyzer'),
            'bank_name' => $this->textFieldWithKeyword('text_analyzer'),
            'branch_name' => $this->textFieldWithKeyword('text_analyzer'),

            // Normalized Fields (for case-insensitive filtering)
            'district_norm' => $this->keywordField(),
            'tehsil_norm' => $this->keywordField(),
            'partner_norm' => $this->keywordField(),
            'bank_norm' => $this->keywordField(),

            // Delay Stage Information
            'stage_key' => $this->keywordField(),
            'stage_label' => $this->keywordField(),
            'stage_type' => $this->keywordField(),
            'stage_start_date' => $this->dateField('strict_date'),

            // Delay Metrics
            'days_waiting' => $this->integerField(),
            'severity' => $this->keywordField(),

            // Search Fields
            'search_blob' => $this->textField('text_analyzer'),

            // Timestamp
            'indexed_at' => $this->dateField('strict_date_time'),
        ];
    }

    /**
     * Keyword field mapping.
     */
    private function keywordField(): array
    {
        return [
            'type' => 'keyword',
        ];
    }

    /**
     * Text field with keyword subfield.
     */
    private function textFieldWithKeyword(string $analyzer): array
    {
        return [
            'type' => 'text',
            'analyzer' => $analyzer,
            'fields' => [
                'keyword' => [
                    'type' => 'keyword',
                ],
            ],
        ];
    }

    /**
     * Text field mapping.
     */
    private function textField(string $analyzer): array
    {
        return [
            'type' => 'text',
            'analyzer' => $analyzer,
        ];
    }

    /**
     * Date field mapping.
     */
    private function dateField(string $format): array
    {
        return [
            'type' => 'date',
            'format' => $format,
        ];
    }

    /**
     * Integer field mapping.
     */
    private function integerField(): array
    {
        return [
            'type' => 'integer',
        ];
    }
}
