<?php

namespace App\Services\Applications;

use App\Enums\ApplicationStatus;
use App\Models\Application;
use App\Models\Contest;
use App\Models\User;
use App\Services\Audit\AuditLogger;
use App\Support\AuditEvents;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ApplicationService
{
    public function __construct(
        private readonly ApplicationValidationService $validationService,
        private readonly AuditLogger $auditLogger,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function createDraft(User $actor, Contest $contest, array $data = []): Application
    {
        $this->validationService->validateStart($actor, $contest);

        $registration = $actor->adhesionRegistration()
            ->with(['household', 'currentHousingSituation'])
            ->firstOrFail();

        return DB::transaction(function () use ($actor, $contest, $registration, $data) {
            if ($registration->household === null || $registration->currentHousingSituation === null) {
                throw ValidationException::withMessages([
                    'registration' => 'O registo de adesão deve ter agregado e situação habitacional antes de iniciar candidatura.',
                ]);
            }

            $application = new Application([
                'candidate_notes' => $data['candidate_notes'] ?? null,
            ]);
            $application->forceFill([
                'public_id' => (string) Str::uuid(),
                'user_id' => $actor->id,
                'adhesion_registration_id' => $registration->id,
                'program_id' => $contest->program_id,
                'contest_id' => $contest->id,
                'household_id' => $registration->household->id,
                'current_housing_situation_id' => $registration->currentHousingSituation->id,
                'status' => ApplicationStatus::Draft,
                'created_by' => $actor->id,
                'updated_by' => $actor->id,
            ]);
            $application->save();

            $this->recordStatus($application, null, ApplicationStatus::Draft, $actor);
            $this->auditLogger->record(
                event: AuditEvents::CREATE,
                auditable: $application,
                module: 'applications',
                action: 'create',
                description: 'Candidatura criada em rascunho.',
                newValues: ['status' => ApplicationStatus::Draft->value],
                metadata: ['contest_id' => $contest->id, 'actor_id' => $actor->id],
            );

            $application->load(['contest.program', 'household', 'currentHousingSituation']);

            return $application;
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function updateDraft(Application $application, array $data, User $actor): Application
    {
        if (! $application->isEditable()) {
            throw ValidationException::withMessages([
                'application' => 'A candidatura submetida não pode ser editada diretamente.',
            ]);
        }

        $application->fill(['candidate_notes' => $data['candidate_notes'] ?? null]);
        $application->forceFill(['updated_by' => $actor->id])->save();

        $this->auditLogger->record(
            event: AuditEvents::UPDATE,
            auditable: $application,
            module: 'applications',
            action: 'update',
            description: 'Rascunho de candidatura atualizado.',
            metadata: ['changed_fields' => ['candidate_notes']],
        );

        return $application->refresh();
    }

    public function withdraw(Application $application, User $actor, ?string $reason = null): Application
    {
        if (! $application->status->canBeWithdrawn()) {
            throw ValidationException::withMessages([
                'application' => 'A candidatura não pode ser desistida no estado atual.',
            ]);
        }

        return DB::transaction(function () use ($application, $actor, $reason) {
            $from = $application->status;
            $application->forceFill([
                'status' => ApplicationStatus::Withdrawn,
                'withdrawn_at' => now(),
                'locked_at' => $application->locked_at ?? now(),
                'updated_by' => $actor->id,
            ])->save();

            $this->recordStatus($application, $from, ApplicationStatus::Withdrawn, $actor, $reason);
            $this->auditLogger->record(
                event: AuditEvents::UPDATE,
                auditable: $application,
                module: 'applications',
                action: 'withdraw',
                description: 'Candidatura desistida pelo candidato.',
                oldValues: ['status' => $from->value],
                newValues: ['status' => ApplicationStatus::Withdrawn->value],
                metadata: ['reason_provided' => filled($reason)],
            );

            return $application->refresh();
        });
    }

    public function recordStatus(
        Application $application,
        ?ApplicationStatus $from,
        ApplicationStatus $to,
        User $actor,
        ?string $reason = null,
    ): void {
        $application->statusHistories()->create([
            'from_status' => $from?->value,
            'to_status' => $to->value,
            'changed_by' => $actor->id,
            'reason' => $reason,
        ]);
    }
}
