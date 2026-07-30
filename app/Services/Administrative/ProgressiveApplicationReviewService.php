<?php

namespace App\Services\Administrative;

use App\Enums\ApplicationReviewStatus;
use App\Enums\ApplicationReviewType;
use App\Models\AdministrativeProcess;
use App\Models\ApplicationReview;
use App\Models\User;
use App\Services\Audit\AuditLogger;
use App\Support\AuditEvents;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ProgressiveApplicationReviewService
{
    public function __construct(
        private readonly ApplicationReviewReadinessService $readinessService,
        private readonly AuditLogger $auditLogger,
    ) {}

    public function touchActivity(
        AdministrativeProcess $process,
        User $actor,
    ): ApplicationReview {
        return DB::transaction(function () use ($process, $actor) {
            $review = $this->reviewForUpdate($process, $actor);
            $oldStatus = $review->status;

            if (in_array($oldStatus, [
                ApplicationReviewStatus::Draft,
                ApplicationReviewStatus::ReadyForClosure,
            ], true)) {
                $review->forceFill([
                    'status' => ApplicationReviewStatus::InProgress,
                    'result' => null,
                    'ready_for_closure_at' => null,
                    'ready_for_closure_by' => null,
                ]);
            }

            $review->forceFill([
                'reviewed_by' => $actor->id,
                'last_activity_at' => now(),
                'lock_version' => $review->lock_version + 1,
            ])->save();

            if ($oldStatus !== $review->status) {
                $this->auditLogger->record(
                    event: AuditEvents::UPDATE,
                    auditable: $review,
                    module: 'administrative_processes',
                    action: $oldStatus === ApplicationReviewStatus::ReadyForClosure
                        ? 'review_reopened_by_document_activity'
                        : 'progressive_review_started',
                    description: $oldStatus === ApplicationReviewStatus::ReadyForClosure
                        ? 'Análise reaberta automaticamente por nova atividade documental.'
                        : 'Análise documental progressiva iniciada.',
                    oldValues: ['status' => $oldStatus->value],
                    newValues: [
                        'status' => ApplicationReviewStatus::InProgress->value,
                    ],
                    metadata: [
                        'actor_id' => $actor->id,
                        'process_id' => $process->id,
                    ],
                );
            }

            return $review->refresh();
        });
    }

    public function markReadyForClosure(
        AdministrativeProcess $process,
        User $actor,
    ): ApplicationReview {
        return DB::transaction(function () use (
            $process,
            $actor,
        ) {
            $review = $this->reviewForUpdate($process, $actor);
            $readiness = $this->readinessService->forProcess(
                $process->load('application'),
            );

            if (! $readiness['ready']) {
                throw ValidationException::withMessages([
                    'process_ids' => sprintf(
                        'O processo %s não está pronto: %s.',
                        $process->process_number,
                        implode('; ', $readiness['blockers']),
                    ),
                ]);
            }

            $oldStatus = $review->status;

            $review->forceFill([
                'status' => ApplicationReviewStatus::ReadyForClosure,
                'result' => null,
                'reviewed_by' => $actor->id,
                'ready_for_closure_at' => now(),
                'ready_for_closure_by' => $actor->id,
                'last_activity_at' => now(),
                'lock_version' => $review->lock_version + 1,
            ])->save();

            $this->auditLogger->record(
                event: AuditEvents::UPDATE,
                auditable: $review,
                module: 'administrative_processes',
                action: 'review_ready_for_closure',
                description: 'Análise documental marcada como pronta para fecho coletivo.',
                oldValues: ['status' => $oldStatus->value],
                newValues: [
                    'status' => ApplicationReviewStatus::ReadyForClosure->value,
                ],
                metadata: [
                    'actor_id' => $actor->id,
                    'process_id' => $process->id,
                    'readiness' => $readiness,
                ],
            );

            return $review->refresh();
        });
    }

    public function reopen(
        AdministrativeProcess $process,
        User $actor,
        ?string $reason = null,
    ): ApplicationReview {
        if (trim((string) $reason) === '') {
            throw ValidationException::withMessages([
                'reason' => 'Indique o fundamento da reabertura.',
            ]);
        }

        return DB::transaction(function () use (
            $process,
            $actor,
            $reason,
        ) {
            $review = $this->reviewForUpdate($process, $actor);

            if (! $review->isReadyForClosure()) {
                throw ValidationException::withMessages([
                    'process_ids' => sprintf(
                        'O processo %s não está marcado como pronto para fecho.',
                        $process->process_number,
                    ),
                ]);
            }

            $review->forceFill([
                'status' => ApplicationReviewStatus::InProgress,
                'result' => null,
                'reviewed_by' => $actor->id,
                'ready_for_closure_at' => null,
                'ready_for_closure_by' => null,
                'last_activity_at' => now(),
                'lock_version' => $review->lock_version + 1,
            ])->save();

            $this->auditLogger->record(
                event: AuditEvents::UPDATE,
                auditable: $review,
                module: 'administrative_processes',
                action: 'review_reopen',
                description: 'Análise documental reaberta antes do fecho coletivo.',
                oldValues: [
                    'status' => ApplicationReviewStatus::ReadyForClosure->value,
                ],
                newValues: [
                    'status' => ApplicationReviewStatus::InProgress->value,
                ],
                metadata: [
                    'actor_id' => $actor->id,
                    'process_id' => $process->id,
                    'reason' => $reason,
                ],
            );

            return $review->refresh();
        });
    }

    private function reviewForUpdate(
        AdministrativeProcess $process,
        User $actor,
    ): ApplicationReview {
        $lockedProcess = AdministrativeProcess::query()
            ->whereKey($process->getKey())
            ->lockForUpdate()
            ->firstOrFail();

        $review = ApplicationReview::query()
            ->where(
                'administrative_process_id',
                $lockedProcess->id,
            )
            ->where(
                'review_type',
                ApplicationReviewType::Documental->value,
            )
            ->whereNotIn('status', [
                ApplicationReviewStatus::Completed->value,
                ApplicationReviewStatus::Cancelled->value,
            ])
            ->latest('id')
            ->lockForUpdate()
            ->first();

        if ($review instanceof ApplicationReview) {
            return $review;
        }

        $review = new ApplicationReview([
            'review_type' => ApplicationReviewType::Documental,
            'summary' => 'Análise documental progressiva.',
        ]);
        $review->forceFill([
            'administrative_process_id' => $lockedProcess->id,
            'application_id' => $lockedProcess->application_id,
            'status' => ApplicationReviewStatus::InProgress,
            'reviewed_by' => $actor->id,
            'started_at' => now(),
            'last_activity_at' => now(),
            'lock_version' => 0,
        ]);
        $review->save();

        $this->auditLogger->record(
            event: AuditEvents::CREATE,
            auditable: $review,
            module: 'administrative_processes',
            action: 'progressive_review_create',
            description: 'Rascunho de análise documental progressiva criado.',
            metadata: [
                'actor_id' => $actor->id,
                'process_id' => $lockedProcess->id,
            ],
        );

        return $review;
    }
}
