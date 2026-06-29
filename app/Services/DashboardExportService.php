<?php

namespace App\Services;

use Illuminate\Support\Facades\Response;

class DashboardExportService
{
    public function __construct(
        protected DashboardService $dashboardService
    ) {
    }

    /**
     * Export filtered dashboard data to CSV.
     */
    public function exportToCSV(array $filters = []): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        // Get all cases (no pagination for export)
        $allCases = $this->getAllCases($filters);

        // Prepare CSV headers
        $headers = [
            'Case UUID',
            'Applicant Name',
            'Applicant CNIC',
            'District',
            'Partner',
            'Stage',
            'Days Waiting',
            'Severity',
        ];

        $filename = 'delayed_cases_' . now()->format('Y-m-d_H-i-s') . '.csv';

        return Response::streamDownload(
            function () use ($headers, $allCases) {
                // Open output stream
                $file = fopen('php://output', 'w');
                
                // Write headers
                fputcsv($file, $headers);

                // Write data rows
                foreach ($allCases as $case) {
                    fputcsv($file, [
                        $case['case_uuid'] ?? '-',
                        $case['applicant_name'] ?? '-',
                        $case['applicant_cnic'] ?? '-',
                        $case['district'] ?? '-',
                        $case['partner_name'] ?? '-',
                        $case['stage_label'] ?? '-',
                        $case['days_waiting'] ?? 0,
                        strtoupper($case['severity'] ?? 'GREEN'),
                    ]);
                }

                fclose($file);
            },
            $filename,
            [
                'Content-Type' => 'text/csv; charset=UTF-8',
                'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            ]
        );
    }

    /**
     * Get all cases matching filters (no pagination for export).
     */
    private function getAllCases(array $filters): array
    {
        // Use the public getDelayedCases method with large page size
        $result = $this->dashboardService->getDelayedCases($filters, 1, 10000);
        return $result['data'] ?? [];
    }
}
