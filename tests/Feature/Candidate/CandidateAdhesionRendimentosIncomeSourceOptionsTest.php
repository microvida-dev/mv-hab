<?php

namespace Tests\Feature\Candidate;

use App\Enums\IncomeSourceType;
use App\Models\AdhesionRegistration;
use App\Models\Household;
use App\Models\HouseholdMember;
use App\Models\IncomeRecord;
use App\Models\IncomeSource;
use App\Models\User;
use Database\Seeders\SystemAccessSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CandidateAdhesionRendimentosIncomeSourceOptionsTest extends TestCase
{
    use RefreshDatabase;

    public function test_adhesion_rendimentos_form_repairs_empty_income_source_catalog(): void
    {
        [$candidate, $household, $member] = $this->candidateWithHousehold();

        $this->assertSame(0, IncomeSource::query()->count());

        $this->actingAs($candidate)
            ->get(route('candidate.income-records.create'))
            ->assertOk()
            ->assertSee('Fonte de rendimento')
            ->assertSee('Trabalho dependente')
            ->assertSee('Trabalho independente')
            ->assertSee('Pensões');

        $source = IncomeSource::query()
            ->where('code', IncomeSourceType::Employment->value)
            ->firstOrFail();

        $this->actingAs($candidate)
            ->post(route('candidate.income-records.store'), [
                'household_member_id' => $member->id,
                'income_source_id' => $source->id,
                'monthly_amount' => 850,
                'is_current' => true,
                'is_taxable' => true,
            ])
            ->assertRedirect(route('candidate.income-records.index'));

        $record = IncomeRecord::query()->where('household_id', $household->id)->firstOrFail();

        $this->assertSame($source->id, $record->income_source_id);
        $this->assertSame('850.00', $record->monthly_amount);
    }

    /**
     * @return array{0: User, 1: Household, 2: HouseholdMember}
     */
    private function candidateWithHousehold(): array
    {
        $this->seed(SystemAccessSeeder::class);

        $candidate = User::factory()->create();
        $candidate->assignRole('candidate');

        $registration = AdhesionRegistration::factory()->for($candidate)->create();
        $household = Household::factory()->for($registration, 'adhesionRegistration')->create();
        $member = HouseholdMember::factory()->create([
            'household_id' => $household->id,
            'adhesion_registration_id' => $registration->id,
            'is_applicant' => true,
            'has_no_income' => false,
        ]);

        return [$candidate, $household, $member];
    }
}
