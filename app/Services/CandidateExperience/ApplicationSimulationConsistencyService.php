<?php

namespace App\Services\CandidateExperience;

use App\Enums\InconsistencySeverity;
use App\Enums\InconsistencyType;
use App\Enums\InteractionType;
use App\Models\Application;
use App\Models\ApplicationSimulationInconsistency;
use App\Models\Household;
use App\Models\SimulationInputSnapshot;
use App\Models\SimulationRecommendedContest;
use App\Models\SimulationResult;
use App\Models\SimulationSession;
use App\Models\User;
use App\Services\Audit\AuditLogger;
use App\Support\AuditEvents;
use BackedEnum;
use Illuminate\Support\Collection;

class ApplicationSimulationConsistencyService
{
    public function __construct(
        private readonly CandidateInteractionService $interactions,
        private readonly AuditLogger $auditLogger,
    ) {}

    /**
     * @return Collection<int, ApplicationSimulationInconsistency>
     */
    public function analyse(Application $application, ?SimulationSession $simulation = null): Collection
    {
        $application->loadMissing([
            'user',
            'household.members',
            'household.incomeRecords',
            'currentHousingSituation',
            'housingPreferences.contestHousingUnit',
            'latestEligibilityCheck',
        ]);

        $user = $application->getRelationValue('user');
        if (! $user instanceof User) {
            return collect();
        }

        $simulation ??= $this->latestRelevantSimulation($application, $user);
        if (! $simulation instanceof SimulationSession) {
            return collect();
        }

        $simulation->loadMissing(['inputSnapshot', 'result', 'recommendedContests']);

        $created = collect();
        $input = $simulation->inputSnapshot;
        if ($input instanceof SimulationInputSnapshot) {
            $created = $created->merge($this->compareInputSnapshot($application, $simulation, $input, $user));
        }

        $result = $simulation->result;
        if ($result instanceof SimulationResult) {
            $created = $created->merge($this->compareResult($application, $simulation, $result, $user));
        }

        if ($simulation->completed_at !== null && $application->updated_at !== null && $simulation->completed_at->lt($application->updated_at)) {
            $created->push($this->upsert(
                $application,
                $simulation,
                $user,
                InconsistencyType::SimulationOutdated,
                InconsistencySeverity::Warning,
                'simulation_date',
                $simulation->completed_at->toDateTimeString(),
                $application->updated_at->toDateTimeString(),
                'A candidatura foi alterada depois da última simulação relevante.',
                'Reexecutar a simulação ou confirmar que os dados atuais estão corretos.',
            ));
        }

        $recommendedContestIds = $simulation->recommendedContests
            ->map(static fn (SimulationRecommendedContest $recommended): int => (int) $recommended->contest_id)
            ->all();

        if ($recommendedContestIds !== [] && ! in_array((int) $application->contest_id, $recommendedContestIds, true)) {
            $created->push($this->upsert(
                $application,
                $simulation,
                $user,
                InconsistencyType::ContestNoLongerMatching,
                InconsistencySeverity::RequiresReview,
                'contest_id',
                $recommendedContestIds,
                (int) $application->contest_id,
                'O concurso escolhido não consta das recomendações da simulação.',
                'Confirmar se a candidatura continua adequada ao concurso selecionado.',
            ));
        }

        foreach ($created as $inconsistency) {
            if ($inconsistency instanceof ApplicationSimulationInconsistency) {
                $this->interactions->record(
                    user: $user,
                    type: InteractionType::InconsistencyDetected,
                    title: 'Inconsistência entre simulação e candidatura',
                    description: $inconsistency->message,
                    related: $inconsistency,
                    application: $application,
                    contest: $application->contest,
                    actor: $user,
                );
            }
        }

        return $created->filter()->values();
    }

    public function resolve(ApplicationSimulationInconsistency $inconsistency, User $actor, ?string $note = null): ApplicationSimulationInconsistency
    {
        $inconsistency->forceFill([
            'is_resolved' => true,
            'resolved_by' => $actor->id,
            'resolved_at' => now(),
            'recommendation' => $note ?: $inconsistency->recommendation,
        ])->save();

        $this->auditLogger->record(
            AuditEvents::UPDATE,
            $inconsistency,
            'candidate_experience',
            'application_inconsistency_resolved',
            'Inconsistência entre simulação e candidatura resolvida.',
            metadata: ['actor_id' => $actor->id],
        );

        return $inconsistency->refresh();
    }

    private function latestRelevantSimulation(Application $application, User $user): ?SimulationSession
    {
        return SimulationSession::query()
            ->where('user_id', $user->id)
            ->where(function ($query) use ($application): void {
                $query->where('application_id', $application->id)
                    ->orWhereNull('application_id');
            })
            ->latest('completed_at')
            ->first();
    }

    /**
     * @return Collection<int, ApplicationSimulationInconsistency>
     */
    private function compareInputSnapshot(Application $application, SimulationSession $simulation, SimulationInputSnapshot $input, User $user): Collection
    {
        $household = $application->getRelationValue('household');
        $members = $household instanceof Household ? $household->members : collect();
        $incomeRecords = $household instanceof Household ? $household->incomeRecords : collect();
        $monthlyIncome = (float) $incomeRecords->sum('monthly_amount');
        if ($monthlyIncome <= 0 && $household instanceof Household) {
            $monthlyIncome = (float) $household->monthly_income;
        }

        $dependents = $members->where('is_dependent', true)->count();
        $adults = max($members->count() - $dependents, 0);
        $currentHousing = $application->currentHousingSituation;

        $items = collect();
        $items = $items->merge($this->compareScalar($application, $simulation, $user, InconsistencyType::HouseholdSizeChanged, 'household_members_count', $input->household_members_count, $members->count(), 0));
        $items = $items->merge($this->compareScalar($application, $simulation, $user, InconsistencyType::HouseholdSizeChanged, 'adults_count', $input->adults_count, $adults, 0));
        $items = $items->merge($this->compareScalar($application, $simulation, $user, InconsistencyType::HouseholdSizeChanged, 'dependents_count', $input->dependents_count, $dependents, 0));
        $items = $items->merge($this->compareScalar($application, $simulation, $user, InconsistencyType::IncomeChanged, 'monthly_income', (float) $input->monthly_income, $monthlyIncome, 50.0));
        $items = $items->merge($this->compareScalar($application, $simulation, $user, InconsistencyType::HousingSituationChanged, 'housing_status', $input->housing_status, $this->enumValue($currentHousing?->getAttribute('housing_status')), 0));

        $firstPreference = $application->housingPreferences->first();
        $preferredTypology = $firstPreference?->contestHousingUnit?->typology;
        $simulationTypologies = is_array($input->preferred_typologies) ? $input->preferred_typologies : [];
        if ($preferredTypology !== null && $simulationTypologies !== [] && ! in_array($preferredTypology, $simulationTypologies, true)) {
            $items->push($this->upsert(
                $application,
                $simulation,
                $user,
                InconsistencyType::PreferredTypologyChanged,
                InconsistencySeverity::Warning,
                'preferred_typology',
                $simulationTypologies,
                $preferredTypology,
                'A tipologia preferida na candidatura diverge da simulação.',
                'Confirmar a preferência habitacional antes de prosseguir.',
            ));
        }

        return $items->filter()->values();
    }

    /**
     * @return Collection<int, ApplicationSimulationInconsistency>
     */
    private function compareResult(Application $application, SimulationSession $simulation, SimulationResult $result, User $user): Collection
    {
        $items = collect();
        $latestCheck = $application->latestEligibilityCheck;
        $eligibilityResult = $this->enumValue($latestCheck?->getAttribute('result'));
        $simulationStatus = $this->enumValue($result->getAttribute('result_status'));

        if ($eligibilityResult !== null && $simulationStatus !== null && $eligibilityResult !== $simulationStatus) {
            $items->push($this->upsert(
                $application,
                $simulation,
                $user,
                InconsistencyType::EligibilityResultChanged,
                InconsistencySeverity::RequiresReview,
                'eligibility_result',
                $simulationStatus,
                $eligibilityResult,
                'O resultado formal ou mais recente diverge da simulação indicativa.',
                'Validar a candidatura com o técnico municipal.',
            ));
        }

        $rentEstimate = $result->estimated_rent_max;
        $firstPreference = $application->housingPreferences->first();
        $selectedRent = $firstPreference?->contestHousingUnit?->monthly_rent;
        if ($rentEstimate !== null && $selectedRent !== null && abs((float) $rentEstimate - (float) $selectedRent) > 25) {
            $items->push($this->upsert(
                $application,
                $simulation,
                $user,
                InconsistencyType::RentEstimateChanged,
                InconsistencySeverity::Info,
                'rent_estimate',
                (float) $rentEstimate,
                (float) $selectedRent,
                'A renda estimada na simulação difere da renda da habitação preferida.',
                'Confirmar a taxa de esforço antes da submissão final.',
            ));
        }

        return $items->filter()->values();
    }

    /**
     * @return Collection<int, ApplicationSimulationInconsistency>
     */
    private function compareScalar(
        Application $application,
        SimulationSession $simulation,
        User $user,
        InconsistencyType $type,
        string $field,
        mixed $simulationValue,
        mixed $applicationValue,
        float $tolerance,
    ): Collection {
        if ($simulationValue === null || $applicationValue === null) {
            return collect();
        }

        $different = is_numeric($simulationValue) && is_numeric($applicationValue)
            ? abs((float) $simulationValue - (float) $applicationValue) > $tolerance
            : (string) $simulationValue !== (string) $applicationValue;

        if (! $different) {
            return collect();
        }

        return collect([$this->upsert(
            $application,
            $simulation,
            $user,
            $type,
            $type === InconsistencyType::IncomeChanged ? InconsistencySeverity::Warning : InconsistencySeverity::RequiresReview,
            $field,
            $simulationValue,
            $applicationValue,
            'Os dados atuais da candidatura diferem da última simulação relevante.',
            'Confirmar ou corrigir os dados antes de avançar.',
        )]);
    }

    private function upsert(
        Application $application,
        SimulationSession $simulation,
        User $user,
        InconsistencyType $type,
        InconsistencySeverity $severity,
        string $field,
        mixed $simulationValue,
        mixed $applicationValue,
        string $message,
        string $recommendation,
    ): ApplicationSimulationInconsistency {
        $inconsistency = ApplicationSimulationInconsistency::query()
            ->where('application_id', $application->id)
            ->where('type', $type->value)
            ->where('field_name', $field)
            ->where('is_resolved', false)
            ->first();

        if (! $inconsistency instanceof ApplicationSimulationInconsistency) {
            $inconsistency = new ApplicationSimulationInconsistency([
                'application_id' => $application->id,
                'user_id' => $user->id,
                'type' => $type,
                'field_name' => $field,
            ]);
        }

        $inconsistency->forceFill([
            'simulation_session_id' => $simulation->id,
            'severity' => $severity,
            'simulation_value' => $this->safeValue($simulationValue),
            'application_value' => $this->safeValue($applicationValue),
            'message' => $message,
            'recommendation' => $recommendation,
        ])->save();

        $this->auditLogger->record(
            AuditEvents::CREATE,
            $inconsistency,
            'candidate_experience',
            'application_inconsistency_detected',
            'Inconsistência entre simulação e candidatura detetada.',
            metadata: ['type' => $type->value, 'severity' => $severity->value, 'field' => $field],
        );

        return $inconsistency->refresh();
    }

    /**
     * @return array{value: mixed}
     */
    private function safeValue(mixed $value): array
    {
        if ($value instanceof BackedEnum) {
            return ['value' => $value->value];
        }

        if (is_array($value)) {
            return ['value' => array_values($value)];
        }

        return ['value' => $value];
    }

    private function enumValue(mixed $value): ?string
    {
        if ($value instanceof BackedEnum) {
            return is_string($value->value) ? $value->value : (string) $value->value;
        }

        return is_scalar($value) ? (string) $value : null;
    }
}
