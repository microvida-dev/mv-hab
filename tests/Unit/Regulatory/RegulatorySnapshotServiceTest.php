<?php

namespace Tests\Unit\Regulatory;

use App\Enums\RegulatoryContext;
use App\Models\AffordableRentRegulatoryProfile;
use App\Models\EligibilityRuleSet;
use App\Models\Municipality;
use App\Models\Program;
use App\Models\User;
use App\Services\Regulatory\RegulatorySnapshotService;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use LogicException;
use Tests\TestCase;

class RegulatorySnapshotServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_locked_snapshot_is_idempotent_and_immutable(): void
    {
        [$program, $profile, $actor] = $this->context();
        $service = app(RegulatorySnapshotService::class);
        $referenceDate = new CarbonImmutable('2026-08-01', 'Europe/Lisbon');
        $snapshot = $service->attach(
            $program,
            $profile,
            RegulatoryContext::ProgramPublication,
            $referenceDate,
            $actor,
            'unit_test',
        );
        $sameSnapshot = $service->attach(
            $program->refresh(),
            $profile,
            RegulatoryContext::ProgramPublication,
            $referenceDate,
            $actor,
            'unit_test_repeat',
        );

        $this->assertTrue($sameSnapshot->is($snapshot));
        $this->assertNotNull($snapshot->locked_at);
        $this->assertSame($program->municipality_id, $snapshot->municipality_id);

        $this->expectException(LogicException::class);
        $snapshot->forceFill(['origin' => 'tampered'])->save();
    }

    public function test_later_rule_and_profile_changes_do_not_rewrite_snapshot(): void
    {
        [$program, $profile, $actor] = $this->context();
        $ruleSet = EligibilityRuleSet::factory()->active()->create([
            'program_id' => $program->id,
            'regulatory_profile_id' => $profile->id,
        ]);
        $snapshot = app(RegulatorySnapshotService::class)->attach(
            $program,
            $profile,
            RegulatoryContext::ProgramPublication,
            new CarbonImmutable('2026-08-01', 'Europe/Lisbon'),
            $actor,
            'unit_test',
        );
        $originalChecksum = $snapshot->checksum;
        $originalParameters = $snapshot->parameters;

        $ruleSet->forceFill(['name' => 'Regra alterada depois do snapshot'])->save();
        $profile->forceFill(['annual_income_base_limit' => '100.00'])->save();
        $snapshot->refresh();

        $this->assertSame($ruleSet->id, $snapshot->rule_sets['eligibility_rule_set_id']);
        $this->assertSame('38632.00', $originalParameters['annual_income_base_limit']);
        $this->assertSame($originalParameters, $snapshot->parameters);
        $this->assertSame($originalChecksum, $snapshot->checksum);
    }

    public function test_snapshot_rejects_profile_from_another_municipality(): void
    {
        $municipality = Municipality::factory()->create();
        $otherMunicipality = Municipality::factory()->create();
        $program = Program::factory()->create([
            'municipality_id' => $municipality->id,
        ]);
        $profile = AffordableRentRegulatoryProfile::factory()->create([
            'municipality_id' => $otherMunicipality->id,
        ]);

        $this->expectException(ValidationException::class);

        app(RegulatorySnapshotService::class)->attach(
            $program,
            $profile,
            RegulatoryContext::ProgramPublication,
            new CarbonImmutable('2026-08-01', 'Europe/Lisbon'),
            User::factory()->create(),
            'unit_test',
        );
    }

    /**
     * @return array{Program, AffordableRentRegulatoryProfile, User}
     */
    private function context(): array
    {
        $profile = AffordableRentRegulatoryProfile::factory()->create();
        $program = Program::factory()->create([
            'regulatory_profile_id' => $profile->id,
            'legal_regime' => $profile->legal_regime,
            'starts_at' => '2026-08-01',
        ]);
        $actor = User::factory()->create(['status' => 'active']);

        return [$program, $profile, $actor];
    }
}
