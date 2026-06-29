<?php

namespace App\Services;

use Carbon\Carbon;

class SeverityService
{
    /**
     * Calculate days waiting and severity.
     *
     * @param string $stageType
     * @param Carbon|string $stageStartDate
     * @return array
     */
    public function calculate(string $stageType, Carbon|string $stageStartDate): array
    {
        if (!$stageStartDate instanceof Carbon) {
            $stageStartDate = Carbon::parse($stageStartDate);
        }

        $daysWaiting = $stageStartDate->diffInDays(now());

        // Return days in integer format and severity level based on stage type and waiting days
        $daysWaiting = (int) $daysWaiting;

        return [
            'days_waiting' => $daysWaiting,
            'severity' => $this->determineSeverity($stageType, $daysWaiting),
        ];
    }

    /**
     * Determine severity based on stage type and waiting days.
     *
     * @param string $stageType
     * @param int $daysWaiting
     * @return string
     */
    private function determineSeverity(string $stageType, int $daysWaiting): string
    {
        if ($stageType === 'release_to_inspection') {

            return match (true) {
                $daysWaiting <= 15 => 'green',
                $daysWaiting <= 30 => 'yellow',
                $daysWaiting <= 45 => 'amber',
                default => 'red',
            };
        }

        if ($stageType === 'inspection_to_release') {

            return match (true) {
                $daysWaiting <= 7 => 'green',
                $daysWaiting <= 15 => 'yellow',
                $daysWaiting <= 30 => 'amber',
                default => 'red',
            };
        }

        throw new \InvalidArgumentException(
            "Unknown stage type: {$stageType}"
        );
    }
}