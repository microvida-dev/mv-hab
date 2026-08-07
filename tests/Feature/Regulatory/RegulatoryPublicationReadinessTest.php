<?php

namespace Tests\Feature\Regulatory;

use App\Enums\AffordableRentLegalRegime;
use App\Enums\ContestDeadlineType;
use App\Enums\ContestStatus;
use App\Enums\ProgramStatus;
use App\Enums\RegulatoryProfileStatus;
use App\Models\AffordableRentRegulatoryProfile;
use App\Models\AllocationRuleSet;
use App\Models\AuditLog;
use App\Models\Contest;
use App\Models\EligibilityRuleSet;
use App\Models\Municipality;
use App\Models\PlatformOperatorAssignment;
use App\Models\Program;
use App\Models\RegulatorySnapshot;
use App\Models\RentLimitTableManifest;
use App\Models\RentLimitTableRow;
use App\Models\RentRuleSet;
use App\Models\TypologyAdequacyRule;
use App\Models\User;
use App\Services\Platform\PlatformMunicipalContextService;
use App\Services\Regulatory\RegulatorySnapshotService;
use App\Services\Regulatory\RentLimits\RentLimitTableChecksumService;
use Database\Seeders\SystemAccessSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use RuntimeException;
use Tests\TestCase;

class RegulatoryPublicationReadinessTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(SystemAccessSeeder::class);
    }

    public function test_paa_program_publication_creates_locked_regulatory_snapshot(): void
    {
        $actor = $this->globalAdministrator();
        $profile = AffordableRentRegulatoryProfile::factory()->create();
        $program = $this->programReadyForPublication($profile);

        $this->actingAs($actor)
            ->withSession(['mfa.verified_at' => now()])
            ->post(route('admin.programs.publish', $program))
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $program->refresh();
        $this->assertSame(ProgramStatus::Published, $program->status);
        $this->assertSame(AffordableRentLegalRegime::PaaLegacy2019, $program->legal_regime);
        $this->assertNotNull($program->regulatory_snapshot_id);
        $this->assertDatabaseHas('regulatory_snapshots', [
            'id' => $program->regulatory_snapshot_id,
            'source_type' => $program->getMorphClass(),
            'source_id' => $program->id,
            'context' => 'program_publication',
            'legal_regime' => AffordableRentLegalRegime::PaaLegacy2019->value,
        ]);

        $this->actingAs($actor)
            ->withSession(['mfa.verified_at' => now()])
            ->post(route('admin.programs.publish', $program->fresh()))
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $this->assertSame(1, RegulatorySnapshot::query()
            ->where('source_type', $program->getMorphClass())
            ->where('source_id', $program->id)
            ->where('context', 'program_publication')
            ->count());
    }

    public function test_incomplete_rsaa_program_publication_is_rejected(): void
    {
        $actor = $this->globalAdministrator();
        $profile = AffordableRentRegulatoryProfile::factory()->rsaaIncomplete()->create();
        $program = $this->programReadyForPublication($profile, '2026-09-01');

        $this->actingAs($actor)
            ->withSession(['mfa.verified_at' => now()])
            ->post(route('admin.programs.publish', $program))
            ->assertRedirect()
            ->assertSessionHasErrors('regulatory');

        $this->assertSame(ProgramStatus::Draft, $program->fresh()->status);
        $this->assertNull($program->fresh()->regulatory_snapshot_id);
    }

    public function test_program_without_real_rule_sets_cannot_be_published(): void
    {
        $actor = $this->globalAdministrator();
        $profile = AffordableRentRegulatoryProfile::factory()->create();
        $program = $this->programReadyForPublication(
            $profile,
            withRuleSets: false,
        );

        $this->actingAs($actor)
            ->withSession(['mfa.verified_at' => now()])
            ->post(route('admin.programs.publish', $program))
            ->assertRedirect()
            ->assertSessionHasErrors('regulatory');

        $this->assertSame(ProgramStatus::Draft, $program->fresh()->status);
        $this->assertNull($program->fresh()->regulatory_snapshot_id);
    }

    public function test_profile_archived_before_locked_readiness_keeps_program_draft(): void
    {
        $actor = $this->globalAdministrator();
        $profile = AffordableRentRegulatoryProfile::factory()->create();
        $program = $this->programReadyForPublication($profile);
        $profile->forceFill(['status' => RegulatoryProfileStatus::Archived])->save();

        $this->actingAs($actor)
            ->withSession(['mfa.verified_at' => now()])
            ->post(route('admin.programs.publish', $program))
            ->assertRedirect()
            ->assertSessionHasErrors('regulatory');

        $this->assertSame(ProgramStatus::Draft, $program->fresh()->status);
        $this->assertNull($program->fresh()->regulatory_snapshot_id);
    }

    public function test_snapshot_failure_rolls_back_publication_and_publish_audit(): void
    {
        $actor = $this->globalAdministrator();
        $profile = AffordableRentRegulatoryProfile::factory()->create();
        $program = $this->programReadyForPublication($profile);
        $auditCount = AuditLog::query()->count();
        $snapshots = Mockery::mock(RegulatorySnapshotService::class);
        $snapshots->shouldReceive('attach')
            ->once()
            ->andThrow(new RuntimeException('Falha de snapshot simulada.'));
        $this->app->instance(RegulatorySnapshotService::class, $snapshots);
        $this->withoutExceptionHandling();

        try {
            $this->actingAs($actor)
                ->withSession(['mfa.verified_at' => now()])
                ->post(route('admin.programs.publish', $program));
            $this->fail('A falha de snapshot deveria ter interrompido a publicação.');
        } catch (RuntimeException $exception) {
            $this->assertSame('Falha de snapshot simulada.', $exception->getMessage());
        }

        $this->assertSame(ProgramStatus::Draft, $program->fresh()->status);
        $this->assertNull($program->fresh()->regulatory_snapshot_id);
        $this->assertSame($auditCount, AuditLog::query()->count());
    }

    public function test_incomplete_rsaa_contest_publication_is_rejected(): void
    {
        $actor = $this->globalAdministrator();
        $profile = AffordableRentRegulatoryProfile::factory()->rsaaIncomplete()->create();
        $program = Program::factory()->published()->create([
            'regulatory_profile_id' => $profile->id,
            'legal_regime' => $profile->legal_regime,
            'starts_at' => '2026-09-01',
        ]);
        $contest = Contest::factory()->for($program)->create([
            'regulatory_profile_id' => $profile->id,
            'legal_regime' => $profile->legal_regime,
            'opens_at' => '2026-09-10 09:00:00',
            'closes_at' => '2026-10-10 17:00:00',
        ]);
        $contest->deadlines()->create([
            'type' => ContestDeadlineType::Applications,
            'label' => 'Prazo de candidatura',
            'starts_at' => '2026-09-10 09:00:00',
            'ends_at' => '2026-10-10 17:00:00',
            'sort_order' => 1,
        ]);

        $this->actingAs($actor)
            ->withSession(['mfa.verified_at' => now()])
            ->post(route('admin.contests.publish', $contest))
            ->assertRedirect()
            ->assertSessionHasErrors('regulatory');

        $this->assertSame(ContestStatus::Draft, $contest->fresh()->status);
        $this->assertNull($contest->fresh()->regulatory_snapshot_id);
    }

    public function test_browser_payload_cannot_select_profile_incompatible_with_start_date(): void
    {
        $actor = $this->globalAdministrator();
        $municipality = Municipality::factory()->create();
        $paa = AffordableRentRegulatoryProfile::factory()->create();

        $this->actingAs($actor)
            ->withSession([
                'mfa.verified_at' => now(),
                PlatformMunicipalContextService::SESSION_KEY => $municipality->id,
            ])
            ->post(route('admin.programs.store'), [
                'municipality_id' => $municipality->id,
                'regulatory_profile_id' => $paa->id,
                'name' => 'Programa incompatível',
                'summary' => 'Programa de teste para validar a fronteira regulamentar.',
                'description' => 'O payload tenta escolher um perfil PAA numa data RSAA.',
                'legal_basis' => 'Base legal de teste.',
                'starts_at' => '2026-09-01',
                'rules' => [],
            ])
            ->assertRedirect()
            ->assertSessionHasErrors('regulatory_profile_id');

        $this->assertDatabaseMissing('programs', ['name' => 'Programa incompatível']);
    }

    public function test_municipal_actor_cannot_publish_program_from_another_municipality(): void
    {
        $municipalityA = Municipality::factory()->create();
        $municipalityB = Municipality::factory()->create();
        $actor = User::factory()->create([
            'municipality_id' => $municipalityA->id,
            'status' => 'active',
        ]);
        $actor->assignRole('administrator');
        $profile = AffordableRentRegulatoryProfile::factory()->create();
        $program = $this->programReadyForPublication(
            $profile,
            municipality: $municipalityB,
        );

        $this->actingAs($actor)
            ->withSession(['mfa.verified_at' => now()])
            ->post(route('admin.programs.publish', $program))
            ->assertForbidden();

        $this->assertSame(ProgramStatus::Draft, $program->fresh()->status);
    }

    public function test_candidate_is_excluded_and_auditor_remains_read_only(): void
    {
        $candidate = User::factory()->create(['status' => 'active']);
        $candidate->assignRole('candidate');
        $auditor = User::factory()->create(['status' => 'active']);
        $auditor->assignRole('auditor');

        $this->actingAs($candidate)
            ->get(route('admin.programs.index'))
            ->assertForbidden();

        $this->actingAs($auditor)
            ->withSession(['mfa.verified_at' => now()])
            ->get(route('admin.programs.index'))
            ->assertOk();

        $this->actingAs($auditor)
            ->withSession(['mfa.verified_at' => now()])
            ->get(route('admin.programs.create'))
            ->assertForbidden();
    }

    private function globalAdministrator(): User
    {
        $actor = User::factory()->withoutMunicipality()->create([
            'status' => 'active',
        ]);
        $actor->assignRole('administrator');
        PlatformOperatorAssignment::factory()->for($actor)->create();

        return $actor->refresh();
    }

    private function programReadyForPublication(
        AffordableRentRegulatoryProfile $profile,
        string $startsAt = '2026-08-01',
        ?Municipality $municipality = null,
        bool $withRuleSets = true,
    ): Program {
        $municipality ??= Municipality::factory()->create();
        $program = Program::factory()->create([
            'municipality_id' => $municipality->id,
            'regulatory_profile_id' => $profile->id,
            'legal_regime' => $profile->legal_regime,
            'legal_basis' => 'Base legal de teste.',
            'starts_at' => $startsAt,
        ]);
        $program->rules()->create([
            'title' => 'Regra pública',
            'description' => 'Regra pública necessária à publicação.',
            'sort_order' => 1,
            'effective_from' => $startsAt,
        ]);

        if ($withRuleSets) {
            EligibilityRuleSet::factory()->active()->create([
                'program_id' => $program->id,
                'contest_id' => null,
                'regulatory_profile_id' => $profile->id,
                'starts_at' => $startsAt,
                'ends_at' => null,
            ]);
            $rentRuleSet = RentRuleSet::factory()->create([
                'program_id' => $program->id,
                'contest_id' => null,
                'regulatory_profile_id' => $profile->id,
            ]);
            $this->createRentManifest($profile, $rentRuleSet);
            TypologyAdequacyRule::factory()->create([
                'program_id' => $program->id,
                'contest_id' => null,
                'regulatory_profile_id' => $profile->id,
            ]);
            AllocationRuleSet::factory()->create([
                'program_id' => $program->id,
                'contest_id' => null,
                'regulatory_profile_id' => $profile->id,
            ]);
        }

        return $program;
    }

    private function createRentManifest(
        AffordableRentRegulatoryProfile $profile,
        RentRuleSet $ruleSet,
    ): void {
        $manifest = RentLimitTableManifest::factory()->create([
            'regulatory_profile_id' => $profile->id,
            'rent_rule_set_id' => $ruleSet->id,
            'source_version' => $profile->source_version,
            'effective_from' => '2026-01-01',
            'effective_until' => '2026-12-31',
            'row_count' => 1,
            'municipality_coverage' => ['TESTE'],
            'typology_coverage' => ['T1'],
            'validated_by' => User::factory(),
        ]);
        $row = RentLimitTableRow::factory()->create([
            'manifest_id' => $manifest->id,
            'municipality_code' => 'TESTE',
            'typology' => 'T1',
            'minimum_rent' => $ruleSet->minimum_rent,
            'maximum_rent' => $ruleSet->maximum_rent,
        ]);
        $manifest->forceFill([
            'checksum' => app(RentLimitTableChecksumService::class)->calculate([$row]),
        ])->save();
    }
}
