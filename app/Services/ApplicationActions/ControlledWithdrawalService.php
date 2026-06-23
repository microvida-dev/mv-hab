<?php

namespace App\Services\ApplicationActions;

use App\Enums\ControlledWithdrawalStatus;
use App\Enums\TimelineEventType;
use App\Enums\TimelineEventVisibility;
use App\Models\Application;
use App\Models\ControlledWithdrawal;
use App\Models\User;
use App\Services\Applications\ApplicationService;
use App\Services\Audit\AuditLogger;
use App\Services\ProcessTracking\ProcessTimelineService;
use App\Support\AuditEvents;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ControlledWithdrawalService
{
    public function __construct(
        private readonly ApplicationService $applications,
        private readonly ProcessTimelineService $timeline,
        private readonly AuditLogger $auditLogger,
    ) {}

    public function create(Application $application, User $candidate, string $reason, bool $acknowledged): ControlledWithdrawal
    {
        if ($application->user_id !== $candidate->id || ! $application->status->canBeWithdrawn()) {
            throw ValidationException::withMessages(['application' => 'A candidatura não pode ser desistida no estado atual.']);
        }

        if (! $acknowledged) {
            throw ValidationException::withMessages(['consequence_acknowledged' => 'Confirme que compreende as consequências da desistência.']);
        }

        return DB::transaction(function () use ($application, $candidate, $reason, $acknowledged): ControlledWithdrawal {
            $withdrawal = new ControlledWithdrawal([
                'reason' => $reason,
                'consequence_acknowledged' => $acknowledged,
            ]);
            $withdrawal->forceFill([
                'application_id' => $application->id,
                'user_id' => $candidate->id,
                'status' => ControlledWithdrawalStatus::PendingConfirmation,
                'requested_at' => now(),
            ])->save();

            $this->timeline->record(
                application: $application,
                type: TimelineEventType::WithdrawalRequested,
                visibility: TimelineEventVisibility::CandidateVisible,
                title: 'Pedido de desistência iniciado',
                description: 'A desistência aguarda confirmação final.',
                actor: $candidate,
                related: $withdrawal,
            );
            $this->auditLogger->record(AuditEvents::CREATE, $withdrawal, 'applications', 'controlled_withdrawal_create', 'Desistência controlada iniciada.');

            return $withdrawal->refresh();
        });
    }

    public function confirm(ControlledWithdrawal $withdrawal, User $candidate): ControlledWithdrawal
    {
        if ($withdrawal->user_id !== $candidate->id || $withdrawal->status !== ControlledWithdrawalStatus::PendingConfirmation) {
            throw ValidationException::withMessages(['withdrawal' => 'Desistência indisponível para confirmação.']);
        }

        return DB::transaction(function () use ($withdrawal, $candidate): ControlledWithdrawal {
            $application = $withdrawal->application()->lockForUpdate()->firstOrFail();
            $this->applications->withdraw($application, $candidate, $withdrawal->reason);

            $withdrawal->forceFill([
                'status' => ControlledWithdrawalStatus::Completed,
                'confirmed_at' => now(),
                'completed_at' => now(),
                'processed_by' => $candidate->id,
            ])->save();

            $this->timeline->record(
                application: $application,
                type: TimelineEventType::ApplicationWithdrawn,
                visibility: TimelineEventVisibility::CandidateVisible,
                title: 'Candidatura desistida',
                description: 'A desistência foi confirmada pelo candidato.',
                actor: $candidate,
                related: $withdrawal,
            );
            $this->auditLogger->record(AuditEvents::UPDATE, $withdrawal, 'applications', 'controlled_withdrawal_confirm', 'Desistência controlada confirmada.');

            return $withdrawal->refresh();
        });
    }

    public function cancel(ControlledWithdrawal $withdrawal, User $candidate): ControlledWithdrawal
    {
        if ($withdrawal->user_id !== $candidate->id) {
            throw ValidationException::withMessages(['withdrawal' => 'Não pode cancelar esta desistência.']);
        }

        $withdrawal->forceFill([
            'status' => ControlledWithdrawalStatus::Cancelled,
            'cancelled_at' => now(),
        ])->save();

        return $withdrawal->refresh();
    }
}
