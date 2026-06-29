<?php

namespace Database\Factories;
use App\Models\ApplicantCase;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ApplicantCase>
 */
class ApplicantCaseFactory extends Factory
{
    protected $model = ApplicantCase::class;
    
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $districts = [
            'Lahore',
            'Kasur',
            'Sheikhupura',
            'Sahiwal',
            'Okara'
        ];

        $tehsils = [
            'Model Town',
            'Raiwind',
            'City',
            'Cantt',
            'Pattoki',
            'Depalpur',
            'Renala Khurd',
            'Chunian',
            'Shalimar',
            'Muridke'
        ];

        $partners = [
            'Partner A',
            'Partner B',
            'Partner C',
            'Partner D',
            'Partner E'
        ];

        $banks = [
            'HBL',
            'UBL',
            'MCB',
            'Meezan Bank',
            'Bank Alfalah'
        ];

        return [

            'case_uuid' => 'CASE-' . str_pad(
                fake()->unique()->numberBetween(1, 999999),
                6,
                '0',
                STR_PAD_LEFT
            ),

            'applicant_name' => fake()->name(),

            'applicant_cnic' => fake()->unique()->numerify('#####-#######-#'),

            'district' => fake()->randomElement($districts),

            'tehsil' => fake()->randomElement($tehsils),

            'partner_name' => fake()->randomElement($partners),

            'bank_name' => fake()->randomElement($banks),

            'branch_name' => 'Branch ' . fake()->numberBetween(1, 20),

        ];
    }
}
