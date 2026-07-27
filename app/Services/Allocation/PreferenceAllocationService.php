<?php

namespace App\Services\Allocation;

use App\DataTransferObjects\AllocationExecutionResult;
use App\Enums\AllocationStatus;
use App\Enums\ApplicationSnapshotType;
use App\Enums\HousingCompatibilityStatus;
use App\Enums\HousingUnitStatus;
use App\Models\AllocationRun;
use App\Models\ContestHousingUnit;
use App\Models\DefinitiveListEntry;
use App\Models\HousingPreference;
use App\Models\User;
use App\Services\Applications\ApplicationHousingPreferenceSourceResolver;
use App\Services\Audit\AuditLogger;
use App\Support\AuditEvents;
use Illuminate\Support\Collection;

class PreferenceAllocationService extends RankingAllocationService
{
    public function __construct(
        TypologyAdequacyService $typologyService,
        ContestHousingUnitService $contestHousingUnitService,
        private readonly AuditLogger $auditLogger,
        private readonly ApplicationHousingPreferenceSourceResolver $preferenceSource,
    ) {
        parent::__construct($typologyService, $contestHousingUnitService);
    }

    public function allocate(AllocationRun $run, User $actor): AllocationExecutionResult
    {
        $entries = $this->requiredDefinitiveList($run)->entries()
            ->eligibleForAllocation()
            ->with([
                'application.housingPreferences',
                'application.regulatorySnapshot',
                'application.snapshots',
            ])
            ->orderBy('rank_position')
            ->get();
        $availableUnits = ContestHousingUnit::query()
            ->available()
            ->where('contest_id', $run->contest_id)
            ->where(function ($query): void {
                $query->whereNull('availability_starts_at')
                    ->orWhere('availability_starts_at', '<=', now());
            })
            ->where(function ($query): void {
                $query->whereNull('availability_ends_at')
                    ->orWhere('availability_ends_at', '>=', now());
            })
            ->whereHas(
                'housingUnit',
                fn ($query) => $query->where(
                    'status',
                    HousingUnitStatus::Available->value,
                ),
            )
            ->with('housingUnit')
            ->orderBy('id')
            ->get();
        $allocations = collect();
        $reserveEntries = collect();

        foreach ($entries as $entry) {
            $application = $this->requiredApplication($entry);
            $unit = $this->preferredAvailableUnit($entry, $availableUnits);

            if (! $unit) {
                $reserveEntries->push($entry);
                $this->auditLogger->record(
                    AuditEvents::CREATE,
                    $application,
                    'allocations',
                    'reserve_by_preference_unavailability',
                    'Candidatura encaminhada para reserva por indisponibilidade das preferências.',
                    metadata: [
                        'allocation_run_id' => $run->id,
                        'application_id' => $application->id,
                        'locked_preferences_count' => $application
                            ->housingPreferences
                            ->whereNotNull('locked_at')
                            ->count(),
                    ],
                );

                continue;
            }

            $allocation = $this->createAllocation($run, $entry, $unit, $actor);
            $preference = $application->housingPreferences
                ->firstWhere('contest_housing_unit_id', $unit->id);

            if ($preference instanceof HousingPreference) {
                $allocation->forceFill([
                    'preference_order' => $preference->preference_order,
                    'status' => AllocationStatus::Proposed,
                ])->save();
                $this->auditLogger->record(
                    AuditEvents::CREATE,
                    $allocation,
                    'allocations',
                    'allocation_by_locked_preference',
                    'Habitação atribuída segundo a ordem de preferência submetida.',
                    metadata: [
                        'allocation_run_id' => $run->id,
                        'application_id' => $application->id,
                        'preference_order' => $preference->preference_order,
                    ],
                );
            }

            $allocations->push($allocation->refresh());
            $availableUnits = $availableUnits->reject(fn (ContestHousingUnit $candidate) => $candidate->id === $unit->id)->values();
        }

        return new AllocationExecutionResult(
            allocationRun: $run,
            allocations: $allocations,
            reserveEntries: $reserveEntries,
        );
    }

    /** @param  Collection<int, ContestHousingUnit>  $availableUnits */
    private function preferredAvailableUnit(
        DefinitiveListEntry $entry,
        Collection $availableUnits,
    ): ?ContestHousingUnit {
        $application = $this->requiredApplication($entry);
        $regulatorySnapshotId = $application->regulatory_snapshot_id;
        $finalSnapshot = $application->snapshots->firstWhere(
            'snapshot_type',
            ApplicationSnapshotType::HousingPreferences,
        );
        $snapshotData = $finalSnapshot?->getAttribute('data');
        $snapshotPreferences = is_array($snapshotData)
            ? collect($snapshotData)
            : collect();

        if (
            ! $this->preferenceSource->source($application)->isOfficial()
            || $snapshotPreferences->isEmpty()
        ) {
            return null;
        }

        foreach (
            $application->housingPreferences
                ->filter(
                    fn (HousingPreference $preference): bool => $preference->locked_at !== null
                        && $preference->submitted_at !== null
                        && $preference->invalidated_at === null
                        && $preference->compatibility_status === HousingCompatibilityStatus::Compatible
                        && $regulatorySnapshotId !== null
                        && $preference->regulatory_snapshot_id === $regulatorySnapshotId,
                )
                ->filter(
                    fn (HousingPreference $preference): bool => $preference->preference_order >= 1
                        && $preference->preference_order <= 3
                        && $snapshotPreferences->contains(
                            fn (mixed $snapshot): bool => is_array($snapshot)
                                && ($snapshot['source'] ?? null)
                                    === 'housing_preferences'
                                && (int) ($snapshot['contest_housing_unit_id'] ?? 0)
                                    === $preference->contest_housing_unit_id
                                && (int) ($snapshot['preference_order'] ?? 0)
                                    === $preference->preference_order
                                && (int) ($snapshot['regulatory_snapshot_id'] ?? 0)
                                    === $preference->regulatory_snapshot_id,
                        ),
                )
                ->sortBy('preference_order') as $preference
        ) {
            $unit = $availableUnits->firstWhere('id', $preference->contest_housing_unit_id);

            if ($unit instanceof ContestHousingUnit) {
                return $unit;
            }
        }

        return null;
    }
}
