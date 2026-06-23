<?php

namespace App\Services\ProcessConfirmations;

use App\Enums\ProcessConfirmationStatus;
use App\Models\Application;
use App\Models\ProcessConfirmation;
use App\Models\User;
use App\Services\Audit\AuditLogger;
use App\Support\AuditEvents;
use Illuminate\Support\Facades\DB;

class ProcessConfirmationService
{
    public function __construct(
        private readonly ProcessNumberGenerator $numbers,
        private readonly AuditLogger $auditLogger,
    ) {}

    public function generate(Application $application, ?User $actor = null, bool $forceRegenerate = false): ProcessConfirmation
    {
        $existing = $application->processConfirmations()->latest()->first();

        if ($existing instanceof ProcessConfirmation && ! $forceRegenerate) {
            return $existing;
        }

        return DB::transaction(function () use ($application, $actor): ProcessConfirmation {
            $application->loadMissing(['contest', 'user']);
            $processNumber = $this->numbers->generate($application);
            $confirmation = new ProcessConfirmation([
                'title' => 'Confirmação de processo',
                'message' => 'A candidatura foi registada com o número de processo '.$processNumber.'.',
            ]);
            $confirmation->forceFill([
                'confirmation_number' => $this->confirmationNumber(),
                'process_number' => $processNumber,
                'application_id' => $application->id,
                'user_id' => $application->user_id,
                'contest_id' => $application->contest_id,
                'status' => ProcessConfirmationStatus::Generated,
                'payload' => [
                    'application_number' => $application->application_number,
                    'contest_code' => data_get($application->getRelationValue('contest'), 'code'),
                    'generated_at' => now()->toDateTimeString(),
                ],
                'generated_by' => $actor?->id,
            ])->save();

            $this->auditLogger->record(
                AuditEvents::CREATE,
                $confirmation,
                'applications',
                'process_confirmation_generate',
                'Confirmação automática com número de processo gerada.',
                metadata: ['application_id' => $application->id, 'process_number' => $processNumber],
            );

            return $confirmation->refresh();
        });
    }

    public function markSent(ProcessConfirmation $confirmation, ?User $actor = null): ProcessConfirmation
    {
        $confirmation->forceFill([
            'status' => ProcessConfirmationStatus::Sent,
            'sent_at' => now(),
        ])->save();

        $this->auditLogger->record(AuditEvents::UPDATE, $confirmation, 'applications', 'process_confirmation_sent', 'Confirmação de processo marcada como enviada.');

        return $confirmation->refresh();
    }

    public function markFailed(ProcessConfirmation $confirmation, string $reason): ProcessConfirmation
    {
        $confirmation->forceFill([
            'status' => ProcessConfirmationStatus::Failed,
            'failed_at' => now(),
            'failure_reason' => $reason,
        ])->save();

        return $confirmation->refresh();
    }

    private function confirmationNumber(): string
    {
        $next = ProcessConfirmation::withTrashed()->count() + 1;

        do {
            $number = 'CONF-'.now()->format('Y').'-'.str_pad((string) $next, 6, '0', STR_PAD_LEFT);
            $next++;
        } while (ProcessConfirmation::withTrashed()->where('confirmation_number', $number)->exists());

        return $number;
    }
}
