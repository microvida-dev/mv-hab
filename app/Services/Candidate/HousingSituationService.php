<?php

namespace App\Services\Candidate;

use App\Enums\HousingCondition;
use App\Enums\HousingStatus;
use App\Models\AdhesionRegistration;
use App\Models\CurrentHousingSituation;
use App\Models\User;
use App\Services\Audit\AuditLogger;
use App\Support\AuditEvents;
use Illuminate\Support\Facades\DB;

class HousingSituationService
{
    public function __construct(
        private readonly HouseholdService $householdService,
        private readonly AuditLogger $auditLogger,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function updateOrCreate(
        AdhesionRegistration $registration,
        array $data,
        User $actor,
    ): CurrentHousingSituation {
        $this->householdService->ensureEditable($registration);

        return DB::transaction(function () use ($registration, $data, $actor) {
            $situation = $registration->currentHousingSituation()->withTrashed()->first();
            $created = $situation === null;

            if ($situation?->trashed()) {
                $situation->restore();
            }

            $situation ??= new CurrentHousingSituation;
            $situation->fill($this->normalize($data));
            $changedFields = array_keys($situation->getDirty());
            $situation->forceFill(['adhesion_registration_id' => $registration->id]);
            $situation->save();

            $this->auditLogger->record(
                event: $created ? AuditEvents::CREATE : AuditEvents::UPDATE,
                auditable: $situation,
                module: 'current_housing',
                action: $created ? 'create' : 'update',
                description: $created
                    ? 'Situação habitacional atual declarada.'
                    : 'Situação habitacional atual atualizada.',
                metadata: [
                    'actor_id' => $actor->id,
                    'changed_fields' => $changedFields,
                    'risk_indicators_count' => $this->riskIndicatorsCount($situation),
                ],
            );

            $situation->refresh();

            return $situation;
        });
    }

    public function riskIndicatorsCount(CurrentHousingSituation $situation): int
    {
        return collect([
            $situation->is_overcrowded,
            $situation->is_at_risk_of_eviction,
            $situation->is_homeless,
            $situation->is_temporary_accommodation,
            $situation->has_accessibility_needs,
            $situation->has_high_rent_burden,
        ])->filter()->count();
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function normalize(array $data): array
    {
        if (($data['housing_status'] ?? null) === HousingStatus::Homeless->value) {
            $data['is_homeless'] = true;
        }

        if (($data['housing_status'] ?? null) === HousingStatus::Temporary->value) {
            $data['is_temporary_accommodation'] = true;
        }

        if (($data['current_housing_condition'] ?? null) === HousingCondition::Overcrowded->value) {
            $data['is_overcrowded'] = true;
        }

        unset($data['adhesion_registration_id']);

        return $data;
    }
}
