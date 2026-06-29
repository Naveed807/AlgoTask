<?php

namespace App\Services;

use App\Models\ApplicantCase;
use Illuminate\Support\Str;

class DelayDocumentBuilderService
{
    public function __construct(
        protected DelayStageService $delayStageService,
        protected SeverityService $severityService
    ) {
    }

    /**
     * Build Elasticsearch document.
     *
     * Returns null if case is completed.
     */
    public function build(ApplicantCase $case): ?array
    {
        $stage = $this->delayStageService->determineStage($case);

        // Completed case
        if (!$stage) {
            return null;
        }

        $severity = $this->severityService->calculate(
            $stage['stage_type'],
            $stage['stage_start_date']
        );

        return [

            /*
            |--------------------------------------------------------------------------
            | Case Information
            |--------------------------------------------------------------------------
            */

            'case_uuid' => $case->case_uuid,

            'applicant_name' => $case->applicant_name,

            'applicant_cnic' => $case->applicant_cnic,

            'district' => $case->district,

            'tehsil' => $case->tehsil,

            'partner_name' => $case->partner_name,

            'bank_name' => $case->bank_name,

            'branch_name' => $case->branch_name,

            /*
            |--------------------------------------------------------------------------
            | Normalized Fields
            |--------------------------------------------------------------------------
            */

            'district_norm' => Str::lower(trim($case->district)),

            'tehsil_norm' => Str::lower(trim($case->tehsil)),

            'partner_norm' => Str::lower(trim($case->partner_name)),

            'bank_norm' => Str::lower(trim($case->bank_name)),

            /*
            |--------------------------------------------------------------------------
            | Delay Stage
            |--------------------------------------------------------------------------
            */

            'stage_key' => $stage['stage_key'],

            'stage_label' => $stage['stage_label'],

            'stage_type' => $stage['stage_type'],

            'stage_start_date' => is_string($stage['stage_start_date'])
                ? $stage['stage_start_date']
                : $stage['stage_start_date']->toDateString(),

            /*
            |--------------------------------------------------------------------------
            | Delay Metrics
            |--------------------------------------------------------------------------
            */

            'days_waiting' => $severity['days_waiting'],

            'severity' => $severity['severity'],

            /*
            |--------------------------------------------------------------------------
            | Search Blob
            |--------------------------------------------------------------------------
            */

            'search_blob' => implode(' ', [

                $case->case_uuid,

                $case->applicant_name,

                $case->applicant_cnic,

                $case->district,

                $case->tehsil,

                $case->partner_name,

                $case->bank_name,

                $case->branch_name,

            ]),

            /*
            |--------------------------------------------------------------------------
            | Indexed At
            |--------------------------------------------------------------------------
            */

            'indexed_at' => now()->toISOString(),
        ];
    }
}