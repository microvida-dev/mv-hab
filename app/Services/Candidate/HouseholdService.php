<?php

namespace App\Services\Candidate;

use App\Enums\AdhesionRegistrationStatus;
use App\Enums\HouseholdRelationship;
use App\Models\AdhesionRegistration;
use App\Models\Household;
use App\Models\HouseholdMember;
use App\Models\User;
use App\Services\Audit\AuditLogger;
use App\Support\AuditEvents;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class HouseholdService
{
    public function __construct(private readonly AuditLogger $auditLogger) {}

    /**
     * @param  array<string, bool|float|int|string|null>  $data
     */
    public function create(AdhesionRegistration $registration, array $data, User $actor): Household
    {
        $this->ensureEditable($registration);

        return DB::transaction(function () use ($registration, $data, $actor) {
            if ($registration->household()->exists()) {
                throw ValidationException::withMessages([
                    'household' => 'Já existe um agregado associado ao seu Registo de Adesão.',
                ]);
            }

            $household = new Household([
                'name' => $data['name'] ?? 'Agregado de '.($registration->full_name ?: $actor->name),
                'household_type' => $data['household_type'] ?? 'family',
                'monthly_income' => 0,
                'members_count' => 0,
                'notes' => $data['notes'] ?? null,
            ]);
            $household->forceFill([
                'adhesion_registration_id' => $registration->id,
                'citizen_id' => null,
            ]);
            $household->save();

            $this->syncApplicant($household, $registration);
            $this->refreshMetrics($household);

            $this->auditLogger->record(
                event: AuditEvents::CREATE,
                auditable: $household,
                module: 'households',
                action: 'create',
                description: 'Agregado do candidato criado.',
                newValues: ['members_count' => $household->members_count],
                metadata: ['actor_id' => $actor->id],
            );

            return $household->fresh(['members', 'incomeRecords']) ?? $household;
        });
    }

    /**
     * @param  array<string, bool|float|int|string|null>  $data
     */
    public function update(Household $household, array $data, User $actor): Household
    {
        $registration = $this->registrationFor($household);
        $this->ensureEditable($registration);

        return DB::transaction(function () use ($household, $data, $actor, $registration) {
            $household->fill([
                'name' => $data['name'],
                'household_type' => $data['household_type'],
                'notes' => $data['notes'] ?? null,
            ]);
            $changedFields = array_keys($household->getDirty());
            $household->save();

            $this->syncApplicant($household, $registration);
            $this->refreshMetrics($household);

            $this->auditLogger->record(
                event: AuditEvents::UPDATE,
                auditable: $household,
                module: 'households',
                action: 'update',
                description: 'Dados gerais do agregado atualizados.',
                metadata: [
                    'actor_id' => $actor->id,
                    'changed_fields' => $changedFields,
                ],
            );

            return $household->fresh(['members', 'incomeRecords']) ?? $household;
        });
    }

    public function syncApplicant(Household $household, AdhesionRegistration $registration): void
    {
        $applicant = HouseholdMember::withTrashed()
            ->where('household_id', $household->id)
            ->where('is_applicant', true)
            ->first()
            ?? HouseholdMember::withTrashed()
                ->where('household_id', $household->id)
                ->where('relationship', HouseholdRelationship::Applicant->value)
                ->first();

        if ($applicant?->trashed()) {
            $applicant->restore();
        }

        $registrationUser = $registration->user;
        $birthDate = $registration->getAttribute('birth_date');
        $isElderly = $birthDate !== null && $birthDate !== ''
            ? Carbon::parse($birthDate)->lte(today()->subYears(65))
            : false;

        $applicant ??= $household->members()->make();
        $applicant->fill([
            'is_applicant' => true,
            'full_name' => $registration->full_name ?: (string) data_get($registrationUser, 'name', 'Candidato'),
            'birth_date' => $registration->birth_date,
            'relationship' => HouseholdRelationship::Applicant,
            'nationality' => $registration->nationality,
            'document_type' => $registration->document_type,
            'document_number' => $registration->document_number,
            'document_valid_until' => $registration->document_valid_until,
            'nif' => $registration->nif,
            'is_elderly' => $isElderly,
        ]);
        $applicant->forceFill([
            'household_id' => $household->id,
            'adhesion_registration_id' => $registration->id,
        ]);
        $applicant->save();
    }

    public function refreshMetrics(Household $household): void
    {
        $household->forceFill([
            'members_count' => $household->members()->count(),
            'monthly_income' => $household->incomeRecords()->sum('monthly_amount'),
        ])->saveQuietly();
    }

    public function ensureEditable(AdhesionRegistration $registration): void
    {
        if (! in_array($registration->status, [
            AdhesionRegistrationStatus::Incomplete,
            AdhesionRegistrationStatus::Registered,
        ], true)) {
            throw ValidationException::withMessages([
                'registration' => 'Os dados não podem ser alterados no estado atual do Registo de Adesão.',
            ]);
        }
    }

    private function registrationFor(Household $household): AdhesionRegistration
    {
        $registration = $household->adhesionRegistration;

        if (! $registration instanceof AdhesionRegistration) {
            throw ValidationException::withMessages([
                'registration' => 'Não foi possível identificar o Registo de Adesão associado.',
            ]);
        }

        return $registration;
    }
}
