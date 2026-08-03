<?php

namespace App\Services\Allocation;

use App\Models\AllocationRuleSet;
use App\Models\Application;
use App\Models\DefinitiveList;
use App\Models\RegulatorySnapshot;
use Illuminate\Validation\ValidationException;

class AllocationRuleSetResolver
{
    public function forApplication(Application $application): ?AllocationRuleSet
    {
        $application->loadMissing([
            'regulatorySnapshot',
            'contest.regulatorySnapshot',
            'program.regulatorySnapshot',
        ]);

        $applicationSnapshot = $application->getRelationValue(
            'regulatorySnapshot',
        );
        $contestSnapshot = $application->contest->getRelationValue(
            'regulatorySnapshot',
        );
        $programSnapshot = $application->program->getRelationValue(
            'regulatorySnapshot',
        );
        $snapshot = match (true) {
            $applicationSnapshot instanceof RegulatorySnapshot => $applicationSnapshot,
            $contestSnapshot instanceof RegulatorySnapshot => $contestSnapshot,
            $programSnapshot instanceof RegulatorySnapshot => $programSnapshot,
            default => null,
        };
        $profileId = $snapshot instanceof RegulatorySnapshot
            ? $snapshot->regulatory_profile_id
            : ($application->contest->regulatory_profile_id
                ?? $application->program->regulatory_profile_id);
        $snapshotRuleSetId = $snapshot instanceof RegulatorySnapshot
            ? data_get($snapshot->rule_sets, 'allocation_rule_set_id')
            : null;

        if (is_numeric($snapshotRuleSetId)) {
            $snapshotRule = AllocationRuleSet::query()
                ->active()
                ->whereKey((int) $snapshotRuleSetId)
                ->where('regulatory_profile_id', $profileId)
                ->where(function ($query) use ($application): void {
                    $query
                        ->where('contest_id', $application->contest_id)
                        ->orWhere(function ($fallback) use ($application): void {
                            $fallback
                                ->whereNull('contest_id')
                                ->where('program_id', $application->program_id);
                        });
                })
                ->first();

            if ($snapshotRule instanceof AllocationRuleSet) {
                return $snapshotRule;
            }
        }

        $contestRule = AllocationRuleSet::query()
            ->active()
            ->where('contest_id', $application->contest_id)
            ->when(
                $profileId !== null,
                fn ($query) => $query->where(
                    'regulatory_profile_id',
                    $profileId,
                ),
                fn ($query) => $query->whereNull('regulatory_profile_id'),
            )
            ->latest('id')
            ->first();

        if ($contestRule instanceof AllocationRuleSet) {
            return $contestRule;
        }

        return AllocationRuleSet::query()
            ->active()
            ->where('program_id', $application->program_id)
            ->whereNull('contest_id')
            ->when(
                $profileId !== null,
                fn ($query) => $query->where(
                    'regulatory_profile_id',
                    $profileId,
                ),
                fn ($query) => $query->whereNull('regulatory_profile_id'),
            )
            ->latest('id')
            ->first();
    }

    public function resolveFor(DefinitiveList $list, ?int $ruleSetId = null): AllocationRuleSet
    {
        if ($ruleSetId) {
            return AllocationRuleSet::query()->active()->findOrFail($ruleSetId);
        }

        $contestRule = AllocationRuleSet::query()
            ->active()
            ->where('contest_id', $list->contest_id)
            ->latest()
            ->first();

        if ($contestRule) {
            return $contestRule;
        }

        $programRule = AllocationRuleSet::query()
            ->active()
            ->where('program_id', $list->program_id)
            ->whereNull('contest_id')
            ->latest()
            ->first();

        if (! $programRule) {
            throw ValidationException::withMessages(['allocation_rule_set_id' => 'Não existe regra de atribuição ativa para esta lista.']);
        }

        return $programRule;
    }
}
