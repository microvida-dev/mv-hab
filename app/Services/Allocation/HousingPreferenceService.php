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
use App\Models\HousingUnit;
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
     * @param  SupportCollection<int, CompatibleHousingOptionData>|null  $compatibleOptions
     * @return array{
     *     enabled: bool,
     *     required: bool,
     *     complete_ordering: bool,
     *     required_count: int,
     *     minimum: int,
     *     maximum: int,
     *     configured_minimum: int,
     *     configured_maximum: int,
     *     starts_at: Carbon|null,
     *     ends_at: Carbon|null
     * }
     */
    public function selectionConfiguration(
        Application $application,
        ?SupportCollection $compatibleOptions = null,
    ): array {
        $ruleSet = $this->allocationRules->forApplication($application);
        $enabled = $ruleSet instanceof AllocationRuleSet
            && $ruleSet->allow_preferences;
        $required = $enabled
            && $ruleSet->preferences_required_before_submission;
        $compatibleOptions ??= $enabled
            ? $this->optionsFor($application)
            : collect();
        $requiredCount = $enabled
            ? $compatibleOptions->count()
            : 0;

        return [
            'enabled' => $enabled,
            'required' => $required,
            'complete_ordering' => $enabled,
            'required_count' => $requiredCount,
            'minimum' => $required ? $requiredCount : 0,
            'maximum' => $requiredCount,
            'configured_minimum' => $enabled
                ? max(0, (int) $ruleSet->minimum_preferences)
                : 0,
            'configured_maximum' => $enabled
                ? max(0, (int) $ruleSet->maximum_preferences)
                : 0,
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
            $this->lockSelectionInventory($lockedApplication);
            $compatibleOptions = $this->optionsFor($lockedApplication);
            $this->assertSelectionStructure(
                $preferences,
                $ruleSet,
                $compatibleOptions,
                $submit || $preferences !== [],
            );
            $options = $compatibleOptions
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
                    'compatible_units_count' => $compatibleOptions->count(),
                    'complete_ordering' => true,
                    'orders' => collect($preferences)
                        ->pluck('preference_order')
                        ->map(fn ($order): int => (int) $order)
                        ->all(),
                ],
            );
        });
    }

    /**
     * @return array{
     *     passed: bool,
     *     message: string,
     *     route: string,
     *     routeParameters: array<string, string>,
     *     count: int,
     *     required_count: int,
     *     minimum: int,
     *     maximum: int,
     *     complete_ordering: bool
     * }
     */
    public function readinessForSubmission(Application $application): array
    {
        $ruleSet = $this->allocationRules->forApplication($application);
        $preferences = $application->housingPreferences()->get();

        if (! $ruleSet instanceof AllocationRuleSet || ! $ruleSet->allow_preferences) {
            return [
                'passed' => true,
                'message' => 'Este concurso não exige ordenação de fogos antes da submissão.',
                'route' => 'candidate.housing-preferences.edit',
                'routeParameters' => ['application' => (string) $application->getRouteKey()],
                'count' => $preferences->count(),
                'required_count' => 0,
                'minimum' => 0,
                'maximum' => 0,
                'complete_ordering' => false,
            ];
        }

        $compatibleOptions = $this->optionsFor($application);
        $expectedUnitIds = $this->compatibleUnitIds($compatibleOptions);
        $selectedUnitIds = $preferences
            ->pluck('contest_housing_unit_id')
            ->map(fn ($id): int => (int) $id)
            ->sort()
            ->values();
        $count = $preferences->count();
        $requiredCount = $expectedUnitIds->count();
        $required = $ruleSet->preferences_required_before_submission;
        $valid = $preferences->every(
            fn (HousingPreference $preference): bool => $preference->compatibility_status === HousingCompatibilityStatus::Compatible
                && $preference->invalidated_at === null
                && $preference->locked_at === null,
        );
        $complete = $requiredCount > 0
            && $selectedUnitIds->all() === $expectedUnitIds->all();
        $noSelectionAllowed = ! $required && $count === 0;
        $passed = $noSelectionAllowed || ($complete && $valid);

        return [
            'passed' => $passed,
            'message' => match (true) {
                $noSelectionAllowed => 'Este concurso permite submeter sem ordenar fogos.',
                $requiredCount === 0 => 'Não existem fogos compatíveis e ativos para ordenar. Reveja os dados ou contacte os serviços municipais.',
                $passed => 'Todos os fogos compatíveis estão ordenados e validados.',
                default => "Ordene todos os {$requiredCount} fogos compatíveis, sem repetições nem omissões.",
            },
            'route' => 'candidate.housing-preferences.edit',
            'routeParameters' => ['application' => (string) $application->getRouteKey()],
            'count' => $count,
            'required_count' => $requiredCount,
            'minimum' => $required ? $requiredCount : 0,
            'maximum' => $requiredCount,
            'complete_ordering' => true,
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

        $this->lockSelectionInventory($application);
        $compatibleOptions = $this->optionsFor($application);
        $optionsByUnit = $compatibleOptions
            ->keyBy(fn (CompatibleHousingOptionData $option): int => $option->unit->id);
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
        $this->assertSelectionStructure(
            $payload,
            $ruleSet,
            $compatibleOptions,
            true,
        );

        foreach ($preferences as $preference) {
            $option = $optionsByUnit->get(
                $preference->contest_housing_unit_id,
            );

            if (! $option instanceof CompatibleHousingOptionData) {
                throw ValidationException::withMessages([
                    'preferences' => 'Um fogo ordenado deixou de estar disponível ou compatível. Reveja a ordem completa dos fogos.',
                ]);
            }

            $result = $option->compatibility;
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
                    'compatible_units_count' => $compatibleOptions->count(),
                    'complete_ordering' => true,
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
                    'compatible_units_count' => $compatibleOptions->count(),
                    'complete_ordering' => true,
                    'actor_id' => $actor->id,
                ],
            );
        }
    }

    private function lockSelectionInventory(Application $application): void
    {
        $inventory = ContestHousingUnit::query()
            ->select(['id', 'housing_unit_id'])
            ->where('contest_id', $application->contest_id)
            ->orderBy('id')
            ->lockForUpdate()
            ->get();

        $housingUnitIds = $inventory
            ->pluck('housing_unit_id')
            ->map(fn ($id): int => (int) $id)
            ->unique()
            ->values();

        if ($housingUnitIds->isEmpty()) {
            return;
        }

        HousingUnit::query()
            ->select(['id'])
            ->whereKey($housingUnitIds->all())
            ->orderBy('id')
            ->lockForUpdate()
            ->get();
    }

    /**
     * @param  SupportCollection<int, CompatibleHousingOptionData>  $compatibleOptions
     * @return SupportCollection<int, int>
     */
    private function compatibleUnitIds(
        SupportCollection $compatibleOptions,
    ): SupportCollection {
        return $compatibleOptions
            ->map(
                fn (CompatibleHousingOptionData $option): int => $option
                    ->unit
                    ->id,
            )
            ->sort()
            ->values();
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
     * @param  SupportCollection<int, CompatibleHousingOptionData>  $compatibleOptions
     */
    private function assertSelectionStructure(
        array $preferences,
        ?AllocationRuleSet $ruleSet,
        SupportCollection $compatibleOptions,
        bool $completeOrderRequired,
    ): void {
        if (! $ruleSet instanceof AllocationRuleSet || ! $ruleSet->allow_preferences) {
            if ($preferences !== []) {
                throw ValidationException::withMessages([
                    'preferences' => 'Este concurso não permite ordenar fogos.',
                ]);
            }

            return;
        }

        $expectedUnitIds = $this->compatibleUnitIds($compatibleOptions);
        $requiredCount = $expectedUnitIds->count();
        $count = count($preferences);
        if ($completeOrderRequired && $requiredCount === 0) {
            throw ValidationException::withMessages([
                'preferences' => 'Não existem fogos compatíveis e ativos para ordenar.',
            ]);
        }

        if ($completeOrderRequired && $count !== $requiredCount) {
            throw ValidationException::withMessages([
                'preferences' => "Ordene todos os {$requiredCount} fogos compatíveis, sem omissões.",
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
                'preferences' => 'Cada fogo e cada posição só podem ser usados uma vez.',
            ]);
        }

        $expectedOrders = $count === 0 ? [] : range(1, $count);

        if ($orders->all() !== $expectedOrders) {
            throw ValidationException::withMessages([
                'preferences' => 'A ordem dos fogos deve ser consecutiva, começando na posição 1.',
            ]);
        }

        if ($count === 0) {
            return;
        }

        if ($unitIds->sort()->values()->all() !== $expectedUnitIds->all()) {
            throw ValidationException::withMessages([
                'preferences' => 'A ordem deve incluir exatamente todos os fogos compatíveis e ativos.',
            ]);
        }
    }
}
