<?php

namespace Tests\Unit\Regulatory;

use App\Enums\RentLimitConfigurationStatus;
use App\Enums\RentRuleSetStatus;
use App\Models\AffordableRentRegulatoryProfile;
use App\Models\RentLimitTableManifest;
use App\Models\RentLimitTableRow;
use App\Models\RentRuleSet;
use App\Models\User;
use App\Services\Regulatory\RentLimits\RentLimitTableAuditService;
use App\Services\Regulatory\RentLimits\RentLimitTableChecksumService;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RentLimitTableAuditServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_complete_manifest_is_derived_from_source_rows_and_checksum(): void
    {
        [$profile, $ruleSet, $manifest] = $this->context();

        $result = app(RentLimitTableAuditService::class)->audit(
            $profile,
            $ruleSet,
            new CarbonImmutable('2026-07-15', 'Europe/Lisbon'),
        );

        $this->assertSame(RentLimitConfigurationStatus::Configured, $result->status);
        $this->assertSame(3, $result->actualRowCount);
        $this->assertSame(['TESTE'], $result->municipalities);
        $this->assertSame(['T1', 'T2', 'T3'], $result->typologies);
        $this->assertSame('100.00', $result->minimumRent);
        $this->assertSame('500.00', $result->maximumRent);
        $this->assertSame($manifest->checksum, $result->calculatedChecksum);
        $this->assertSame([], $result->findings);
    }

    public function test_checksum_tampering_and_missing_coverage_fail_closed(): void
    {
        [$profile, $ruleSet, $manifest] = $this->context();
        $manifest->rows()->where('typology', 'T2')->delete();

        $result = app(RentLimitTableAuditService::class)->audit(
            $profile,
            $ruleSet,
            new CarbonImmutable('2026-07-15', 'Europe/Lisbon'),
        );

        $this->assertSame(RentLimitConfigurationStatus::Incomplete, $result->status);
        $this->assertContains('TESTE|T2', $result->missingRows);
        $this->assertContains(
            'O checksum declarado não coincide com o conteúdo instalado.',
            $result->findings,
        );
    }

    public function test_demo_manifest_is_not_valid_outside_demo_mode(): void
    {
        [$profile, $ruleSet] = $this->context();
        config()->set('mvhab.regulatory_demo_mode', false);

        $result = app(RentLimitTableAuditService::class)->audit(
            $profile,
            $ruleSet,
            new CarbonImmutable('2026-07-15', 'Europe/Lisbon'),
        );

        $this->assertSame(RentLimitConfigurationStatus::Incomplete, $result->status);
        $this->assertContains(
            'Dados de demonstração não são válidos fora do modo demo explícito.',
            $result->findings,
        );
    }

    /**
     * @return array{AffordableRentRegulatoryProfile, RentRuleSet, RentLimitTableManifest}
     */
    private function context(): array
    {
        $profile = AffordableRentRegulatoryProfile::factory()->create([
            'source_version' => 'test-rent-table-2026',
            'rent_limits_configured' => true,
        ]);
        $ruleSet = RentRuleSet::factory()->create([
            'regulatory_profile_id' => $profile->id,
            'status' => RentRuleSetStatus::Active,
            'minimum_rent' => '100.00',
            'maximum_rent' => '500.00',
            'effective_from' => '2026-01-01',
            'effective_until' => '2026-12-31',
        ]);
        $manifest = RentLimitTableManifest::factory()->create([
            'regulatory_profile_id' => $profile->id,
            'rent_rule_set_id' => $ruleSet->id,
            'source_version' => $profile->source_version,
            'row_count' => 3,
            'municipality_coverage' => ['TESTE'],
            'typology_coverage' => ['T1', 'T2', 'T3'],
            'validated_by' => User::factory(),
        ]);
        $rows = collect([
            ['T1', '100.00', '200.00'],
            ['T2', '250.00', '350.00'],
            ['T3', '400.00', '500.00'],
        ])->map(fn (array $values): RentLimitTableRow => RentLimitTableRow::factory()->create([
            'manifest_id' => $manifest->id,
            'municipality_code' => 'TESTE',
            'typology' => $values[0],
            'minimum_rent' => $values[1],
            'maximum_rent' => $values[2],
        ]));
        $manifest->forceFill([
            'checksum' => app(RentLimitTableChecksumService::class)
                ->calculate($rows),
        ])->save();

        return [$profile, $ruleSet, $manifest->refresh()];
    }
}
