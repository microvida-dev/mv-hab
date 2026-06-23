<?php

namespace App\Services\Candidate;

use App\Enums\AdhesionRegistrationStatus;
use App\Models\AdhesionRegistration;
use App\Models\User;
use App\Services\Audit\AuditLogger;
use App\Support\AuditEvents;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AdhesionRegistrationService
{
    public function __construct(private readonly AuditLogger $auditLogger) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data, User $actor): AdhesionRegistration
    {
        return DB::transaction(function () use ($data, $actor) {
            if ($actor->adhesionRegistration()->exists()) {
                throw ValidationException::withMessages([
                    'registration' => 'Já existe um Registo de Adesão associado à sua conta.',
                ]);
            }

            $registration = new AdhesionRegistration($data);
            $registration->forceFill([
                'user_id' => $actor->id,
                'status' => AdhesionRegistrationStatus::Incomplete,
            ]);
            $this->applyConsentTimestamps($registration, $data);
            $registration->save();

            $this->recordStatusHistory($registration, null, AdhesionRegistrationStatus::Incomplete, $actor);
            $this->auditStatusEvent($registration, $actor, AuditEvents::CREATE, 'create', null);

            $registration->load('statusHistories.changedBy');

            return $registration;
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(AdhesionRegistration $registration, array $data, User $actor): AdhesionRegistration
    {
        return DB::transaction(function () use ($registration, $data, $actor) {
            $beforeStatus = $registration->status;
            $beforeCompletion = $registration->completionPercentage();

            $registration->fill($data);
            $this->applyConsentTimestamps($registration, $data);
            $changedFields = array_keys($registration->getDirty());

            if ($beforeStatus === AdhesionRegistrationStatus::Registered
                && ($registration->missingRequiredFields() !== [] || ! $registration->isAdult())) {
                $registration->forceFill([
                    'status' => AdhesionRegistrationStatus::Incomplete,
                    'submitted_at' => null,
                ]);
            }

            $registration->save();

            if ($registration->status !== $beforeStatus) {
                $this->recordStatusHistory(
                    $registration,
                    $beforeStatus,
                    $registration->status,
                    $actor,
                    'O registo voltou a incompleto após alteração de dados obrigatórios.',
                );
            }

            $this->auditLogger->record(
                event: AuditEvents::UPDATE,
                auditable: $registration,
                module: 'adhesion_registrations',
                action: 'update',
                description: 'Registo de Adesão atualizado pelo titular.',
                oldValues: [
                    'status' => $beforeStatus->value,
                    'completion_percentage' => $beforeCompletion,
                ],
                newValues: [
                    'status' => $registration->status->value,
                    'completion_percentage' => $registration->completionPercentage(),
                ],
                metadata: [
                    'changed_fields' => array_values(array_diff($changedFields, [
                        'accepted_terms_at',
                        'accepted_data_processing_at',
                    ])),
                ],
            );

            $registration->load('statusHistories.changedBy');

            return $registration;
        });
    }

    public function finalize(AdhesionRegistration $registration, User $actor): AdhesionRegistration
    {
        if (! $registration->canBeFinalized()) {
            throw ValidationException::withMessages([
                'registration' => 'O registo não reúne os requisitos necessários para finalização.',
            ]);
        }

        return DB::transaction(function () use ($registration, $actor) {
            $from = $registration->status;
            $registration->markAsRegistered()->save();

            $this->recordStatusHistory($registration, $from, $registration->status, $actor);
            $this->auditStatusEvent($registration, $actor, AuditEvents::UPDATE, 'finalize', $from);

            $registration->load('statusHistories.changedBy');

            return $registration;
        });
    }

    public function cancel(AdhesionRegistration $registration, User $actor, ?string $reason = null): AdhesionRegistration
    {
        if (! $registration->canBeCancelled()) {
            throw ValidationException::withMessages([
                'registration' => 'Este registo não pode ser cancelado no estado atual.',
            ]);
        }

        return DB::transaction(function () use ($registration, $actor, $reason) {
            $from = $registration->status;
            $registration->markAsCancelled()->save();

            $this->recordStatusHistory($registration, $from, $registration->status, $actor, $reason);
            $this->auditStatusEvent($registration, $actor, AuditEvents::UPDATE, 'cancel', $from);

            $registration->load('statusHistories.changedBy');

            return $registration;
        });
    }

    public function remove(AdhesionRegistration $registration, User $actor, ?string $reason = null): void
    {
        if (! $registration->canBeRemoved()) {
            throw ValidationException::withMessages([
                'registration' => 'Este registo não pode ser removido porque existem impedimentos associados.',
            ]);
        }

        DB::transaction(function () use ($registration, $actor, $reason) {
            $from = $registration->status;
            $registration->markAsRemoved()->save();

            $this->recordStatusHistory($registration, $from, $registration->status, $actor, $reason);
            $this->auditStatusEvent($registration, $actor, AuditEvents::DELETE, 'remove', $from);
            $registration->delete();
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function applyConsentTimestamps(AdhesionRegistration $registration, array $data): void
    {
        if (($data['accepts_terms'] ?? false) && $registration->accepted_terms_at === null) {
            $registration->forceFill(['accepted_terms_at' => now()]);
        }

        if (($data['accepts_data_processing'] ?? false)
            && $registration->accepted_data_processing_at === null) {
            $registration->forceFill(['accepted_data_processing_at' => now()]);
        }
    }

    private function recordStatusHistory(
        AdhesionRegistration $registration,
        ?AdhesionRegistrationStatus $from,
        AdhesionRegistrationStatus $to,
        User $actor,
        ?string $reason = null,
    ): void {
        $registration->statusHistories()->create([
            'from_status' => $from?->value,
            'to_status' => $to->value,
            'changed_by' => $actor->id,
            'reason' => $reason,
        ]);
    }

    private function auditStatusEvent(
        AdhesionRegistration $registration,
        User $actor,
        string $event,
        string $action,
        ?AdhesionRegistrationStatus $from,
    ): void {
        $this->auditLogger->record(
            event: $event,
            auditable: $registration,
            module: 'adhesion_registrations',
            action: $action,
            description: 'Alteração do estado do Registo de Adesão.',
            oldValues: $from ? ['status' => $from->value] : [],
            newValues: [
                'status' => $registration->status->value,
                'completion_percentage' => $registration->completionPercentage(),
            ],
            metadata: [
                'actor_id' => $actor->id,
            ],
        );
    }
}
