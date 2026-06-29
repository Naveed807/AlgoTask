<?php

namespace Database\Seeders;

use App\Models\ApplicantCase;
use App\Models\FinancialRelease;
use App\Models\Inspection;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class AnalyticsSeeder extends Seeder
{
    /**
     * Return a random date based on severity and stage type.
    */
    private function getStageStartDate(string $stageType): \Carbon\Carbon
    {
        $severity = fake()->randomElement([
            'green',
            'yellow',
            'amber',
            'red',
        ]);

        return now()->subDays(
            $this->daysForSeverity($severity, $stageType)
        );
    }

    /**
     * Return random waiting days.
     */
    private function daysForSeverity(string $severity, string $stageType): int
    {
        if ($stageType === 'release_to_inspection') {

            return match ($severity) {
                'green' => rand(0, 15),
                'yellow' => rand(16, 30),
                'amber' => rand(31, 45),
                'red' => rand(46, 90),
            };
        }

        return match ($severity) {
            'green' => rand(0, 7),
            'yellow' => rand(8, 15),
            'amber' => rand(16, 30),
            'red' => rand(31, 90),
        };
    }
    public function run(): void
    {
        ApplicantCase::factory()
            ->count(10000)
            ->create()
            ->each(function (ApplicantCase $case) {
                $stage = fake()->randomElement([
                    1,
                    2,
                    3,
                    4,
                    5 // Completed
                ]);
                switch ($stage) {
                    /*
                     |----------------------------------------------
                     | Stage 1
                     | Waiting for Foundation Inspection
                     |----------------------------------------------
                     */
                    case 1:
                        $firstReleaseDate = $this->getStageStartDate('release_to_inspection');

                        FinancialRelease::create([
                            'case_id' => $case->id,
                            'first_release_date' => $firstReleaseDate,
                            'second_release_date' => null,
                            'final_release_date' => null,
                            'first_amount' => rand(50000, 150000),
                            'second_amount' => null,
                            'final_amount' => null,
                        ]);
                        Inspection::create([
                            'case_id' => $case->id,
                            'foundation_inspection_date' => null,
                            'structure_inspection_date' => null,
                            'foundation_status' => null,
                            'structure_status' => null,
                        ]);
                        break;
                    /*
                     |----------------------------------------------
                     | Stage 2
                     | Waiting for Second Release
                     |----------------------------------------------
                     */
                    case 2:
                        $foundationDate = $this->getStageStartDate('inspection_to_release');
                        FinancialRelease::create([
                            'case_id' => $case->id,
                            'first_release_date' => $foundationDate->copy()->subDays(rand(20,40)),
                            'second_release_date' => null,
                            'final_release_date' => null,
                            'first_amount' => rand(50000,150000),
                            'second_amount' => null,
                            'final_amount' => null,
                        ]);
                        Inspection::create([
                            'case_id' => $case->id,
                            'foundation_inspection_date' => $foundationDate,
                            'structure_inspection_date' => null,
                            'foundation_status' => 'Completed',
                            'structure_status' => null,
                        ]);
                        break;
                    /*
                     |----------------------------------------------
                     | Stage 3
                     | Waiting for Structure Inspection
                     |----------------------------------------------
                     */
                    case 3:
                        $secondRelease = $this->getStageStartDate('release_to_inspection');
                        FinancialRelease::create([
                            'case_id' => $case->id,
                            'first_release_date' => $secondRelease->copy()->subDays(rand(20,40)),
                            'second_release_date' => $secondRelease,
                            'final_release_date' => null,
                            'first_amount' => rand(50000,150000),
                            'second_amount' => rand(50000,150000),
                            'final_amount' => null,
                        ]);
                        Inspection::create([
                            'case_id' => $case->id,
                            'foundation_inspection_date' => $secondRelease->copy()->subDays(rand(5,10)),
                            'structure_inspection_date' => null,
                            'foundation_status' => 'Completed',
                            'structure_status' => null,
                        ]);
                        break;
                    /*
                     |----------------------------------------------
                     | Stage 4
                     | Waiting for Final Release
                     |----------------------------------------------
                     */
                    case 4:
                        $structureDate = $this->getStageStartDate('inspection_to_release');
                        FinancialRelease::create([
                            'case_id' => $case->id,
                            'first_release_date' => $structureDate->copy()->subDays(rand(40,60)),
                            'second_release_date' => $structureDate->copy()->subDays(rand(10,20)),
                            'final_release_date' => null,
                            'first_amount' => rand(50000,150000),
                            'second_amount' => rand(50000,150000),
                            'final_amount' => null,
                        ]);
                        Inspection::create([
                            'case_id' => $case->id,
                            'foundation_inspection_date' => $structureDate->copy()->subDays(rand(20,30)),
                            'structure_inspection_date' => $structureDate,
                            'foundation_status' => 'Completed',
                            'structure_status' => 'Completed',
                        ]);
                        break;
                    /*
                     |----------------------------------------------
                     | Completed
                     |----------------------------------------------
                     */
                    default:
                        $finalDate = now()->subDays(rand(1,20));
                        FinancialRelease::create([
                            'case_id' => $case->id,
                            'first_release_date' => $finalDate->copy()->subDays(60),
                            'second_release_date' => $finalDate->copy()->subDays(30),
                            'final_release_date' => $finalDate,
                            'first_amount' => rand(50000,150000),
                            'second_amount' => rand(50000,150000),
                            'final_amount' => rand(50000,150000),
                        ]);
                        Inspection::create([
                            'case_id' => $case->id,
                            'foundation_inspection_date' => $finalDate->copy()->subDays(45),
                            'structure_inspection_date' => $finalDate->copy()->subDays(15),
                            'foundation_status' => 'Completed',
                            'structure_status' => 'Completed',
                        ]);
                }
            });
    }
}