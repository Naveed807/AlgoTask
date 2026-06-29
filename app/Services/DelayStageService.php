<?php

namespace App\Services;

use App\Models\ApplicantCase;

class DelayStageService
{
    /**
     * Determine the current active delay stage for a case.
     *
     * @param ApplicantCase $case
     * @return array|null
     */
    public function determineStage(ApplicantCase $case): ?array
    {
        $financial = $case->financialRelease;
        $inspection = $case->inspection;

        // Safety check
        if (!$financial || !$inspection) {
            return null;
        }

        /**
         * Completed
         */
        if ($financial->final_release_date) {
            return null; // Completed cases are not part of delay dashboard
        }

        /**
         * Stage 4
         * Waiting for Final Release
         */
        if (
            $financial->second_release_date &&
            $inspection->structure_inspection_date &&
            !$financial->final_release_date
        ) {
            return [
                'stage_key' => 'waiting_for_final_release',
                'stage_label' => 'Waiting for Final Release',
                'stage_type' => 'inspection_to_release',
                'stage_start_date' => $inspection->structure_inspection_date,
            ];
        }

        /**
         * Stage 3
         * Waiting for Structure Inspection
         */
        if (
            $financial->second_release_date &&
            !$inspection->structure_inspection_date
        ) {
            return [
                'stage_key' => 'waiting_for_structure_inspection',
                'stage_label' => 'Waiting for Structure Inspection',
                'stage_type' => 'release_to_inspection',
                'stage_start_date' => $financial->second_release_date,
            ];
        }

        /**
         * Stage 2
         * Waiting for 2nd Release
         */
        if (
            $inspection->foundation_inspection_date &&
            !$financial->second_release_date
        ) {
            return [
                'stage_key' => 'waiting_for_second_release',
                'stage_label' => 'Waiting for 2nd Release',
                'stage_type' => 'inspection_to_release',
                'stage_start_date' => $inspection->foundation_inspection_date,
            ];
        }

        /**
         * Stage 1
         * Waiting for Foundation Inspection
         */
        if (
            $financial->first_release_date &&
            !$inspection->foundation_inspection_date
        ) {
            return [
                'stage_key' => 'waiting_for_foundation_inspection',
                'stage_label' => 'Waiting for Foundation Inspection',
                'stage_type' => 'release_to_inspection',
                'stage_start_date' => $financial->first_release_date,
            ];
        }

        return null;
    }
}