<?php

namespace Tests\Unit\Regulatory;

use App\Enums\AffordableRentLegalRegime;
use App\Enums\RegulatoryContext;
use App\Models\AffordableRentRegulatoryProfile;
use App\Models\Application;
use App\Models\Contest;
use App\Models\Contract;
use App\Models\Program;
use App\Models\RegulatorySnapshot;
use App\Models\RentCalculation;
use App\Services\Regulatory\LegacyContractInventoryService;
use Tests\TestCase;

class LegacyContractInventoryServiceTest extends TestCase
{
    public function test_contract_with_matching_direct_and_snapshot_regime_is_confirmed_paa(): void
    {
        $contract = $this->contract(AffordableRentLegalRegime::PaaLegacy2019);

        $result = app(LegacyContractInventoryService::class)->classify($contract);

        $this->assertSame('confirmed_paa', $result['classification']);
        $this->assertSame(['direct_regime_matches_locked_snapshot'], $result['reasons']);
    }

    public function test_contract_with_matching_direct_and_snapshot_regime_is_confirmed_rsaa(): void
    {
        $contract = $this->contract(AffordableRentLegalRegime::Rsaa2026);

        $result = app(LegacyContractInventoryService::class)->classify($contract);

        $this->assertSame('confirmed_rsaa', $result['classification']);
    }

    public function test_missing_calculation_is_reported_before_regulatory_inference(): void
    {
        $contract = $this->contract(AffordableRentLegalRegime::PaaLegacy2019);
        $contract->forceFill(['rent_calculation_id' => null]);
        $contract->setRelation('rentCalculation', null);

        $result = app(LegacyContractInventoryService::class)->classify($contract);

        $this->assertSame('missing_rent_calculation', $result['classification']);
    }

    public function test_conflicting_snapshot_and_profile_is_ambiguous(): void
    {
        $contract = $this->contract(AffordableRentLegalRegime::PaaLegacy2019);
        $contract->program->regulatoryProfile->forceFill([
            'legal_regime' => AffordableRentLegalRegime::Rsaa2026,
        ]);

        $result = app(LegacyContractInventoryService::class)->classify($contract);

        $this->assertSame('ambiguous', $result['classification']);
        $this->assertContains('conflicting_regulatory_regimes', $result['reasons']);
    }

    public function test_classification_output_is_deterministic_and_contains_only_technical_keys(): void
    {
        $contract = $this->contract(AffordableRentLegalRegime::PaaLegacy2019);
        $service = app(LegacyContractInventoryService::class);

        $first = $service->classify($contract);
        $second = $service->classify($contract);

        $this->assertSame($first, $second);
        $this->assertSame([
            'contract_id',
            'rent_calculation_id',
            'application_ids',
            'allocation_id',
            'contest_ids',
            'program_ids',
            'regulatory_snapshot_ids',
            'regimes_found',
            'classification',
            'reasons',
        ], array_keys($first));
    }

    private function contract(AffordableRentLegalRegime $regime): Contract
    {
        $profile = new AffordableRentRegulatoryProfile([
            'legal_regime' => $regime,
            'code' => 'TEST',
            'version' => '1',
        ]);
        $profile->setAttribute('id', 60);
        $snapshot = new RegulatorySnapshot([
            'legal_regime' => $regime,
            'context' => RegulatoryContext::ContractExecution,
            'locked_at' => now(),
        ]);
        $snapshot->setAttribute('id', 50);
        $program = new Program([
            'regulatory_profile_id' => 60,
            'regulatory_snapshot_id' => 50,
            'legal_regime' => $regime,
        ]);
        $program->setAttribute('id', 10);
        $program->setRelation('regulatoryProfile', $profile);
        $program->setRelation('regulatorySnapshot', $snapshot);
        $contest = new Contest([
            'program_id' => 10,
            'regulatory_profile_id' => 60,
            'regulatory_snapshot_id' => 50,
            'legal_regime' => $regime,
        ]);
        $contest->setAttribute('id', 20);
        $contest->setRelation('regulatoryProfile', $profile);
        $contest->setRelation('regulatorySnapshot', $snapshot);
        $application = new Application([
            'program_id' => 10,
            'contest_id' => 20,
            'regulatory_snapshot_id' => 50,
            'legal_regime' => $regime,
        ]);
        $application->setAttribute('id', 30);
        $application->setRelation('program', $program);
        $application->setRelation('contest', $contest);
        $application->setRelation('regulatorySnapshot', $snapshot);
        $calculation = new RentCalculation([
            'application_id' => 30,
            'allocation_id' => null,
            'regulatory_snapshot_id' => 50,
            'legal_regime' => $regime,
        ]);
        $calculation->setAttribute('id', 40);
        $calculation->setRelation('application', $application);
        $calculation->setRelation('allocation', null);
        $calculation->setRelation('regulatorySnapshot', $snapshot);
        $contract = new Contract([
            'program_id' => 10,
            'contest_id' => 20,
            'application_id' => 30,
            'allocation_id' => null,
            'rent_calculation_id' => 40,
            'regulatory_snapshot_id' => 50,
            'legal_regime' => $regime,
        ]);
        $contract->setAttribute('id', 70);
        $contract->setRelation('program', $program);
        $contract->setRelation('contest', $contest);
        $contract->setRelation('application', $application);
        $contract->setRelation('allocation', null);
        $contract->setRelation('rentCalculation', $calculation);
        $contract->setRelation('regulatorySnapshot', $snapshot);

        return $contract;
    }
}
