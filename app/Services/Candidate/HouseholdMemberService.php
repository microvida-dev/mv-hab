<?php

namespace App\Services\Candidate;

use App\Models\AdhesionRegistration;
use App\Models\Household;
use App\Models\HouseholdMember;
use App\Models\User;
use App\Services\Audit\AuditLogger;
use App\Support\AuditEvents;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class HouseholdMemberService
{
    public function __construct(
        private readonly HouseholdService $householdService,
        private readonly AuditLogger $auditLogger,
    ) {}

    /**
     * @param  array<string, bool|float|int|string|null>  $data
     */
    public function create(Household $household, array $data, User $actor): HouseholdMember
    {
        $this->householdService->ensureEditable($this->registrationForHousehold($household));

        return DB::transaction(function () use ($household, $data, $actor) {
            $member = new HouseholdMember($this->normalizedData($data));
            $member->forceFill([
                'household_id' => $household->id,
                'adhesion_registration_id' => $household->adhesion_registration_id,
            ]);
            $member->save();
            $this->householdService->refreshMetrics($household);

            $this->auditLogger->record(
                event: AuditEvents::CREATE,
                auditable: $member,
                module: 'households',
                action: 'create_member',
                description: 'Membro adicionado ao agregado.',
                metadata: [
                    'actor_id' => $actor->id,
                    'household_id' => $household->id,
                ],
            );

            return $member->fresh() ?? $member;
        });
    }

    /**
     * @param  array<string, bool|float|int|string|null>  $data
     */
    public function update(HouseholdMember $member, array $data, User $actor): HouseholdMember
    {
        $this->householdService->ensureEditable($this->registrationForMember($member));
        $household = $this->householdForMember($member);

        return DB::transaction(function () use ($member, $data, $actor, $household) {
            if ($member->is_applicant) {
                $data['is_applicant'] = true;
            }

            $member->fill($this->normalizedData($data));
            $changedFields = array_keys($member->getDirty());
            $member->save();

            if ($member->has_no_income) {
                $member->incomeRecords()->delete();
            }

            $this->refreshIncomeSummary($member);
            $this->householdService->refreshMetrics($household);

            $this->auditLogger->record(
                event: AuditEvents::UPDATE,
                auditable: $member,
                module: 'households',
                action: 'update_member',
                description: 'Membro do agregado atualizado.',
                metadata: [
                    'actor_id' => $actor->id,
                    'household_id' => $member->household_id,
                    'changed_fields' => $changedFields,
                ],
            );

            return $member->fresh() ?? $member;
        });
    }

    public function delete(HouseholdMember $member, User $actor): void
    {
        $this->householdService->ensureEditable($this->registrationForMember($member));
        $household = $this->householdForMember($member);

        if ($member->is_applicant
            && $household->members()->where('is_applicant', true)->count() <= 1) {
            throw ValidationException::withMessages([
                'member' => 'O agregado deve manter pelo menos um membro requerente.',
            ]);
        }

        DB::transaction(function () use ($member, $actor, $household) {
            $member->incomeRecords()->delete();
            $member->delete();
            $this->householdService->refreshMetrics($household);

            $this->auditLogger->record(
                event: AuditEvents::DELETE,
                auditable: $member,
                module: 'households',
                action: 'delete_member',
                description: 'Membro removido do agregado.',
                metadata: [
                    'actor_id' => $actor->id,
                    'household_id' => $household->id,
                ],
            );
        });
    }

    public function refreshIncomeSummary(HouseholdMember $member): void
    {
        $member->forceFill([
            'monthly_declared_income' => $member->incomeRecords()->sum('monthly_amount'),
            'annual_declared_income' => $member->incomeRecords()->sum('annual_amount'),
        ])->saveQuietly();
    }

    /**
     * @param  array<string, bool|float|int|string|null>  $data
     * @return array<string, bool|float|int|string|null>
     */
    private function normalizedData(array $data): array
    {
        $birthDateInput = $data['birth_date'] ?? null;
        $birthDate = $birthDateInput !== null && ! is_bool($birthDateInput)
            ? Carbon::parse($birthDateInput)
            : null;
        $data['is_elderly'] = $birthDate?->lte(today()->subYears(65)) ?? false;

        if (! ($data['is_disabled'] ?? false)) {
            $data['disability_percentage'] = null;
            $data['has_multiple_disabilities'] = false;
        }

        if (! ($data['has_no_income'] ?? false)) {
            $data['no_income_reason'] = null;
        }

        unset(
            $data['monthly_declared_income'],
            $data['annual_declared_income'],
            $data['household_id'],
            $data['adhesion_registration_id'],
        );

        return $data;
    }

    private function registrationForHousehold(Household $household): AdhesionRegistration
    {
        $registration = $household->adhesionRegistration;

        if (! $registration instanceof AdhesionRegistration) {
            throw ValidationException::withMessages([
                'registration' => 'Não foi possível identificar o Registo de Adesão associado.',
            ]);
        }

        return $registration;
    }

    private function registrationForMember(HouseholdMember $member): AdhesionRegistration
    {
        $registration = $member->adhesionRegistration;

        if (! $registration instanceof AdhesionRegistration) {
            throw ValidationException::withMessages([
                'registration' => 'Não foi possível identificar o Registo de Adesão associado.',
            ]);
        }

        return $registration;
    }

    private function householdForMember(HouseholdMember $member): Household
    {
        $household = $member->household;

        if (! $household instanceof Household) {
            throw ValidationException::withMessages([
                'household' => 'Não foi possível identificar o agregado associado.',
            ]);
        }

        return $household;
    }
}
