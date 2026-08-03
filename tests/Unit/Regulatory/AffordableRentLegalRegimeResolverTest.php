<?php

namespace Tests\Unit\Regulatory;

use App\Enums\AffordableRentLegalRegime;
use App\Enums\LegalRegimeResolutionStatus;
use App\Enums\RegulatoryContext;
use App\Models\AffordableRentRegulatoryProfile;
use App\Models\Contract;
use App\Models\Municipality;
use App\Models\Program;
use App\Models\User;
use App\Services\Regulatory\AffordableRentLegalRegimeResolver;
use App\Services\Regulatory\RegulatorySnapshotService;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AffordableRentLegalRegimeResolverTest extends TestCase
{
    use RefreshDatabase;

    public function test_transition_boundary_resolves_paa_and_rsaa_in_lisbon_timezone(): void
    {
        $paa = AffordableRentRegulatoryProfile::factory()->create();
        $rsaa = AffordableRentRegulatoryProfile::factory()->rsaaIncomplete()->create();
        $resolver = app(AffordableRentLegalRegimeResolver::class);

        $this->assertTrue($resolver
            ->resolveForDate(new CarbonImmutable('2026-08-31 23:59:59', 'Europe/Lisbon'))
            ->is($paa));
        $this->assertTrue($resolver
            ->resolveForDate(new CarbonImmutable('2026-09-01 00:00:00', 'Europe/Lisbon'))
            ->is($rsaa));
    }

    public function test_timezone_conversion_does_not_shift_the_legal_boundary(): void
    {
        AffordableRentRegulatoryProfile::factory()->create();
        $rsaa = AffordableRentRegulatoryProfile::factory()->rsaaIncomplete()->create();
        $resolver = app(AffordableRentLegalRegimeResolver::class);

        $resolved = $resolver->resolveForDate(
            new CarbonImmutable('2026-08-31 23:30:00', 'UTC'),
        );

        $this->assertTrue($resolved->is($rsaa));
        $this->assertSame(AffordableRentLegalRegime::Rsaa2026, $resolved->legal_regime);
    }

    public function test_published_program_keeps_its_paa_snapshot_after_transition(): void
    {
        $municipality = Municipality::factory()->create();
        $profile = AffordableRentRegulatoryProfile::factory()->create();
        $program = Program::factory()->published()->create([
            'municipality_id' => $municipality->id,
            'regulatory_profile_id' => $profile->id,
            'legal_regime' => $profile->legal_regime,
            'starts_at' => '2026-08-01',
        ]);
        $actor = User::factory()->create(['status' => 'active']);

        app(RegulatorySnapshotService::class)->attach(
            $program,
            $profile,
            RegulatoryContext::ProgramPublication,
            new CarbonImmutable('2026-08-01', 'Europe/Lisbon'),
            $actor,
            'unit_test',
        );

        $resolved = app(AffordableRentLegalRegimeResolver::class)->profileForProgram(
            $program->refresh(),
            new CarbonImmutable('2026-10-01', 'Europe/Lisbon'),
        );

        $this->assertTrue($resolved->is($profile));
        $this->assertSame(AffordableRentLegalRegime::PaaLegacy2019, $resolved->legal_regime);
    }

    public function test_contract_snapshot_preserves_paa_and_ambiguous_contract_requires_review(): void
    {
        $profile = AffordableRentRegulatoryProfile::factory()->create();
        $contract = Contract::factory()->create();
        $actor = User::factory()->create(['status' => 'active']);

        app(RegulatorySnapshotService::class)->attach(
            $contract,
            $profile,
            RegulatoryContext::ContractExecution,
            new CarbonImmutable('2026-08-20', 'Europe/Lisbon'),
            $actor,
            'unit_test',
        );

        $resolver = app(AffordableRentLegalRegimeResolver::class);
        $resolved = $resolver->resolveContract($contract->refresh());

        $this->assertSame(LegalRegimeResolutionStatus::Resolved, $resolved->status);
        $this->assertSame(AffordableRentLegalRegime::PaaLegacy2019, $resolved->regime);
        $this->assertTrue($resolved->profile?->is($profile) === true);

        $ambiguous = $resolver->resolveContract(Contract::factory()->create());

        $this->assertSame(
            LegalRegimeResolutionStatus::RequiresManualReview,
            $ambiguous->status,
        );
        $this->assertNull($ambiguous->regime);
        $this->assertNull($ambiguous->profile);
    }
}
