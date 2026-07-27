<?php

namespace App\Services\Allocation;

use App\Data\Applications\CompatibleHousingOptionData;
use App\Enums\ApplicationPreferenceSource;
use App\Enums\ApplicationSnapshotType;
use App\Enums\ApplicationStatus;
use App\Enums\HousingCompatibilityStatus;
use App\Models\AllocationRuleSet;
use App\Models\Application;
use App\Models\ContestHousingUnit;
use App\Models\HousingPreference;
use App\Models\User;
use App\Services\Applications\ApplicationHousingPreferenceSourceResolver;
use App\Services\Applications\HousingCompatibilityService;
use App\Services\Audit\AuditLogger;
use App\Support\AuditEvents;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection as SupportCollection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class HousingPreferenceService
{
    public function __construct(
        private readonly AuditLogger $auditLogger,
        private readonly HousingCompatibilityService $compatibility,
        private readonly AllocationRuleSetResolver $allocationRules,
        private readonly ApplicationHousingPreferenceSourceResolver $preferenceSource,
    ) {}

    /**
     * @return Collection<int, ContestHousingUnit>
     */
    public function availableFor(Application $application): Collection
    {
        /** @var Collection<int, ContestHousingUnit> $units */
        $units = new Collection(
            $this->optionsFor($application)
                ->map(fn (CompatibleHousingOptionData $option) => $option->unit)
                ->all(),
        );

        return $units;
    }

    /**
     * @return SupportCollection<int, CompatibleHousingOptionData>
     */
    public function optionsFor(Application $application): SupportCollection
    {
        return $this->compatibility->optionsFor($application);
    }

    /**
     * @return array{
     *     household_members: int,
     *     income_complete: bool,
     *     annual_income: string|null,
     *     annual_income_limit: string|null,
     *     monthly_income: string|null,
     *     maximum_monthly_rent: string|null,
     *     maximum_effort_rate_percentage: string|null,
     *     adequate_typologies: list<string>,
     *     regulatory_regime: string|null,
     *     regulatory_profile: string|null,
     *     configuration_complete: bool
     * }
     */
    public function compatibilitySummary(Application $application): array
    {
        return $this->compatibility->summaryFor($application);
    }

    /**
     * @return array{
     *     enabled: bool,
     *     required: bool,
     *     minimum: int,
     *     maximum: int,
     *     starts_at: Carbon|null,
     *     ends_at: Carbon|null
     * }
     */
    public function selectionConfiguration(Application $application): array
    {
        $ruleSet = $this->allocationRules->forApplication($application);
        $enabled = $ruleSet instanceof AllocationRuleSet
            && $ruleSet->allow_preferences;
        $minimum = $enabled && $ruleSet->preferences_required_before_submission
            ? max(1, (int) $ruleSet->minimum_preferences)
            : 0;
        $maximum = $enabled
            ? max($minimum, (int) $ruleSet->maximum_preferences)
            : 0;

        return [
            'enabled' => $enabled,
            'required' => $enabled
                && $ruleSet->preferences_required_before_submission,
            'minimum' => $minimum,
            'maximum' => $maximum,
            'starts_at' => $ruleSet?->preference_selection_starts_at,
            'ends_at' => $ruleSet?->preference_selection_ends_at,
        ];
    }

    /**
     * @param  list<array<string, mixed>>  $preferences
     */
    public function replace(Application $application, array $preferences, User $candidate, bool $submit = false): void
    {
        if ($application->user_id !== $candidate->id) {
            throw ValidationException::withMessages(['application' => 'Só pode alterar preferências da sua candidatura.']);
        }

        DB::transaction(function () use ($application, $preferences, $candidate, $submit): void {
            $lockedApplication = Application::query()
                ->lockForUpdate()
                ->findOrFail($application->id);
            $this->assertEditable($lockedApplication);
            $this->assertNoAllocations($lockedApplication);
            $this->assertNoLockedPreferences($lockedApplication);
            $this->assertNoFinalPreferenceSnapshot($lockedApplication);
            $ruleSet = $this->allocationRules->forApplication($lockedApplication);
            $this->assertSelectionStructure($preferences, $ruleSet, $submit);
            $options = $this->optionsFor($lockedApplication)
                ->keyBy(fn (CompatibleHousingOptionData $option): int => $option->unit->id);
            $previousOrders = $lockedApplication->housingPreferences()
                ->whereNull('locked_at')
                ->orderBy('preference_order')
                ->pluck('preference_order', 'contest_housing_unit_id')
                ->map(fn ($order): int => (int) $order)
                ->all();

            HousingPreference::withTrashed()
                ->where('application_id', $lockedApplication->id)
                ->whereNull('locked_at')
                ->forceDelete();

            foreach ($preferences as $preference) {
                $unitId = (int) $preference['contest_housing_unit_id'];
                $option = $options->get($unitId);

                if (! $option instanceof CompatibleHousingOptionData) {
                    throw ValidationException::withMessages([
                        'preferences' => 'Uma das habitações selecionadas deixou de estar disponível ou compatível.',
                    ]);
                }

                $preferenceModel = new HousingPreference([
                    'preference_order' => (int) $preference['preference_order'],
                    'notes' => $preference['notes'] ?? null,
                    'compatibility_status' => $option->compatibility->status,
                    'compatibility_snapshot' => $option->compatibility->snapshot,
                    'evaluated_at' => now(),
                    'invalidated_at' => null,
                    'invalidation_reason' => null,
                ]);
                $preferenceModel->forceFill([
                    'application_id' => $lockedApplication->id,
                    'user_id' => $candidate->id,
                    'contest_id' => $lockedApplication->contest_id,
                    'contest_housing_unit_id' => $option->unit->id,
                    'housing_unit_id' => $option->unit->housing_unit_id,
                    'regulatory_snapshot_id' => $option->compatibility->snapshot['regulatory_snapshot_id'] ?? null,
                    'submitted_at' => $submit ? now() : null,
                    'locked_at' => null,
                ])->save();
            }

            $this->preferenceSource->markOfficial($lockedApplication);

            $newOrders = collect($preferences)
                ->mapWithKeys(fn (array $preference): array => [
                    (int) $preference['contest_housing_unit_id'] => (int) $preference['preference_order'],
                ])
                ->all();
            $reordered = ! $submit
                && $previousOrders !== []
                && array_keys($previousOrders) === array_keys($newOrders)
                && $previousOrders !== $newOrders;
            $action = match (true) {
                $submit => 'housing_preferences_submitted',
                $reordered => 'housing_preferences_reordered',
                default => 'housing_preferences_updated',
            };

            $this->auditLogger->record(
                AuditEvents::UPDATE,
                $lockedApplication,
                'allocations',
                $action,
                $submit
                    ? 'Preferências de habitação confirmadas pelo candidato.'
                    : ($reordered
                        ? 'Ordem das preferências de habitação atualizada.'
                        : 'Preferências de habitação atualizadas.'),
                metadata: [
                    'application_id' => $lockedApplication->id,
                    'preferences_count' => count($preferences),
                    'orders' => collect($preferences)
                        ->pluck('preference_order')
                        ->map(fn ($order): int => (int) $order)
                        ->all(),
                ],
            );
        });
    }

    /**
     * @return array{passed: bool, message: string, route: string, routeParameters: array<string, string>, count: int, minimum: int, maximum: int}
     */
    public function readinessForSubmission(Application $application): array
    {
        $ruleSet = $this->allocationRules->forApplication($application);
        $preferences = $application->housingPreferences()->get();

        if (! $ruleSet instanceof AllocationRuleSet || ! $ruleSet->allow_preferences) {
            return [
                'passed' => true,
                'message' => 'Este concurso não exige seleção de habitações antes da submissão.',
                'route' => 'candidate.housing-preferences.edit',
                'routeParameters' => ['application' => (string) $application->getRouteKey()],
                'count' => $preferences->count(),
                'minimum' => 0,
                'maximum' => 0,
            ];
        }

        $minimum = $ruleSet->preferences_required_before_submission
            ? max(1, (int) $ruleSet->minimum_preferences)
            : 0;
        $maximum = max($minimum, (int) $ruleSet->maximum_preferences);
        $count = $preferences->count();
        $valid = $preferences->every(
            fn (HousingPreference $preference): bool => $preference->compatibility_status === HousingCompatibilityStatus::Compatible
                && $preference->invalidated_at === null
                && $preference->locked_at === null,
        );
        $passed = $count >= $minimum && $count <= $maximum && $valid;

        return [
            'passed' => $passed,
            'message' => $passed
                ? 'As habitações pretendidas estão selecionadas e validadas.'
                : 'Selecione pelo menos uma habitação compatível e confirme novamente as escolhas.',
            'route' => 'candidate.housing-preferences.edit',
            'routeParameters' => ['application' => (string) $application->getRouteKey()],
            'count' => $count,
            'minimum' => $minimum,
            'maximum' => $maximum,
        ];
    }

    public function revalidateAndLockForSubmission(
        Application $application,
        User $actor,
        Carbon $submittedAt,
    ): void {
        $ruleSet = $this->allocationRules->forApplication($application);

        if (
            $ruleSet?->allow_preferences === true
            && $this->preferenceSource->source($application)
                === ApplicationPreferenceSource::Uninitialized
        ) {
            $this->preferenceSource->markOfficial($application, $submittedAt);
        }

        if (
            $ruleSet?->allow_preferences === true
            && ! $this->preferenceSource->source($application)->isOfficial()
        ) {
            throw ValidationException::withMessages([
                'preferences' => 'As preferências habitacionais não têm uma fonte oficial confirmada.',
            ]);
        }

        $preferences = $application->housingPreferences()
            ->lockForUpdate()
            ->orderBy('preference_order')
            ->get();
        $payload = array_values($preferences
            ->map(fn (HousingPreference $preference): array => [
                'contest_housing_unit_id' => $preference->contest_housing_unit_id,
                'preference_order' => $preference->preference_order,
            ])
            ->all());
        $this->assertSelectionStructure($payload, $ruleSet, true);

        foreach ($preferences as $preference) {
            $unit = ContestHousingUnit::query()
                ->whereKey($preference->contest_housing_unit_id)
                ->lockForUpdate()
                ->with('housingUnit')
                ->first();

            if (! $unit instanceof ContestHousingUnit) {
                throw ValidationException::withMessages([
                    'preferences' => 'Uma habitação selecionada deixou de estar disponível.',
                ]);
            }

            $result = $this->compatibility->evaluate($application, $unit);

            if (! $result->compatible) {
                throw ValidationException::withMessages([
                    'preferences' => 'Uma habitação selecionada deixou de estar disponível ou compatível. Reveja as habitações pretendidas.',
                ]);
            }

            $preference->forceFill([
                'compatibility_status' => $result->status,
                'compatibility_snapshot' => $result->snapshot,
                'regulatory_snapshot_id' => $result->snapshot['regulatory_snapshot_id'] ?? null,
                'evaluated_at' => $submittedAt,
                'invalidated_at' => null,
                'invalidation_reason' => null,
                'submitted_at' => $submittedAt,
                'locked_at' => $submittedAt,
            ])->save();
        }

        if ($preferences->isNotEmpty()) {
            $this->auditLogger->record(
                AuditEvents::UPDATE,
                $application,
                'allocations',
                'housing_preferences_revalidated',
                'Preferências habitacionais revalidadas no servidor durante a submissão.',
                metadata: [
                    'application_id' => $application->id,
                    'preferences_count' => $preferences->count(),
                    'actor_id' => $actor->id,
                ],
            );
            $this->auditLogger->record(
                AuditEvents::UPDATE,
                $application,
                'allocations',
                'housing_preferences_locked',
                'Preferências habitacionais revalidadas e bloqueadas na submissão.',
                metadata: [
                    'application_id' => $application->id,
                    'preferences_count' => $preferences->count(),
                    'actor_id' => $actor->id,
                ],
            );
        }
    }

    private function assertEditable(Application $application): void
    {
        if ($application->status !== ApplicationStatus::Draft) {
            throw ValidationException::withMessages([
                'application' => 'A candidatura já não permite alterar habitações pretendidas.',
            ]);
        }
    }

    private function assertNoLockedPreferences(Application $application): void
    {
        if (HousingPreference::withTrashed()
            ->where('application_id', $application->id)
            ->whereNotNull('locked_at')
            ->exists()) {
            throw ValidationException::withMessages([
                'preferences' => 'As preferências submetidas estão bloqueadas.',
            ]);
        }
    }

    private function assertNoAllocations(Application $application): void
    {
        if ($application->allocations()->exists()) {
            throw ValidationException::withMessages([
                'preferences' => 'As preferências ficam bloqueadas após existir execução de atribuição.',
            ]);
        }
    }

    private function assertNoFinalPreferenceSnapshot(Application $application): void
    {
        if ($application->snapshots()
            ->where(
                'snapshot_type',
                ApplicationSnapshotType::HousingPreferences->value,
            )
            ->exists()) {
            throw ValidationException::withMessages([
                'preferences' => 'As preferências finais da candidatura já se encontram fixadas.',
            ]);
        }
    }

    /**
     * @param  list<array<string, mixed>>  $preferences
     */
    private function assertSelectionStructure(
        array $preferences,
        ?AllocationRuleSet $ruleSet,
        bool $enforceMinimum,
    ): void {
        if (! $ruleSet instanceof AllocationRuleSet || ! $ruleSet->allow_preferences) {
            if ($preferences !== []) {
                throw ValidationException::withMessages([
                    'preferences' => 'Este concurso não permite preferências habitacionais.',
                ]);
            }

            return;
        }

        $minimum = $enforceMinimum && $ruleSet->preferences_required_before_submission
            ? max(1, (int) $ruleSet->minimum_preferences)
            : 0;
        $maximum = max($minimum, (int) $ruleSet->maximum_preferences);
        $count = count($preferences);

        if ($count < $minimum || $count > $maximum) {
            throw ValidationException::withMessages([
                'preferences' => "Selecione entre {$minimum} e {$maximum} habitações.",
            ]);
        }

        $orders = collect($preferences)
            ->pluck('preference_order')
            ->map(fn ($order): int => (int) $order)
            ->sort()
            ->values();
        $unitIds = collect($preferences)
            ->pluck('contest_housing_unit_id')
            ->map(fn ($id): int => (int) $id);

        if ($orders->duplicates()->isNotEmpty() || $unitIds->duplicates()->isNotEmpty()) {
            throw ValidationException::withMessages([
                'preferences' => 'Cada habitação e posição só pode ser selecionada uma vez.',
            ]);
        }

        $expectedOrders = $count === 0 ? [] : range(1, $count);

        if ($orders->all() !== $expectedOrders) {
            throw ValidationException::withMessages([
                'preferences' => 'A ordem das preferências deve ser consecutiva, começando em 1.',
            ]);
        }
    }
}
