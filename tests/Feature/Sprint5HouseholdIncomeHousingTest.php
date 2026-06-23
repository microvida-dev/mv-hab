<?php

namespace Tests\Feature;

use App\Enums\HouseholdRelationship;
use App\Enums\HousingCondition;
use App\Enums\HousingStatus;
use App\Enums\ProfessionalStatus;
use App\Models\AdhesionRegistration;
use App\Models\CurrentHousingSituation;
use App\Models\Household;
use App\Models\HouseholdMember;
use App\Models\IncomeRecord;
use App\Models\IncomeSource;
use App\Models\User;
use Database\Seeders\SystemAccessSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class Sprint5HouseholdIncomeHousingTest extends TestCase
{
    use RefreshDatabase;

    public function test_household_pages_require_authentication_candidate_role_and_registration(): void
    {
        $this->get(route('candidate.household.show'))->assertRedirect(route('login'));

        $administrator = $this->userWithRole('administrator');
        $this->actingAs($administrator)
            ->get(route('candidate.household.show'))
            ->assertForbidden();

        $candidate = $this->candidate();
        $this->actingAs($candidate)
            ->get(route('candidate.household.show'))
            ->assertRedirect(route('candidate.registration.create'));
    }

    public function test_candidate_can_create_one_household_with_synced_applicant_and_no_mass_assignment(): void
    {
        [$candidate, $registration] = $this->candidateWithRegistration();
        $otherRegistration = AdhesionRegistration::factory()->create();

        $this->actingAs($candidate)
            ->post(route('candidate.household.store'), [
                'name' => 'Agregado de Teste',
                'household_type' => 'family',
                'adhesion_registration_id' => $otherRegistration->id,
                'citizen_id' => 999,
                'members_count' => 99,
                'monthly_income' => 99999,
            ])
            ->assertRedirect(route('candidate.household.show'));

        $household = Household::query()->where('adhesion_registration_id', $registration->id)->firstOrFail();
        $applicant = $household->members()->firstOrFail();

        $this->assertNull($household->citizen_id);
        $this->assertSame(1, $household->members_count);
        $this->assertSame('0.00', $household->monthly_income);
        $this->assertTrue($applicant->is_applicant);
        $this->assertSame($registration->full_name, $applicant->full_name);
        $this->assertSame($registration->id, $applicant->adhesion_registration_id);
        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $candidate->id,
            'module' => 'households',
            'action' => 'create',
        ]);

        $this->actingAs($candidate)
            ->post(route('candidate.household.store'), ['household_type' => 'family'])
            ->assertForbidden();
    }

    public function test_candidate_can_manage_members_with_validation_and_member_count_updates(): void
    {
        [$candidate, , $household] = $this->candidateWithHousehold();

        $this->actingAs($candidate)
            ->post(route('candidate.household-members.store'), [
                ...$this->memberData(),
                'full_name' => 'Dependente de Teste',
                'is_dependent' => true,
                'household_id' => 999,
                'adhesion_registration_id' => 999,
            ])
            ->assertRedirect(route('candidate.household-members.index'));

        $member = $household->members()->where('full_name', 'Dependente de Teste')->firstOrFail();
        $this->assertSame($household->id, $member->household_id);
        $this->assertSame($household->adhesion_registration_id, $member->adhesion_registration_id);
        $this->assertSame(2, $household->fresh()->members_count);

        $this->actingAs($candidate)
            ->post(route('candidate.household-members.store'), [
                ...$this->memberData(),
                'nif' => $member->nif,
            ])
            ->assertSessionHasErrors('nif');

        $this->actingAs($candidate)
            ->post(route('candidate.household-members.store'), [
                ...$this->memberData(),
                'birth_date' => today()->addDay()->toDateString(),
                'disability_percentage' => 101,
            ])
            ->assertSessionHasErrors(['birth_date', 'disability_percentage']);

        $this->actingAs($candidate)
            ->put(route('candidate.household-members.update', $member), [
                ...$this->memberData(),
                'full_name' => 'Dependente Atualizado',
                'has_no_income' => true,
                'no_income_reason' => 'Sem atividade remunerada.',
            ])
            ->assertRedirect(route('candidate.household-members.index'));

        $this->assertSame('Dependente Atualizado', $member->fresh()->full_name);
        $this->assertTrue($member->fresh()->has_no_income);

        $this->actingAs($candidate)
            ->delete(route('candidate.household-members.destroy', $member))
            ->assertRedirect(route('candidate.household-members.index'));

        $this->assertSoftDeleted('household_members', ['id' => $member->id]);
        $this->assertSame(1, $household->fresh()->members_count);
    }

    public function test_household_must_keep_an_applicant_member(): void
    {
        [$candidate, , $household] = $this->candidateWithHousehold();
        $applicant = $household->members()->where('is_applicant', true)->firstOrFail();

        $this->actingAs($candidate)
            ->delete(route('candidate.household-members.destroy', $applicant))
            ->assertSessionHasErrors('member');

        $this->assertDatabaseHas('household_members', [
            'id' => $applicant->id,
            'deleted_at' => null,
        ]);
    }

    public function test_candidate_cannot_access_another_candidates_member_or_income(): void
    {
        [$candidate] = $this->candidateWithHousehold();
        [$otherCandidate, , $otherHousehold] = $this->candidateWithHousehold();
        $member = $otherHousehold->members()->firstOrFail();
        $source = IncomeSource::factory()->create();
        $record = IncomeRecord::factory()->create([
            'household_member_id' => $member->id,
            'household_id' => $otherHousehold->id,
            'adhesion_registration_id' => $otherHousehold->adhesion_registration_id,
            'income_source_id' => $source->id,
        ]);

        $this->actingAs($candidate)
            ->get(route('candidate.household-members.edit', $member))
            ->assertForbidden();
        $this->actingAs($candidate)
            ->get(route('candidate.income-records.edit', $record))
            ->assertForbidden();

        $this->actingAs($otherCandidate)
            ->get(route('candidate.income-records.edit', $record))
            ->assertOk();
    }

    public function test_income_requires_owned_member_and_amount_and_calculates_totals(): void
    {
        [$candidate, , $household] = $this->candidateWithHousehold();
        $source = IncomeSource::factory()->create();
        $member = $household->members()->firstOrFail();
        [, , $foreignHousehold] = $this->candidateWithHousehold();
        $foreignMember = $foreignHousehold->members()->firstOrFail();

        $this->actingAs($candidate)
            ->post(route('candidate.income-records.store'), [
                'household_member_id' => $member->id,
                'income_source_id' => $source->id,
            ])
            ->assertSessionHasErrors('monthly_amount');

        $this->actingAs($candidate)
            ->post(route('candidate.income-records.store'), [
                'household_member_id' => $foreignMember->id,
                'income_source_id' => $source->id,
                'monthly_amount' => 100,
            ])
            ->assertSessionHasErrors('household_member_id');

        $this->actingAs($candidate)
            ->post(route('candidate.income-records.store'), [
                'household_member_id' => $member->id,
                'income_source_id' => $source->id,
                'monthly_amount' => 1000,
                'household_id' => $foreignHousehold->id,
                'adhesion_registration_id' => $foreignHousehold->adhesion_registration_id,
                'is_current' => true,
                'is_taxable' => true,
            ])
            ->assertRedirect(route('candidate.income-records.index'));

        $record = IncomeRecord::query()->where('household_id', $household->id)->firstOrFail();
        $this->assertSame('1000.00', $record->monthly_amount);
        $this->assertSame('12000.00', $record->annual_amount);
        $this->assertSame($household->adhesion_registration_id, $record->adhesion_registration_id);
        $this->assertSame('1000.00', $household->fresh()->monthly_income);
        $this->assertSame('1000.00', $member->fresh()->monthly_declared_income);

        $secondMember = HouseholdMember::factory()->create([
            'household_id' => $household->id,
            'adhesion_registration_id' => $household->adhesion_registration_id,
        ]);

        $this->actingAs($candidate)
            ->put(route('candidate.income-records.update', $record), [
                'household_member_id' => $secondMember->id,
                'income_source_id' => $source->id,
                'annual_amount' => 18000,
                'is_current' => true,
                'is_taxable' => true,
            ])
            ->assertRedirect(route('candidate.income-records.index'));

        $this->assertSame('1500.00', $record->fresh()->monthly_amount);
        $this->assertSame('18000.00', $record->fresh()->annual_amount);
        $this->assertSame($secondMember->id, $record->fresh()->household_member_id);
        $this->assertSame('0.00', $member->fresh()->monthly_declared_income);
        $this->assertSame('1500.00', $secondMember->fresh()->monthly_declared_income);

        $this->actingAs($candidate)
            ->delete(route('candidate.income-records.destroy', $record))
            ->assertRedirect(route('candidate.income-records.index'));

        $this->assertSoftDeleted('income_records', ['id' => $record->id]);
        $this->assertSame('0.00', $household->fresh()->monthly_income);
    }

    public function test_member_marked_without_income_cannot_receive_income_record(): void
    {
        [$candidate, , $household] = $this->candidateWithHousehold();
        $source = IncomeSource::factory()->create();
        $member = HouseholdMember::factory()->withoutIncome()->create([
            'household_id' => $household->id,
            'adhesion_registration_id' => $household->adhesion_registration_id,
        ]);

        $this->actingAs($candidate)
            ->post(route('candidate.income-records.store'), [
                'household_member_id' => $member->id,
                'income_source_id' => $source->id,
                'monthly_amount' => 100,
            ])
            ->assertSessionHasErrors('household_member_id');
    }

    public function test_candidate_can_create_and_update_current_housing_without_mass_assigning_owner(): void
    {
        [$candidate, $registration] = $this->candidateWithRegistration();
        $otherRegistration = AdhesionRegistration::factory()->create();

        $this->actingAs($candidate)
            ->put(route('candidate.current-housing.update'), [
                ...$this->housingData(),
                'adhesion_registration_id' => $otherRegistration->id,
            ])
            ->assertRedirect(route('candidate.current-housing.show'));

        $situation = CurrentHousingSituation::query()->firstOrFail();
        $this->assertSame($registration->id, $situation->adhesion_registration_id);
        $this->assertSame(HousingStatus::Rented, $situation->housing_status);
        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $candidate->id,
            'module' => 'current_housing',
            'action' => 'create',
        ]);

        $this->actingAs($candidate)
            ->put(route('candidate.current-housing.update'), [
                ...$this->housingData(),
                'housing_status' => HousingStatus::Homeless->value,
                'current_monthly_rent' => -1,
                'residence_years_in_municipality' => 121,
            ])
            ->assertSessionHasErrors(['current_monthly_rent', 'residence_years_in_municipality']);
    }

    public function test_dashboard_shows_real_progress_summaries_and_missing_steps(): void
    {
        [$candidate, $registration, $household] = $this->candidateWithHousehold();
        $source = IncomeSource::factory()->create();
        $member = $household->members()->firstOrFail();
        IncomeRecord::factory()->create([
            'household_member_id' => $member->id,
            'household_id' => $household->id,
            'adhesion_registration_id' => $registration->id,
            'income_source_id' => $source->id,
            'monthly_amount' => 900,
            'annual_amount' => 10800,
        ]);

        $this->actingAs($candidate)
            ->get(route('candidate.dashboard'))
            ->assertOk()
            ->assertSee('Progresso geral')
            ->assertSee('900,00 €')
            ->assertSee('Preencha a informação sobre a sua situação habitacional atual.');

        CurrentHousingSituation::factory()->create([
            'adhesion_registration_id' => $registration->id,
        ]);

        $this->actingAs($candidate)
            ->get(route('candidate.dashboard'))
            ->assertOk()
            ->assertSee('Habitação arrendada')
            ->assertSee('Os dados preparatórios do registo estão completos.');
    }

    public function test_sensitive_candidate_data_is_not_exposed_on_public_pages(): void
    {
        [, $registration] = $this->candidateWithRegistration();
        CurrentHousingSituation::factory()->create([
            'adhesion_registration_id' => $registration->id,
            'is_domestic_violence_victim' => true,
            'additional_notes' => 'Nota sensível reservada ao titular.',
        ]);

        $this->get(route('public.portal'))
            ->assertOk()
            ->assertDontSee($registration->nif)
            ->assertDontSee('Nota sensível reservada ao titular.');
    }

    private function candidate(): User
    {
        return $this->userWithRole('candidate');
    }

    private function userWithRole(string $role): User
    {
        $this->seed(SystemAccessSeeder::class);
        $user = User::factory()->create();
        $user->assignRole($role);

        return $user;
    }

    private function candidateWithRegistration(): array
    {
        $candidate = $this->candidate();
        $registration = AdhesionRegistration::factory()->for($candidate)->create();

        return [$candidate, $registration];
    }

    private function candidateWithHousehold(): array
    {
        [$candidate, $registration] = $this->candidateWithRegistration();

        $this->actingAs($candidate)->post(route('candidate.household.store'), [
            'household_type' => 'family',
        ])->assertRedirect(route('candidate.household.show'));

        return [$candidate, $registration->fresh(), $registration->household()->firstOrFail()];
    }

    private function memberData(): array
    {
        return [
            'full_name' => 'Membro de Teste',
            'birth_date' => today()->subYears(12)->toDateString(),
            'relationship' => HouseholdRelationship::Child->value,
            'nationality' => 'Nacionalidade de teste',
            'nif' => 'MEMBER-'.fake()->unique()->numerify('####'),
            'professional_status' => ProfessionalStatus::Student->value,
            'is_dependent' => true,
            'is_student' => true,
            'is_disabled' => false,
            'has_no_income' => false,
        ];
    }

    private function housingData(): array
    {
        return [
            'housing_status' => HousingStatus::Rented->value,
            'current_address' => 'Morada atual de teste',
            'current_municipality' => 'Município de Teste',
            'resides_in_municipality' => true,
            'residence_years_in_municipality' => 3,
            'current_housing_condition' => HousingCondition::Adequate->value,
            'current_monthly_rent' => 500,
            'request_reason' => 'Necessidade habitacional declarada para teste.',
        ];
    }
}
