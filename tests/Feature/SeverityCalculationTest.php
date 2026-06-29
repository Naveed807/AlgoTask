<?php

namespace Tests\Feature;

use App\Services\SeverityService;
use Carbon\Carbon;
use Tests\TestCase;

class SeverityCalculationTest extends TestCase
{
    protected SeverityService $severityService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->severityService = new SeverityService();
    }

    /** @test */
    public function calculates_green_severity_for_release_to_inspection_new_case()
    {
        $result = $this->severityService->calculate(
            'release_to_inspection',
            now()->toDateString()
        );

        $this->assertEquals('green', $result['severity']);
        $this->assertEquals(0, $result['days_waiting']);
    }

    /** @test */
    public function calculates_yellow_severity_for_release_to_inspection_medium_delay()
    {
        $result = $this->severityService->calculate(
            'release_to_inspection',
            now()->subDays(20)->toDateString()
        );

        $this->assertEquals('yellow', $result['severity']);
        $this->assertGreaterThanOrEqual(19, $result['days_waiting']);
    }

    /** @test */
    public function calculates_amber_severity_for_release_to_inspection_high_delay()
    {
        $result = $this->severityService->calculate(
            'release_to_inspection',
            now()->subDays(35)->toDateString()
        );

        $this->assertEquals('amber', $result['severity']);
        $this->assertGreaterThanOrEqual(34, $result['days_waiting']);
    }

    /** @test */
    public function calculates_red_severity_for_release_to_inspection_critical_delay()
    {
        $result = $this->severityService->calculate(
            'release_to_inspection',
            now()->subDays(50)->toDateString()
        );

        $this->assertEquals('red', $result['severity']);
        $this->assertGreaterThanOrEqual(49, $result['days_waiting']);
    }

    /** @test */
    public function calculates_green_severity_for_inspection_to_release_new_case()
    {
        $result = $this->severityService->calculate(
            'inspection_to_release',
            now()->toDateString()
        );

        $this->assertEquals('green', $result['severity']);
        $this->assertEquals(0, $result['days_waiting']);
    }

    /** @test */
    public function calculates_yellow_severity_for_inspection_to_release_medium_delay()
    {
        $result = $this->severityService->calculate(
            'inspection_to_release',
            now()->subDays(10)->toDateString()
        );

        $this->assertEquals('yellow', $result['severity']);
        $this->assertGreaterThanOrEqual(9, $result['days_waiting']);
    }

    /** @test */
    public function calculates_amber_severity_for_inspection_to_release_high_delay()
    {
        $result = $this->severityService->calculate(
            'inspection_to_release',
            now()->subDays(20)->toDateString()
        );

        $this->assertEquals('amber', $result['severity']);
        $this->assertGreaterThanOrEqual(19, $result['days_waiting']);
    }

    /** @test */
    public function calculates_red_severity_for_inspection_to_release_critical_delay()
    {
        $result = $this->severityService->calculate(
            'inspection_to_release',
            now()->subDays(40)->toDateString()
        );

        $this->assertEquals('red', $result['severity']);
        $this->assertGreaterThanOrEqual(39, $result['days_waiting']);
    }

    /** @test */
    public function release_to_inspection_thresholds_are_consistent()
    {
        // Green: 0-15 days
        $result1 = $this->severityService->calculate('release_to_inspection', now()->subDays(10)->toDateString());
        $this->assertEquals('green', $result1['severity']);

        // Yellow: 16-30 days
        $result2 = $this->severityService->calculate('release_to_inspection', now()->subDays(25)->toDateString());
        $this->assertEquals('yellow', $result2['severity']);

        // Amber: 31-45 days
        $result3 = $this->severityService->calculate('release_to_inspection', now()->subDays(40)->toDateString());
        $this->assertEquals('amber', $result3['severity']);

        // Red: 46+ days
        $result4 = $this->severityService->calculate('release_to_inspection', now()->subDays(50)->toDateString());
        $this->assertEquals('red', $result4['severity']);
    }

    /** @test */
    public function inspection_to_release_thresholds_are_consistent()
    {
        // Green: 0-7 days
        $result1 = $this->severityService->calculate('inspection_to_release', now()->subDays(5)->toDateString());
        $this->assertEquals('green', $result1['severity']);

        // Yellow: 8-15 days
        $result2 = $this->severityService->calculate('inspection_to_release', now()->subDays(10)->toDateString());
        $this->assertEquals('yellow', $result2['severity']);

        // Amber: 16-30 days
        $result3 = $this->severityService->calculate('inspection_to_release', now()->subDays(20)->toDateString());
        $this->assertEquals('amber', $result3['severity']);

        // Red: 31+ days
        $result4 = $this->severityService->calculate('inspection_to_release', now()->subDays(40)->toDateString());
        $this->assertEquals('red', $result4['severity']);
    }

    /** @test */
    public function handles_boundary_values_for_release_to_inspection()
    {
        // Boundaries: 0-15 (green), 16-30 (yellow), 31-45 (amber), 46+ (red)
        $this->assertEquals('green', $this->severityService->calculate('release_to_inspection', now()->subDays(15)->toDateString())['severity']);
        $this->assertEquals('yellow', $this->severityService->calculate('release_to_inspection', now()->subDays(16)->toDateString())['severity']);
        $this->assertEquals('yellow', $this->severityService->calculate('release_to_inspection', now()->subDays(30)->toDateString())['severity']);
        $this->assertEquals('amber', $this->severityService->calculate('release_to_inspection', now()->subDays(31)->toDateString())['severity']);
        $this->assertEquals('amber', $this->severityService->calculate('release_to_inspection', now()->subDays(45)->toDateString())['severity']);
        $this->assertEquals('red', $this->severityService->calculate('release_to_inspection', now()->subDays(46)->toDateString())['severity']);
    }

    /** @test */
    public function handles_boundary_values_for_inspection_to_release()
    {
        // Boundaries: 0-7 (green), 8-15 (yellow), 16-30 (amber), 31+ (red)
        $this->assertEquals('green', $this->severityService->calculate('inspection_to_release', now()->subDays(7)->toDateString())['severity']);
        $this->assertEquals('yellow', $this->severityService->calculate('inspection_to_release', now()->subDays(8)->toDateString())['severity']);
        $this->assertEquals('yellow', $this->severityService->calculate('inspection_to_release', now()->subDays(15)->toDateString())['severity']);
        $this->assertEquals('amber', $this->severityService->calculate('inspection_to_release', now()->subDays(16)->toDateString())['severity']);
        $this->assertEquals('amber', $this->severityService->calculate('inspection_to_release', now()->subDays(30)->toDateString())['severity']);
        $this->assertEquals('red', $this->severityService->calculate('inspection_to_release', now()->subDays(31)->toDateString())['severity']);
    }

    /** @test */
    public function accepts_carbon_date_object()
    {
        $date = Carbon::now()->subDays(20);
        $result = $this->severityService->calculate('release_to_inspection', $date);

        $this->assertEquals('yellow', $result['severity']);
        $this->assertGreaterThanOrEqual(19, $result['days_waiting']);
    }

    /** @test */
    public function throws_exception_for_invalid_stage_type()
    {
        $this->expectException(\InvalidArgumentException::class);

        $this->severityService->calculate('invalid_stage', now()->toDateString());
    }
}

