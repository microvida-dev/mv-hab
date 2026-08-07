<?php

namespace Tests\Feature\Regulatory;

use App\Enums\AffordableRentLegalRegime;
use App\Enums\RegulatoryConfigurationStatus;
use App\Models\AffordableRentRegulatoryProfile;
use App\Models\Municipality;
use App\Models\Permission;
use App\Models\PlatformOperatorAssignment;
use App\Models\Program;
use App\Models\RentLimitTableManifest;
use App\Models\RentRuleSet;
use App\Models\Role;
use App\Models\User;
use App\Services\Platform\PlatformMunicipalContextService;
use Database\Seeders\SystemAccessSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegulatoryConfigurationBackofficeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(SystemAccessSeeder::class);
    }

    public function test_program_creation_is_reserved_to_global_platform_administrator(): void
    {
        $municipality = Municipality::factory()->create();
        $municipal = User::factory()->create([
            'municipality_id' => $municipality->id,
            'status' => 'active',
        ]);
        $this->grantRolePermissions('municipal_technician', ['programs.create']);
        $municipal->assignRole('municipal_technician');

        $this->actingAs($municipal)
            ->withSession(['mfa.verified_at' => now()])
            ->get(route('admin.programs.create'))
            ->assertForbidden();

        $global = $this->globalAdministrator();

        $this->actingAs($global)
            ->withSession([
                'mfa.verified_at' => now(),
                PlatformMunicipalContextService::SESSION_KEY => $municipality->id,
            ])
            ->get(route('admin.programs.create'))
            ->assertOk()
            ->assertSee($municipality->name);
    }

    public function test_program_store_uses_selected_context_instead_of_browser_municipality(): void
    {
        $selectedMunicipality = Municipality::factory()->create();
        $tamperedMunicipality = Municipality::factory()->create();
        $profile = AffordableRentRegulatoryProfile::factory()->create([
            'municipality_id' => null,
            'effective_from' => '2019-07-01',
            'effective_until' => '2026-08-31',
        ]);
        $global = $this->globalAdministrator();

        $this->actingAs($global)
            ->withSession([
                'mfa.verified_at' => now(),
                PlatformMunicipalContextService::SESSION_KEY => $selectedMunicipality->id,
            ])
            ->post(route('admin.programs.store'), [
                'municipality_id' => $tamperedMunicipality->id,
                'regulatory_profile_id' => $profile->id,
                'name' => 'Programa canónico do contexto',
                'slug' => 'programa-canonico-contexto',
                'summary' => 'Resumo público do programa municipal.',
                'description' => 'Descrição pública do programa municipal configurado pelo operador global.',
                'legal_basis' => 'Base legal oficial do procedimento.',
                'starts_at' => '2026-08-01',
                'ends_at' => null,
            ])
            ->assertRedirect();

        $program = Program::query()->where('slug', 'programa-canonico-contexto')->sole();
        $this->assertSame($selectedMunicipality->id, $program->municipality_id);
        $this->assertSame($profile->id, $program->regulatory_profile_id);
    }

    public function test_regulatory_configuration_is_not_available_to_municipal_backoffice(): void
    {
        $municipality = Municipality::factory()->create();
        $municipal = User::factory()->create([
            'municipality_id' => $municipality->id,
            'status' => 'active',
        ]);
        $this->grantRolePermissions('municipal_technician', ['programs.view', 'programs.update']);
        $municipal->assignRole('municipal_technician');

        $this->actingAs($municipal)
            ->withSession(['mfa.verified_at' => now()])
            ->get(route('admin.regulatory-profiles.index'))
            ->assertForbidden();
    }

    public function test_global_administrator_can_create_municipal_overlay_from_selected_context(): void
    {
        $municipality = Municipality::factory()->create();
        $parent = AffordableRentRegulatoryProfile::factory()->create([
            'municipality_id' => null,
            'configuration_status' => RegulatoryConfigurationStatus::Complete,
        ]);
        $global = $this->globalAdministrator();

        $this->actingAs($global)
            ->withSession([
                'mfa.verified_at' => now(),
                PlatformMunicipalContextService::SESSION_KEY => $municipality->id,
            ])
            ->post(route('admin.regulatory-profiles.store'), [
                'scope_type' => 'municipal',
                'parent_profile_id' => $parent->id,
                'legal_regime' => AffordableRentLegalRegime::PaaLegacy2019->value,
                'code' => 'MUN-OVERLAY-2026',
                'version' => '1.0',
                'name' => 'Overlay municipal oficial',
                'legal_basis' => 'Regulamento Municipal oficial.',
                'effective_from' => '2026-01-01',
                'effective_until' => '2026-08-31',
                'configuration_status' => RegulatoryConfigurationStatus::Incomplete->value,
                'official_source' => 'Diário da República e regulamento municipal.',
                'publication_reference' => 'EDITAL/2024',
                'source_version' => 'municipal-2024',
                'maximum_effort_rate_percentage' => '35.00',
                'annual_income_base_limit' => '38632.00',
                'second_person_increment' => '10000.00',
                'additional_person_increment' => '5000.00',
                'eligibility_rules_configured' => '1',
                'typology_rules_configured' => '1',
                'contract_terms_configured' => '1',
            ])
            ->assertRedirect();

        $profile = AffordableRentRegulatoryProfile::query()
            ->where('code', 'MUN-OVERLAY-2026')
            ->sole();

        $this->assertSame($municipality->id, $profile->municipality_id);
        $this->assertSame($parent->id, $profile->parent_profile_id);
        $this->assertSame('draft', $profile->status->value);
    }

    public function test_global_administrator_can_configure_and_validate_rent_limit_table(): void
    {
        $municipality = Municipality::factory()->create(['code' => 'ALCANENA']);
        $profile = AffordableRentRegulatoryProfile::factory()->create([
            'municipality_id' => null,
            'source_version' => 'paa-rendas-2024',
            'rent_limits_configured' => false,
        ]);
        $program = Program::factory()->create([
            'municipality_id' => $municipality->id,
            'regulatory_profile_id' => $profile->id,
            'legal_regime' => $profile->legal_regime->value,
            'starts_at' => '2026-08-01',
        ]);
        $ruleSet = RentRuleSet::factory()->create([
            'program_id' => $program->id,
            'contest_id' => null,
            'regulatory_profile_id' => $profile->id,
        ]);
        $global = $this->globalAdministrator();

        $this->actingAs($global)
            ->withSession([
                'mfa.verified_at' => now(),
                PlatformMunicipalContextService::SESSION_KEY => $municipality->id,
            ])
            ->put(route('admin.regulatory-profiles.rent-limits.update', $profile), [
                'rent_rule_set_id' => $ruleSet->id,
                'source_document' => 'Tabela oficial de limites de renda aplicável.',
                'source_reference' => 'PORTARIA-TABELA-2024',
                'effective_from' => '2026-01-01',
                'effective_until' => '2026-08-31',
                'rows' => [
                    [
                        'typology' => 'T1',
                        'minimum_rent' => '100.00',
                        'maximum_rent' => '500.00',
                        'source_row_reference' => 'Linha T1',
                    ],
                    [
                        'typology' => 'T2',
                        'minimum_rent' => '120.00',
                        'maximum_rent' => '600.00',
                        'source_row_reference' => 'Linha T2',
                    ],
                ],
            ])
            ->assertRedirect();

        $manifest = RentLimitTableManifest::query()->where('rent_rule_set_id', $ruleSet->id)->sole();
        $this->assertSame(['ALCANENA'], $manifest->municipality_coverage);
        $this->assertSame(['T1', 'T2'], $manifest->typology_coverage);
        $this->assertSame(2, $manifest->row_count);
        $this->assertNotNull($manifest->checksum);
        $this->assertSame('configured', $manifest->validation_status->value);
        $this->assertTrue($profile->refresh()->rent_limits_configured);
        $this->assertSame('100.00', $ruleSet->refresh()->minimum_rent);
        $this->assertSame('600.00', $ruleSet->maximum_rent);
    }

    public function test_rent_rule_set_create_renders_form_partial_instead_of_literal_blade_directive(): void
    {
        $municipality = Municipality::factory()->create();
        $global = $this->globalAdministrator();

        $this->actingAs($global)
            ->withSession([
                'mfa.verified_at' => now(),
                PlatformMunicipalContextService::SESSION_KEY => $municipality->id,
            ])
            ->get(route('backoffice.contracts.rent-rule-sets.create'))
            ->assertOk()
            ->assertSee('Nova regra de renda')
            ->assertSee('Taxa de esforço (%)')
            ->assertSee('Renda máxima')
            ->assertDontSee("@include('backoffice.contracts.rent-rule-sets.form')", false);
    }

    private function globalAdministrator(): User
    {
        $actor = User::factory()->withoutMunicipality()->create(['status' => 'active']);
        $actor->assignRole('administrator');
        PlatformOperatorAssignment::factory()->for($actor)->create();

        return $actor->refresh();
    }

    /**
     * @param  list<string>  $permissions
     */
    private function grantRolePermissions(string $roleName, array $permissions): void
    {
        $role = Role::query()->where('name', $roleName)->firstOrFail();
        $permissionIds = Permission::query()->whereIn('name', $permissions)->pluck('id');
        $this->assertCount(count($permissions), $permissionIds);
        $role->permissions()->syncWithoutDetaching($permissionIds);
    }
}
