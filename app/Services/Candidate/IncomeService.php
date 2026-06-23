<?php

namespace App\Services\Candidate;

use App\Models\AdhesionRegistration;
use App\Models\Household;
use App\Models\HouseholdMember;
use App\Models\IncomeRecord;
use App\Models\User;
use App\Services\Audit\AuditLogger;
use App\Support\AuditEvents;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class IncomeService
{
    public function __construct(
        private readonly HouseholdService $householdService,
        private readonly HouseholdMemberService $memberService,
        private readonly AuditLogger $auditLogger,
    ) {}

    /**
     * @param  array<string, bool|float|int|string|null>  $data
     */
    public function create(Household $household, HouseholdMember $member, array $data, User $actor): IncomeRecord
    {
        $this->ensureMemberBelongsToHousehold($member, $household);
        $this->householdService->ensureEditable($this->registrationForHousehold($household));

        if ($member->has_no_income) {
            throw ValidationException::withMessages([
                'household_member_id' => 'Este membro está assinalado como não tendo rendimentos.',
            ]);
        }

        return DB::transaction(function () use ($household, $member, $data, $actor) {
            $record = new IncomeRecord($this->normalizedAmounts($data));
            $record->forceFill([
                'household_member_id' => $member->id,
                'household_id' => $household->id,
                'adhesion_registration_id' => $household->adhesion_registration_id,
            ]);
            $record->save();
            $this->refreshTotals($member, $household);

            $this->auditLogger->record(
                event: AuditEvents::CREATE,
                auditable: $record,
                module: 'income_records',
                action: 'create',
                description: 'Rendimento declarado para um membro do agregado.',
                metadata: [
                    'actor_id' => $actor->id,
                    'household_id' => $household->id,
                    'member_id' => $member->id,
                ],
            );

            return $record->fresh(['incomeSource', 'householdMember']) ?? $record;
        });
    }

    /**
     * @param  array<string, bool|float|int|string|null>  $data
     */
    public function update(IncomeRecord $record, array $data, User $actor): IncomeRecord
    {
        $this->householdService->ensureEditable($this->registrationForRecord($record));
        $previousMember = $this->memberForRecord($record);
        $household = $this->householdForRecord($record);
        $member = $household
            ->members()
            ->find((int) ($data['household_member_id'] ?? 0));

        if (! $member instanceof HouseholdMember) {
            throw ValidationException::withMessages([
                'household_member_id' => 'O membro selecionado não pertence ao seu agregado.',
            ]);
        }

        if ($member->has_no_income) {
            throw ValidationException::withMessages([
                'household_member_id' => 'Este membro está assinalado como não tendo rendimentos.',
            ]);
        }

        return DB::transaction(function () use ($record, $data, $actor, $previousMember, $member, $household) {
            $record->fill($this->normalizedAmounts($data));
            $record->forceFill(['household_member_id' => $member->id]);
            $changedFields = array_keys($record->getDirty());
            $record->save();
            $this->refreshTotals($member, $household);

            if ($previousMember->isNot($member)) {
                $this->memberService->refreshIncomeSummary($previousMember);
            }

            $this->auditLogger->record(
                event: AuditEvents::UPDATE,
                auditable: $record,
                module: 'income_records',
                action: 'update',
                description: 'Rendimento declarado atualizado.',
                metadata: [
                    'actor_id' => $actor->id,
                    'household_id' => $record->household_id,
                    'changed_fields' => $changedFields,
                ],
            );

            return $record->fresh(['incomeSource', 'householdMember']) ?? $record;
        });
    }

    public function delete(IncomeRecord $record, User $actor): void
    {
        $this->householdService->ensureEditable($this->registrationForRecord($record));
        $member = $this->memberForRecord($record);
        $household = $this->householdForRecord($record);

        DB::transaction(function () use ($record, $actor, $member, $household) {
            $record->delete();
            $this->refreshTotals($member, $household);

            $this->auditLogger->record(
                event: AuditEvents::DELETE,
                auditable: $record,
                module: 'income_records',
                action: 'delete',
                description: 'Rendimento declarado removido.',
                metadata: [
                    'actor_id' => $actor->id,
                    'household_id' => $household->id,
                ],
            );
        });
    }

    /**
     * @return array{monthly: float, annual: float}
     */
    public function totals(Household $household): array
    {
        return [
            'monthly' => (float) $household->incomeRecords()->sum('monthly_amount'),
            'annual' => (float) $household->incomeRecords()->sum('annual_amount'),
        ];
    }

    /**
     * @param  array<string, bool|float|int|string|null>  $data
     * @return array<string, bool|float|int|string|null>
     */
    private function normalizedAmounts(array $data): array
    {
        $monthly = filled($data['monthly_amount'] ?? null) ? (float) $data['monthly_amount'] : null;
        $annual = filled($data['annual_amount'] ?? null) ? (float) $data['annual_amount'] : null;

        if ($monthly === null && $annual === null) {
            throw ValidationException::withMessages([
                'monthly_amount' => 'Indique um valor mensal ou anual.',
            ]);
        }

        if ($monthly === null) {
            $monthly = round(((float) $annual) / 12, 2);
        }

        if ($annual === null) {
            $annual = round($monthly * 12, 2);
        }

        $data['monthly_amount'] = $monthly;
        $data['annual_amount'] = $annual;
        unset(
            $data['household_id'],
            $data['adhesion_registration_id'],
        );

        return $data;
    }

    private function refreshTotals(HouseholdMember $member, Household $household): void
    {
        $this->memberService->refreshIncomeSummary($member);
        $this->householdService->refreshMetrics($household);
    }

    private function ensureMemberBelongsToHousehold(HouseholdMember $member, Household $household): void
    {
        if ($member->household_id !== $household->id
            || $member->adhesion_registration_id !== $household->adhesion_registration_id) {
            throw ValidationException::withMessages([
                'household_member_id' => 'O membro selecionado não pertence ao seu agregado.',
            ]);
        }
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

    private function registrationForRecord(IncomeRecord $record): AdhesionRegistration
    {
        $registration = $record->adhesionRegistration;

        if (! $registration instanceof AdhesionRegistration) {
            throw ValidationException::withMessages([
                'registration' => 'Não foi possível identificar o Registo de Adesão associado.',
            ]);
        }

        return $registration;
    }

    private function householdForRecord(IncomeRecord $record): Household
    {
        $household = $record->household;

        if (! $household instanceof Household) {
            throw ValidationException::withMessages([
                'household' => 'Não foi possível identificar o agregado associado.',
            ]);
        }

        return $household;
    }

    private function memberForRecord(IncomeRecord $record): HouseholdMember
    {
        $member = $record->householdMember;

        if (! $member instanceof HouseholdMember) {
            throw ValidationException::withMessages([
                'household_member_id' => 'Não foi possível identificar o membro associado.',
            ]);
        }

        return $member;
    }
}
