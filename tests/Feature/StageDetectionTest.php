<?php

namespace Tests\Feature;

use App\Models\ApplicantCase;
use App\Models\FinancialRelease;
use App\Models\Inspection;
use App\Services\DelayStageService;
use Tests\TestCase;

class StageDetectionTest extends TestCase
{
    protected DelayStageService $stageService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->stageService = new DelayStageService();
    }

    /** @test */
    public function can_detect_waiting_for_second_release_stage()
    {
        $case = ApplicantCase::factory()->create();
        
        $financial = FinancialRelease::factory()->create([
            'applicant_case_id' => $case->id,
            'first_release_date' => now()->subDays(20),
            'second_release_date' => null,
            'final_release_date' => null,
        ]);
        
        $inspection = Inspection::factory()->create([
            'applicant_case_id' => $case->id,
            'structure_inspection_date' => null,
        ]);
        
        $case->financialRelease()->associate($financial);
        $case->inspection()->associate($inspection);
        $case->save();

        $stage = $this->stageService->determineStage($case);

        $this->assertNotNull($stage);
        $this->assertEquals('Waiting for Second Release', $stage['stage_label']);
    }

    /** @test */
    public function can_detect_waiting_for_structure_inspection_stage()
    {
        $case = ApplicantCase::factory()->create();
        
        $financial = FinancialRelease::factory()->create([
            'applicant_case_id' => $case->id,
            'first_release_date' => now()->subDays(30),
            'second_release_date' => now()->subDays(10),
            'final_release_date' => null,
        ]);
        
        $inspection = Inspection::factory()->create([
            'applicant_case_id' => $case->id,
            'structure_inspection_date' => null,
        ]);
        
        $case->financialRelease()->associate($financial);
        $case->inspection()->associate($inspection);
        $case->save();

        $stage = $this->stageService->determineStage($case);

        $this->assertNotNull($stage);
        $this->assertEquals('Waiting for Structure Inspection', $stage['stage_label']);
    }

    /** @test */
    public function can_detect_waiting_for_final_release_stage()
    {
        $case = ApplicantCase::factory()->create();
        
        $financial = FinancialRelease::factory()->create([
            'applicant_case_id' => $case->id,
            'first_release_date' => now()->subDays(40),
            'second_release_date' => now()->subDays(20),
            'final_release_date' => null,
        ]);
        
        $inspection = Inspection::factory()->create([
            'applicant_case_id' => $case->id,
            'structure_inspection_date' => now()->subDays(15),
        ]);
        
        $case->financialRelease()->associate($financial);
        $case->inspection()->associate($inspection);
        $case->save();

        $stage = $this->stageService->determineStage($case);

        $this->assertNotNull($stage);
        $this->assertEquals('Waiting for Final Release', $stage['stage_label']);
    }

    /** @test */
    public function returns_null_for_completed_case()
    {
        $case = ApplicantCase::factory()->create();
        
        $financial = FinancialRelease::factory()->create([
            'applicant_case_id' => $case->id,
            'first_release_date' => now()->subDays(50),
            'second_release_date' => now()->subDays(40),
            'final_release_date' => now()->subDays(5),
        ]);
        
        $inspection = Inspection::factory()->create([
            'applicant_case_id' => $case->id,
            'structure_inspection_date' => now()->subDays(20),
        ]);
        
        $case->financialRelease()->associate($financial);
        $case->inspection()->associate($inspection);
        $case->save();

        $stage = $this->stageService->determineStage($case);

        $this->assertNull($stage);
    }

    /** @test */
    public function returns_null_when_no_financial_or_inspection_records()
    {
        $case = ApplicantCase::factory()->create();

        $stage = $this->stageService->determineStage($case);

        $this->assertNull($stage);
    }

    /** @test */
    public function stage_start_date_is_set_correctly()
    {
        $case = ApplicantCase::factory()->create();
        $releaseDate = now()->subDays(25);
        
        $financial = FinancialRelease::factory()->create([
            'applicant_case_id' => $case->id,
            'first_release_date' => $releaseDate,
            'second_release_date' => null,
            'final_release_date' => null,
        ]);
        
        $inspection = Inspection::factory()->create([
            'applicant_case_id' => $case->id,
            'structure_inspection_date' => null,
        ]);
        
        $case->financialRelease()->associate($financial);
        $case->inspection()->associate($inspection);
        $case->save();

        $stage = $this->stageService->determineStage($case);

        $this->assertNotNull($stage);
        $this->assertEquals($releaseDate->toDateString(), $stage['stage_start_date']->toDateString());
    }
}

