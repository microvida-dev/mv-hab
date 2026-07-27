<?php

namespace App\Services\Regulatory;

use App\Enums\AffordableRentLegalRegime;
use App\Models\Application;
use App\Models\Contract;
use App\Models\RegulatorySnapshot;
use Illuminate\Support\Collection;

final class LegacyContractInventoryService
{
    /**
     * @return Collection<int, array<string, mixed>>
     */
    public function inventory(): Collection
    {
        $inventory = collect();

        Contract::query()
            ->select([
                'id',
                'program_id',
                'contest_id',
                'application_id',
                'allocation_id',
                'rent_calculation_id',
                'regulatory_snapshot_id',
                'legal_regime',
                'regulatory_classification_status',
            ])
            ->with([
                'regulatorySnapshot:id,regulatory_profile_id,legal_regime,context,source_type,source_id,locked_at',
                'rentCalculation:id,application_id,allocation_id,regulatory_snapshot_id,legal_regime',
                'rentCalculation.regulatorySnapshot:id,regulatory_profile_id,legal_regime,context,source_type,source_id,locked_at',
                'rentCalculation.application:id,program_id,contest_id,regulatory_snapshot_id,legal_regime',
                'rentCalculation.application.regulatorySnapshot:id,regulatory_profile_id,legal_regime,context,source_type,source_id,locked_at',
                'rentCalculation.application.contest:id,program_id,regulatory_profile_id,regulatory_snapshot_id,legal_regime',
                'rentCalculation.application.contest.regulatoryProfile:id,legal_regime,code,version',
                'rentCalculation.application.contest.regulatorySnapshot:id,regulatory_profile_id,legal_regime,context,source_type,source_id,locked_at',
                'rentCalculation.application.program:id,regulatory_profile_id,regulatory_snapshot_id,legal_regime',
                'rentCalculation.application.program.regulatoryProfile:id,legal_regime,code,version',
                'rentCalculation.application.program.regulatorySnapshot:id,regulatory_profile_id,legal_regime,context,source_type,source_id,locked_at',
                'rentCalculation.allocation:id,application_id,program_id,contest_id',
                'rentCalculation.allocation.application:id,program_id,contest_id,regulatory_snapshot_id,legal_regime',
                'allocation:id,application_id,program_id,contest_id',
                'allocation.application:id,program_id,contest_id,regulatory_snapshot_id,legal_regime',
                'allocation.application.regulatorySnapshot:id,regulatory_profile_id,legal_regime,context,source_type,source_id,locked_at',
                'allocation.application.contest:id,program_id,regulatory_profile_id,regulatory_snapshot_id,legal_regime',
                'allocation.application.contest.regulatoryProfile:id,legal_regime,code,version',
                'allocation.application.contest.regulatorySnapshot:id,regulatory_profile_id,legal_regime,context,source_type,source_id,locked_at',
                'allocation.application.program:id,regulatory_profile_id,regulatory_snapshot_id,legal_regime',
                'allocation.application.program.regulatoryProfile:id,legal_regime,code,version',
                'allocation.application.program.regulatorySnapshot:id,regulatory_profile_id,legal_regime,context,source_type,source_id,locked_at',
                'application:id,program_id,contest_id,regulatory_snapshot_id,legal_regime',
                'application.regulatorySnapshot:id,regulatory_profile_id,legal_regime,context,source_type,source_id,locked_at',
                'application.contest:id,program_id,regulatory_profile_id,regulatory_snapshot_id,legal_regime',
                'application.contest.regulatoryProfile:id,legal_regime,code,version',
                'application.contest.regulatorySnapshot:id,regulatory_profile_id,legal_regime,context,source_type,source_id,locked_at',
                'application.program:id,regulatory_profile_id,regulatory_snapshot_id,legal_regime',
                'application.program.regulatoryProfile:id,legal_regime,code,version',
                'application.program.regulatorySnapshot:id,regulatory_profile_id,legal_regime,context,source_type,source_id,locked_at',
                'contest:id,program_id,regulatory_profile_id,regulatory_snapshot_id,legal_regime',
                'contest.regulatoryProfile:id,legal_regime,code,version',
                'contest.regulatorySnapshot:id,regulatory_profile_id,legal_regime,context,source_type,source_id,locked_at',
                'program:id,regulatory_profile_id,regulatory_snapshot_id,legal_regime',
                'program.regulatoryProfile:id,legal_regime,code,version',
                'program.regulatorySnapshot:id,regulatory_profile_id,legal_regime,context,source_type,source_id,locked_at',
            ])
            ->orderBy('id')
            ->chunkById(200, function ($contracts) use ($inventory): void {
                foreach ($contracts as $contract) {
                    $inventory->push($this->classify($contract));
                }
            });

        return $inventory->values();
    }

    /**
     * @return array<string, mixed>
     */
    public function classify(Contract $contract): array
    {
        $calculation = $contract->rentCalculation;
        $allocation = $contract->allocation ?? $calculation?->allocation;
        $applications = collect([
            $contract->application,
            $calculation?->application,
            $allocation?->application,
        ])->filter(fn (mixed $application): bool => $application instanceof Application);
        $applicationIds = $applications->pluck('id')->unique()->values();
        $application = $applications->first();
        $contestIds = collect([
            $contract->contest_id,
            $calculation?->application?->contest_id,
            $allocation?->contest_id,
            $application?->contest_id,
        ])->filter()->map(fn (mixed $id): int => (int) $id)->unique()->values();
        $programIds = collect([
            $contract->program_id,
            $calculation?->application?->program_id,
            $allocation?->program_id,
            $application?->program_id,
        ])->filter()->map(fn (mixed $id): int => (int) $id)->unique()->values();
        $contests = $applications
            ->pluck('contest')
            ->push($contract->contest)
            ->filter()
            ->unique('id')
            ->values();
        $programs = $applications
            ->pluck('program')
            ->push($contract->program)
            ->filter()
            ->unique('id')
            ->values();
        $snapshots = collect([
            $contract->regulatorySnapshot,
            $calculation?->regulatorySnapshot,
            ...$applications->pluck('regulatorySnapshot')->all(),
            ...$contests->pluck('regulatorySnapshot')->all(),
            ...$programs->pluck('regulatorySnapshot')->all(),
        ])->filter(fn (mixed $snapshot): bool => $snapshot instanceof RegulatorySnapshot)
            ->unique('id')
            ->values();
        $profileRegimes = collect([
            ...$contests->pluck('regulatoryProfile.legal_regime')->all(),
            ...$programs->pluck('regulatoryProfile.legal_regime')->all(),
        ])->filter()
            ->map(fn (mixed $regime): string => $this->regimeValue($regime))
            ->unique()
            ->values();
        $snapshotRegimes = $snapshots
            ->map(fn (RegulatorySnapshot $snapshot): string => $snapshot->legal_regime->value)
            ->unique()
            ->values();
        $directRegimes = collect([
            $contract->legal_regime,
            $calculation?->legal_regime,
            $application?->legal_regime,
        ])->filter()
            ->map(fn (mixed $regime): string => $this->regimeValue($regime))
            ->unique()
            ->values();
        $allRegimes = $directRegimes
            ->merge($snapshotRegimes)
            ->merge($profileRegimes)
            ->unique()
            ->sort()
            ->values();
        $classification = 'requires_manual_review';
        $reasons = [];

        if ($calculation === null) {
            $classification = 'missing_rent_calculation';
            $reasons[] = 'rent_calculation_missing';
        } elseif ($applications->isEmpty() && $allocation === null) {
            $classification = 'missing_application_or_allocation';
            $reasons[] = 'application_and_allocation_missing';
        } elseif ($applicationIds->count() > 1) {
            $classification = 'ambiguous';
            $reasons[] = 'conflicting_application_ids';
        } elseif ($contestIds->isEmpty()) {
            $classification = 'missing_contest';
            $reasons[] = 'contest_missing_from_authoritative_chain';
        } elseif ($contestIds->count() > 1) {
            $classification = 'ambiguous';
            $reasons[] = 'conflicting_contest_ids';
        } elseif ($programIds->isEmpty()) {
            $classification = 'missing_program';
            $reasons[] = 'program_missing_from_authoritative_chain';
        } elseif ($programIds->count() > 1) {
            $classification = 'ambiguous';
            $reasons[] = 'conflicting_program_ids';
        } elseif ($allRegimes->count() > 1) {
            $classification = 'ambiguous';
            $reasons[] = 'conflicting_regulatory_regimes';
        } elseif ($directRegimes->count() === 1 && $snapshotRegimes->count() === 1) {
            $classification = $allRegimes->first() === AffordableRentLegalRegime::PaaLegacy2019->value
                ? 'confirmed_paa'
                : 'confirmed_rsaa';
            $reasons[] = 'direct_regime_matches_locked_snapshot';
        } elseif ($snapshotRegimes->count() === 1) {
            $classification = 'classifiable_from_locked_snapshot';
            $reasons[] = 'single_locked_snapshot_regime';
        } elseif ($profileRegimes->isEmpty()) {
            $classification = 'missing_profile';
            $reasons[] = 'regulatory_profile_missing';
        } elseif ($profileRegimes->count() === 1) {
            $classification = 'classifiable_from_profile';
            $reasons[] = 'single_profile_regime';
        } else {
            $reasons[] = 'insufficient_authoritative_evidence';
        }

        return [
            'contract_id' => $contract->id,
            'rent_calculation_id' => $contract->rent_calculation_id,
            'application_ids' => $applicationIds->all(),
            'allocation_id' => $contract->allocation_id ?? $calculation?->allocation_id,
            'contest_ids' => $contestIds->all(),
            'program_ids' => $programIds->all(),
            'regulatory_snapshot_ids' => $snapshots->pluck('id')->sort()->values()->all(),
            'regimes_found' => $allRegimes->all(),
            'classification' => $classification,
            'reasons' => $reasons,
        ];
    }

    private function regimeValue(mixed $regime): string
    {
        return $regime instanceof AffordableRentLegalRegime
            ? $regime->value
            : (string) $regime;
    }
}
