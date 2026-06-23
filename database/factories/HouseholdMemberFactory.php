<?php

namespace Database\Factories;

use App\Enums\HouseholdRelationship;
use App\Enums\ProfessionalStatus;
use App\Models\Household;
use App\Models\HouseholdMember;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<HouseholdMember>
 */
class HouseholdMemberFactory extends Factory
{
    public function definition(): array
    {
        return [
            'household_id' => Household::factory()->candidate(),
            'adhesion_registration_id' => fn (array $attributes) => Household::query()
                ->findOrFail($attributes['household_id'])
                ->adhesion_registration_id,
            'is_applicant' => false,
            'full_name' => fake()->name(),
            'birth_date' => today()->subYears(fake()->numberBetween(1, 75)),
            'gender' => null,
            'relationship' => HouseholdRelationship::OtherRelative->value,
            'nationality' => 'Nacionalidade de teste',
            'nif' => 'TEST-'.fake()->unique()->numerify('######'),
            'professional_status' => ProfessionalStatus::Other->value,
            'qualification_level' => 4,
            'works_in_municipality' => false,
            'is_dependent' => false,
            'is_student' => false,
            'is_disabled' => false,
            'has_multiple_disabilities' => false,
            'is_pregnant' => false,
            'has_reduced_mobility' => false,
            'is_informal_caregiver' => false,
            'is_elderly' => false,
            'monthly_declared_income' => 0,
            'annual_declared_income' => 0,
            'has_no_income' => false,
            'is_exempt_from_irs' => false,
        ];
    }

    public function applicant(): static
    {
        return $this->state(fn () => [
            'is_applicant' => true,
            'relationship' => HouseholdRelationship::Applicant->value,
        ]);
    }

    public function withoutIncome(): static
    {
        return $this->state(fn () => [
            'has_no_income' => true,
            'no_income_reason' => 'Situação declarada em contexto de teste.',
        ]);
    }
}
